#!/bin/bash
export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin
export MPLCONFIGDIR=/tmp
export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508
# run_analysis.sh — JAHbio job 22

JOB_ID=22
BASE=/localdisk/home/s2837201/public_html/ICA/data/job_22
PROTEIN='cox1'
TAXON='Mammalia'
MAX_SEQS=50
FASTA="$BASE/sequences.fasta"
ALIGNED="$BASE/aligned.fasta"
PHP_DIR=/localdisk/home/s2837201/public_html/ICA

update_status() {
    if [ -z "$3" ]; then
        php $PHP_DIR/update_status.php $JOB_ID "$1" "$2"
    else
        php $PHP_DIR/update_status.php $JOB_ID "$1" "$2" "$3"
    fi
}
update_status fetch Running
python3 - <<'PYEOF'
from Bio import Entrez, SeqIO
import sys

Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'
search_term = 'cox1[protein] AND Mammalia[organism]'
fasta_out   = '/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta'
max_seqs    = 50

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
PYEOF
if [ $? -ne 0 ]; then update_status fetch Failed; exit 1; fi

N_SEQS=$(grep -c '^>' "/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta" 2>/dev/null)
N_SEQS=${N_SEQS:-0}
php $PHP_DIR/update_job.php $JOB_ID $N_SEQS
update_status fetch Complete "/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta"
# Parse sequences to JSON then import via PDO
python3 $BASE/parse_sequences.py
php /localdisk/home/s2837201/public_html/ICA/import_sequences.php $JOB_ID $BASE

update_status histogram Running
python3 - <<'PYEOF'
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta'
out_png = '/localdisk/home/s2837201/public_html/ICA/data/job_22/histogram.png'


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
PYEOF
update_status histogram Complete '/localdisk/home/s2837201/public_html/ICA/data/job_22/histogram.png'


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
update_status motif Running
MOTIF_OUT="$BASE/motifs.txt"
> "$MOTIF_OUT"
python3 - <<'PYEOF'
from Bio import SeqIO
import subprocess, os, tempfile

fasta    = '/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta'
out_file = '/localdisk/home/s2837201/public_html/ICA/data/job_22/motifs.txt'


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
PYEOF
if [ $? -eq 0 ]; then
    update_status motif Complete "$MOTIF_OUT"
    python3 $BASE/parse_motifs.py
    php /localdisk/home/s2837201/public_html/ICA/import_motifs.php $JOB_ID $BASE
else
    update_status motif Failed
fi

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
update_status pymol Running
python3 - <<'PYEOF'
from Bio import SeqIO
import subprocess, sys

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_22/sequences.fasta'
pdb_out = '/localdisk/home/s2837201/public_html/ICA/data/job_22/structure.pdb'

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
PYEOF
if [ $? -eq 0 ]; then
    update_status pymol Complete '/localdisk/home/s2837201/public_html/ICA/data/job_22/structure.pdb'
else
    update_status pymol Failed
fi

echo 'JAHbio job 22 complete.'
