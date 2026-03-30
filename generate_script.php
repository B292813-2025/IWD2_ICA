<?php
// function defined
function generate_script($job_id, $base_dir, $protein, $taxon, $max_seqs,
                          $do_align, $do_motif, $do_blast, $do_pymol,
                          $username, $password, $database) {

    $protein_safe = escapeshellarg($protein); //ensures treated as 1 argument
    $taxon_safe   = escapeshellarg($taxon);
    $db_update    = "mysql -u $username -p$password $database -e"; //saved as variable 

    $s  = "#!/bin/bash\n"; //shebang
    $s .= "export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin\n"; //manually sets path to BLAST (was suggested by Claude AI as I was running into an issue with this)
    $s .= "export MPLCONFIGDIR=/tmp\n"; //this line is used to avoid any matplotlib issues 
    $s .= "export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508\n"; //set the API key
    $s .= "# run_analysis.sh — JAHbio job {$job_id}\n\n"; // comment header

// Bash variables
    $s .= "JOB_ID={$job_id}\n";
    $s .= "BASE={$base_dir}\n";
    $s .= "PROTEIN={$protein_safe}\n";
    $s .= "TAXON={$taxon_safe}\n";
    $s .= "MAX_SEQS={$max_seqs}\n";
    $s .= 'FASTA="$BASE/sequences.fasta"' . "\n"; //file path defined
    $s .= 'ALIGNED="$BASE/aligned.fasta"' . "\n"; // file path defined
    $s .= "DB_UPDATE=\"{$db_update}\"\n\n"; //stores the mySQL line as a variable

// Bash again
    $s .= <<<'BASH'
update_status() {
    if [ -z "$3" ]; then
        $DB_UPDATE "UPDATE analysis SET status='$2' WHERE job_id=$JOB_ID AND analysis_type='$1';" //if no output file provided update the status only
    else
        $DB_UPDATE "UPDATE analysis SET status='$2', output_file='$3' WHERE job_id=$JOB_ID AND analysis_type='$1';" //else store file too
    fi
}

BASH;

// Biopython - fetch sequences

    $s .= "update_status fetch Running\n"; //set status to Running
    $s .= "python3 - <<'PYEOF'\n"; //starts inline python session
    $s .= "from Bio import Entrez, SeqIO\n"; 
    $s .= "import sys\n\n"; //import tools
    $s .= "Entrez.email   = 's2837201@ed.ac.uk'\n";
    $s .= "Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'\n";
    $s .= "search_term = '{$protein}[All Fields] AND {$taxon}[organism]'\n"; //define search and parameters to be used
    $s .= "fasta_out   = '{$base_dir}/sequences.fasta'\n"; 
    $s .= "max_seqs    = {$max_seqs}\n\n";
    $s .= "tax_handle = Entrez.esearch(db='taxonomy', term='{$taxon}')\n"; //check if taxon exists in NCBI
    $s .= "tax_record = Entrez.read(tax_handle)\n";
    $s .= "tax_handle.close()\n";
    $s .= "if tax_record['Count'] == '0':\n"; //exit immediately if there are no records for the taxon rather than running the analysis (progressing to next page) - improved user experience
    $s .= "    print('Taxon not found in NCBI taxonomy: {$taxon}')\n";
    $s .= "    sys.exit(1)\n\n";
    $s .= <<<'PYEOF'
handle  = Entrez.esearch(db='protein', term=search_term, retmax=max_seqs) //search
record  = Entrez.read(handle) // read
handle.close() //close it
id_list = record['IdList'] //list of IDs

if len(id_list) < 2:
    print(f"Only {len(id_list)} sequences found - exiting")
    sys.exit(1)

fetch_handle = Entrez.efetch(db='protein', id=','.join(id_list), rettype='fasta', retmode='text') //fetch sequences
with open(fasta_out, 'w') as f:
    f.write(fetch_handle.read()) //writes to a fasta file
fetch_handle.close()

print(f"Fetched {len(id_list)} sequences.") //logs it
PYEOF;
    $s .= "\nPYEOF\n";
    $s .= "if [ \$? -ne 0 ]; then update_status fetch Failed; exit 1; fi\n\n"; //exit if failed
    $s .= "N_SEQS=\$(grep -c '^>' \"{$base_dir}/sequences.fasta\" 2>/dev/null)\n"; //counts number of sequences
    $s .= "N_SEQS=\${N_SEQS:-0}\n";
    $s .= "\$DB_UPDATE \"UPDATE jobs SET n_returned=\$N_SEQS WHERE job_id=\$JOB_ID;\"\n"; //stores number of sequences returned in the DB
    $s .= "update_status fetch Complete \"{$base_dir}/sequences.fasta\"\n"; //status set to complete
    $s .= "python3 \$BASE/parse_sequences.py\n\n";

// Histogram time
    $s .= "update_status histogram Running\n";
    $s .= "python3 - <<'PYEOF'\n";
    $s .= "import matplotlib\nmatplotlib.use('Agg')\nimport matplotlib.pyplot as plt\n\n";
    $s .= "fasta   = '{$base_dir}/sequences.fasta'\n"; //fasta file in job directory - all these results are stored server-side
    $s .= "out_png = '{$base_dir}/histogram.png'\n\n";
    $s .= <<<'PYEOF'
lengths = []
with open(fasta) as f:
    seq = ""
    for line in f:
        if line.startswith(">"): //detetcting new sequence
            if seq: lengths.append(len(seq))
            seq = ""
        else:
            seq += line.strip()
    if seq: lengths.append(len(seq)) //record the length

plt.figure(figsize=(8,5))
plt.hist(lengths, bins='auto', color='#c8102e', edgecolor='#041e42') //set to auto (different #seq fetched)
plt.xlabel('Sequence Length (aa)')
plt.ylabel('Count')
plt.tight_layout()
plt.savefig(out_png, dpi=150) //save image
PYEOF;
    $s .= "\nPYEOF\n";
    $s .= "update_status histogram Complete '{$base_dir}/histogram.png'\n\n"; //sets status as complete

// Alignment and conservation
    if ($do_align) {
        $s .= <<<'BASH'
update_status alignment Running
clustalo -i "$FASTA" -o "$ALIGNED" --outfmt=fasta --force //MSA run command
if [ $? -eq 0 ]; then
    update_status alignment Complete "$ALIGNED"
else
    update_status alignment Failed
fi

// Plotcon
update_status conservation Running
plotcon -sequence "$ALIGNED" -winsize 4 -graph png -goutfile "$BASE/conservation" -auto //create conservation plot
if [ $? -eq 0 ]; then
    update_status conservation Complete "$BASE/conservation.1.png"
else
    update_status conservation Failed
fi

BASH;
    }

// Prosite motuf scan
    if ($do_motif) {
        $s .= <<<'BASH'
update_status motif Running
MOTIF_OUT="$BASE/motifs.txt"
patmatmotifs -sequence "$FASTA" -outfile "$MOTIF_OUT" -auto //finds motifs
if [ $? -eq 0 ]; then
    update_status motif Complete "$MOTIF_OUT"
    python3 $BASE/parse_motifs.py
else
    update_status motif Failed
fi

BASH;
    }

// BLAST
    if ($do_blast) {
        $s .= <<<'BASH'
# ---- STEP 6: BLAST similarity search ----
update_status blast Running
BLAST_DB="$BASE/blastdb"
BLAST_OUT="$BASE/blast.txt"
makeblastdb -in "$FASTA" -dbtype prot -out "$BLAST_DB" -parse_seqids //making a local BLAST database
blastp -db "$BLAST_DB" -query "$FASTA" -outfmt 7 -out "$BLAST_OUT" -max_target_seqs 10 //run blast
if [ $? -eq 0 ]; then
    update_status blast Complete "$BLAST_OUT"
else
    update_status blast Failed
fi

BASH;
    }

// 3D structure prediction
    if ($do_pymol) {
        $s .= "update_status pymol Running\n";
        $s .= "python3 - <<'PYEOF'\n";
        $s .= "from Bio import SeqIO\n";
        $s .= "import subprocess, sys\n\n";
        $s .= "fasta   = '{$base_dir}/sequences.fasta'\n";
        $s .= "pdb_out = '{$base_dir}/structure.pdb'\n\n";
        $s .= <<<'PYEOF'
rec = next(SeqIO.parse(fasta, 'fasta'))
seq = str(rec.seq)

seq = seq[:400] //limiting to 400 (Meta API constraint)

result = subprocess.run(
    ['curl', '-s', '-X', 'POST',
     'https://api.esmatlas.com/foldSequence/v1/pdb/',
     '-H', 'Content-Type: application/x-www-form-urlencoded',
     '-d', seq,
     '-o', pdb_out],
    capture_output=True, text=True
)
// calls Meta ESMFold API

with open(pdb_out) as f:
    first_line = f.readline()
if 'HEADER' in first_line or 'ATOM' in first_line: //make sure valid PDB file is fetched
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
