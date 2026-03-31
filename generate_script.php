<?php
// Returns the complete run_analysis.sh bash script as a string.
// Called once per job from search.php.

function generate_script($job_id, $base_dir, $protein, $taxon, $max_seqs,
                          $do_align, $do_motif, $do_blast, $do_pymol,
                          $username, $password, $database) {

    $protein_safe = escapeshellarg($protein);
    $taxon_safe   = escapeshellarg($taxon);
    $db_update    = "mysql -u $username -p$password $database -e";

    //  Header and environment
    $s  = "#!/bin/bash\n";
    $s .= "export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin\n";
    $s .= "export MPLCONFIGDIR=/tmp\n";
    $s .= "export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508\n";
    $s .= "# run_analysis.sh — JAHbio job {$job_id}\n\n";

    // Bash variables
    $s .= "JOB_ID={$job_id}\n";
    $s .= "BASE={$base_dir}\n";
    $s .= "PROTEIN={$protein_safe}\n";
    $s .= "TAXON={$taxon_safe}\n";
    $s .= "MAX_SEQS={$max_seqs}\n";
    $s .= 'FASTA="$BASE/sequences.fasta"' . "\n";
    $s .= 'ALIGNED="$BASE/aligned.fasta"' . "\n";
    $s .= "DB_UPDATE=\"{$db_update}\"\n\n";

    // update_status helper
    $s .= <<<'BASH'
update_status() {
    if [ -z "$3" ]; then
        $DB_UPDATE "UPDATE analysis SET status='$2' WHERE job_id=$JOB_ID AND analysis_type='$1';"
    else
        $DB_UPDATE "UPDATE analysis SET status='$2', output_file='$3' WHERE job_id=$JOB_ID AND analysis_type='$1';"
    fi
}

BASH;

    // 1: Fetch using Biopython
    $s .= "update_status fetch Running\n";
    $s .= "python3 - <<'PYEOF'\n";
    $s .= "from Bio import Entrez, SeqIO\n";
    $s .= "import sys\n\n";
    $s .= "Entrez.email   = 's2837201@ed.ac.uk'\n";
    $s .= "Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'\n";
    $s .= "search_term = '{$protein}[protein] AND {$taxon}[organism]'\n";
    $s .= "fasta_out   = '{$base_dir}/sequences.fasta'\n";
    $s .= "max_seqs    = {$max_seqs}\n\n";
    $s .= <<<'PYEOF'
handle  = Entrez.esearch(db='protein', term=search_term, retmax=max_seqs)
record  = Entrez.read(handle)
handle.close()
id_list = record['IdList']

if len(id_list) < 2:
    print(f"Only {len(id_list)} sequences found - exiting")
    sys.exit(1)

fetch_handle = Entrez.efetch(db='protein', id=','.join(id_list), rettype='fasta', retmode='text')
with open(fasta_out, 'w') as f:
    f.write(fetch_handle.read())
fetch_handle.close()

print(f"Fetched {len(id_list)} sequences.")
PYEOF;
    $s .= "\nPYEOF\n";
    $s .= "if [ \$? -ne 0 ]; then update_status fetch Failed; exit 1; fi\n\n";
    $s .= "N_SEQS=\$(grep -c '^>' \"{$base_dir}/sequences.fasta\" 2>/dev/null)\n";
    $s .= "N_SEQS=\${N_SEQS:-0}\n";
    $s .= "\$DB_UPDATE \"UPDATE jobs SET n_returned=\$N_SEQS WHERE job_id=\$JOB_ID;\"\n";
    $s .= "update_status fetch Complete \"{$base_dir}/sequences.fasta\"\n";
    $s .= "# Parse sequences to JSON then import via PDO\n";
    $s .= "python3 \$BASE/parse_sequences.py\n";
    $s .= "php /localdisk/home/s2837201/public_html/ICA/import_sequences.php \$JOB_ID \$BASE\n\n";

    // 2: Histogram
    $s .= "update_status histogram Running\n";
    $s .= "python3 - <<'PYEOF'\n";
    $s .= "import matplotlib\nmatplotlib.use('Agg')\nimport matplotlib.pyplot as plt\n\n";
    $s .= "fasta   = '{$base_dir}/sequences.fasta'\n";
    $s .= "out_png = '{$base_dir}/histogram.png'\n\n";
    $s .= <<<'PYEOF'
lengths = []
with open(fasta) as f:
    seq = ""
    for line in f:
        if line.startswith(">"):
            if seq: lengths.append(len(seq))
            seq = ""
        else:
            seq += line.strip()
    if seq: lengths.append(len(seq))

plt.figure(figsize=(8,5))
plt.hist(lengths, bins='auto', color='#c8102e', edgecolor='#041e42')
plt.xlabel('Sequence Length (aa)')
plt.ylabel('Count')
plt.tight_layout()
plt.savefig(out_png, dpi=150)
PYEOF;
    $s .= "\nPYEOF\n";
    $s .= "update_status histogram Complete '{$base_dir}/histogram.png'\n\n";

    // 3,4: Alignment + Conservation
    if ($do_align) {
        $s .= <<<'BASH'
update_status alignment Running
clustalo -i "$FASTA" -o "$ALIGNED" --outfmt=fasta --force
if [ $? -eq 0 ]; then
    update_status alignment Complete "$ALIGNED"
else
    update_status alignment Failed
fi

update_status conservation Running
plotcon -sequence "$ALIGNED" -winsize 4 -graph png -goutfile "$BASE/conservation" -auto
if [ $? -eq 0 ]; then
    update_status conservation Complete "$BASE/conservation.1.png"
else
    update_status conservation Failed
fi

BASH;
    }

    // 5: PROSITE motif scan
    if ($do_motif) {
        $s .= <<<'BASH'
update_status motif Running
MOTIF_OUT="$BASE/motifs.txt"
patmatmotifs -sequence "$FASTA" -outfile "$MOTIF_OUT" -auto
if [ $? -eq 0 ]; then
    update_status motif Complete "$MOTIF_OUT"
    # Parse motifs to JSON then import via PDO
    python3 $BASE/parse_motifs.py
    php /localdisk/home/s2837201/public_html/ICA/import_motifs.php $JOB_ID $BASE
else
    update_status motif Failed
fi

BASH;
    }

    // 6: BLAST 
    if ($do_blast) {
        $s .= <<<'BASH'
update_status blast Running
BLAST_DB="$BASE/blastdb"
BLAST_OUT="$BASE/blast.txt"
makeblastdb -in "$FASTA" -dbtype prot -out "$BLAST_DB" -parse_seqids
blastp -db "$BLAST_DB" -query "$FASTA" -outfmt 7 -out "$BLAST_OUT" -max_target_seqs 10
if [ $? -eq 0 ]; then
    update_status blast Complete "$BLAST_OUT"
else
    update_status blast Failed
fi

BASH;
    }

    // 7: ESMFold structure prediction
    if ($do_pymol) {
        $s .= "update_status pymol Running\n";
        $s .= "python3 - <<'PYEOF'\n";
        $s .= "from Bio import SeqIO\n";
        $s .= "import subprocess, sys\n\n";
        $s .= "fasta   = '{$base_dir}/sequences.fasta'\n";
        $s .= "pdb_out = '{$base_dir}/structure.pdb'\n\n";
        $s .= <<<'PYEOF'
# Use the first sequence for structure prediction
rec = next(SeqIO.parse(fasta, 'fasta'))
seq = str(rec.seq)

# Truncate to 400aa max - ESMFold is slow on very long sequences - poor user experience if they have to wait long
seq = seq[:400]

result = subprocess.run(
    ['curl', '-s', '-X', 'POST',
     'https://api.esmatlas.com/foldSequence/v1/pdb/',
     '-H', 'Content-Type: application/x-www-form-urlencoded',
     '-d', seq,
     '-o', pdb_out],
    capture_output=True, text=True
)

# Validate output
with open(pdb_out) as f:
    first_line = f.readline()
if 'HEADER' in first_line or 'ATOM' in first_line:
    print(f"Structure predicted for {rec.id}")
    sys.exit(0)
else:
    print("ESMFold returned invalid response")
    sys.exit(1)
PYEOF;
        $s .= "\nPYEOF\n";
        $s .= "if [ \$? -eq 0 ]; then\n";
        $s .= "    update_status pymol Complete '{$base_dir}/structure.pdb'\n";
        $s .= "else\n";
        $s .= "    update_status pymol Failed\n";
        $s .= "fi\n\n";
    }

    $s .= "echo 'JAHbio job {$job_id} complete.'\n";

    return $s;
}
?>
