<?php
// Returns the complete run_analysis.sh bash script as a string
// Called once per job from search.php
// fetch --> histogram --> alignment --> motifs --> blast --> structure

function generate_script($job_id, $base_dir, $protein, $taxon, $max_seqs,
                          $do_align, $do_motif, $do_blast, $do_pymol,
                          $username, $password, $database, $search_type = 'All Fields') {
   
    //safe shell insertion
    $protein_safe = escapeshellarg($protein);
    $taxon_safe   = escapeshellarg($taxon);
    // directory containing helper PHP script
    $php_dir      = '/localdisk/home/s2837201/public_html/ICA';

    // Header and environment
    // ensure required tools are present in PATH
    $s  = "#!/bin/bash\n";
    $s .= "export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin\n";
    $s .= "export MPLCONFIGDIR=/tmp\n";
    $s .= "export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508\n";
    $s .= "# run_analysis.sh — JAHbio job {$job_id}\n\n";

    // define bash variables (also set file paths)
    $s .= "JOB_ID={$job_id}\n";
    $s .= "BASE={$base_dir}\n";
    $s .= "PROTEIN={$protein_safe}\n";
    $s .= "TAXON={$taxon_safe}\n";
    $s .= "MAX_SEQS={$max_seqs}\n";
    $s .= 'FASTA="$BASE/sequences.fasta"' . "\n";
    $s .= 'ALIGNED="$BASE/aligned.fasta"' . "\n";
    $s .= "PHP_DIR={$php_dir}\n\n";

    // update_status helper 
    // $1 = analysis type
    // $2 = status 
    // $3 = output file path
    $s .= <<<'BASH'
update_status() {
    if [ -z "$3" ]; then
        php $PHP_DIR/update_status.php $JOB_ID "$1" "$2"
    else
        php $PHP_DIR/update_status.php $JOB_ID "$1" "$2" "$3"
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
    $s .= "search_term = '{$protein}[{$search_type}] AND {$taxon}[organism]'\n";
    $s .= "fasta_out   = '{$base_dir}/sequences.fasta'\n";
    $s .= "max_seqs    = {$max_seqs}\n\n";
    $s .= <<<'PYEOF'
import time
id_list = []
# retry search up to 3 times (handles NCBI errors)
for attempt in range(3):
    try:
        handle  = Entrez.esearch(db='protein', term=search_term, retmax=max_seqs)
        record  = Entrez.read(handle)
        handle.close()
        id_list = record['IdList']
        break
    except RuntimeError:
        if attempt < 2:
            time.sleep(3)
        else:
            print('NCBI search failed after 3 attempts')
            sys.exit(1)

# Require at least 2 sequences - exit
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
    // Check exit status of Python script
    $s .= "if [ \$? -ne 0 ]; then update_status fetch Failed; exit 1; fi\n\n";
    // Count number of sequences retrieved
    $s .= "N_SEQS=\$(grep -c '^>' \"{$base_dir}/sequences.fasta\" 2>/dev/null)\n";
    $s .= "N_SEQS=\${N_SEQS:-0}\n";
    // Update jobs table with number of sequences
    $s .= "php \$PHP_DIR/update_job.php \$JOB_ID \$N_SEQS\n";
    // Mark step as complete and store output file
    $s .= "update_status fetch Complete \"{$base_dir}/sequences.fasta\"\n";
    $s .= "# Parse sequences to JSON then import via PDO\n";
    $s .= "python3 \$BASE/parse_sequences.py\n";
    $s .= "php /localdisk/home/s2837201/public_html/ICA/import_sequences.php \$JOB_ID \$BASE\n\n";

    // 2: Histogram - plot with matplotlib.pyplot - bins set to auto (different # retrieved each time)
    $s .= "update_status histogram Running\n";
    $s .= "python3 - <<'PYEOF'\n";
    $s .= "import matplotlib\nmatplotlib.use('Agg')\nimport matplotlib.pyplot as plt\n\n";
    $s .= "fasta   = '{$base_dir}/sequences.fasta'\n";
    $s .= "out_png = '{$base_dir}/histogram.png'\n\n";
    $s .= <<<'PYEOF'

# extract sequence lengths from FASTA
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

    //3+4: Alignment + Conservation
    if ($do_align) {
        $s .= <<<'BASH'

# MSA using Clustal Omega
update_status alignment Running
clustalo -i "$FASTA" -o "$ALIGNED" --outfmt=fasta --force

# Check result and update status
if [ $? -eq 0 ]; then
    update_status alignment Complete "$ALIGNED"
else
    update_status alignment Failed
fi

# Conservation plot using EMBOSS plotcon
update_status conservation Running
plotcon -sequence "$ALIGNED" -winsize 4 -graph png -goutfile "$BASE/conservation" -auto
if [ $? -eq 0 ]; then
    update_status conservation Complete "$BASE/conservation.1.png"
else
    update_status conservation Failed
fi

BASH;
    }

    // 5: PROSITE motif scan (patmatmotifs)
    if ($do_motif) {
        $s .= "update_status motif Running\n";
        $s .= "MOTIF_OUT=\"\$BASE/motifs.txt\"\n";
        $s .= "> \"\$MOTIF_OUT\"\n";

        // Python script processes each sequence individually
        $s .= "python3 - <<'PYEOF'\n";
        $s .= "from Bio import SeqIO\n";
        $s .= "import subprocess, os, tempfile\n\n";
        $s .= "fasta    = '{$base_dir}/sequences.fasta'\n";
        $s .= "out_file = '{$base_dir}/motifs.txt'\n\n";
        $s .= <<<'PYEOF'

# run motif scan per sequence
with open(out_file, 'w') as combined:
    for rec in SeqIO.parse(fasta, 'fasta'):
        # clean accession to match what is stored in sequences table
        acc = rec.id
        if '|' in acc:
            parts = acc.split('|')
            if parts[0] == 'pdb' and len(parts) >= 3:
                acc = parts[1] + '_' + parts[2]
            else:
                acc = parts[1]
        # write single sequence to temp file using cleaned accession
        with tempfile.NamedTemporaryFile(mode='w', suffix='.fasta', delete=False) as tmp:
            tmp.write(f'>{acc}\n{str(rec.seq)}\n')
            tmp_path = tmp.name
        with tempfile.NamedTemporaryFile(mode='w', suffix='.txt', delete=False) as out:
            out_path = out.name
        # run patmatmotifs on single sequence
        subprocess.run(['patmatmotifs', '-sequence', tmp_path, '-outfile', out_path, '-auto'],
                       capture_output=True)
        # Append results
        with open(out_path) as f:
            combined.write(f.read())

        # Clean up temp files
        os.unlink(tmp_path)
        os.unlink(out_path)

print('Motif scan complete.')
PYEOF;
        $s .= "\nPYEOF\n";
        $s .= "if [ \$? -eq 0 ]; then\n";
        $s .= "    update_status motif Complete \"\$MOTIF_OUT\"\n";
        $s .= "    python3 \$BASE/parse_motifs.py\n";
        $s .= "    php /localdisk/home/s2837201/public_html/ICA/import_motifs.php \$JOB_ID \$BASE\n";
        $s .= "else\n";
        $s .= "    update_status motif Failed\n";
        $s .= "fi\n\n";
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

# truncate to 400aa max — ESMFold is slow on very long sequences - improved UX
seq = seq[:400]

result = subprocess.run(
    ['curl', '-s', '-X', 'POST',
     'https://api.esmatlas.com/foldSequence/v1/pdb/',
     '-H', 'Content-Type: application/x-www-form-urlencoded',
     '-d', seq,
     '-o', pdb_out],
    capture_output=True, text=True
)

# validate output
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
