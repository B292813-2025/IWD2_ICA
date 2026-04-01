#!/bin/bash
export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin
export MPLCONFIGDIR=/tmp
export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508
# run_analysis.sh — JAHbio job 1

JOB_ID=1
BASE=/localdisk/home/s2837201/public_html/ICA/data/job_1
PROTEIN='glucose-6-phosphatase'
TAXON='Aves'
MAX_SEQS=50
FASTA="$BASE/sequences.fasta"
ALIGNED="$BASE/aligned.fasta"
DB_UPDATE="mysql -u s2837201 -pElijah271202? s2837201_ICA -e"

update_status() {
    if [ -z "$3" ]; then
        $DB_UPDATE "UPDATE analysis SET status='$2' WHERE job_id=$JOB_ID AND analysis_type='$1';"
    else
        $DB_UPDATE "UPDATE analysis SET status='$2', output_file='$3' WHERE job_id=$JOB_ID AND analysis_type='$1';"
    fi
}
# ---- STEP 1: Fetch sequences from NCBI ----
update_status fetch Running
python3 - <<'PYEOF'
from Bio import Entrez, SeqIO
import sys

Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'
search_term = 'glucose-6-phosphatase[protein] AND Aves[organism]'
fasta_out   = '/localdisk/home/s2837201/public_html/ICA/data/job_1/sequences.fasta'
max_seqs    = 50


import time
id_list = []
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

N_SEQS=$(grep -c '^>' "/localdisk/home/s2837201/public_html/ICA/data/job_1/sequences.fasta" 2>/dev/null)
N_SEQS=${N_SEQS:-0}
$DB_UPDATE "UPDATE jobs SET n_returned=$N_SEQS WHERE job_id=$JOB_ID;"
update_status fetch Complete "/localdisk/home/s2837201/public_html/ICA/data/job_1/sequences.fasta"
# Parse sequences to JSON then import via PDO
python3 $BASE/parse_sequences.py
php /localdisk/home/s2837201/public_html/ICA/import_sequences.php $JOB_ID $BASE

# ---- STEP 2: Sequence length histogram ----
update_status histogram Running
python3 - <<'PYEOF'
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_1/sequences.fasta'
out_png = '/localdisk/home/s2837201/public_html/ICA/data/job_1/histogram.png'

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
update_status histogram Complete '/localdisk/home/s2837201/public_html/ICA/data/job_1/histogram.png'

# ---- STEP 3: Multiple sequence alignment (ClustalOmega) ----
update_status alignment Running
clustalo -i "$FASTA" -o "$ALIGNED" --outfmt=fasta --force
if [ $? -eq 0 ]; then
    update_status alignment Complete "$ALIGNED"
else
    update_status alignment Failed
fi

# ---- STEP 4: Conservation plot (Plotcon) ----
update_status conservation Running
plotcon -sequence "$ALIGNED" -winsize 4 -graph png -goutfile "$BASE/conservation" -auto
if [ $? -eq 0 ]; then
    update_status conservation Complete "$BASE/conservation.1.png"
else
    update_status conservation Failed
fi
# ---- STEP 5: PROSITE motif scan (patmatmotifs) ----
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
# ---- STEP 6: BLAST similarity search ----
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
# ---- STEP 7: Predict 3D structure with ESMFold ----
update_status pymol Running
python3 - <<'PYEOF'
from Bio import SeqIO
import subprocess, sys

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_1/sequences.fasta'
pdb_out = '/localdisk/home/s2837201/public_html/ICA/data/job_1/structure.pdb'

# Use the first sequence for structure prediction
rec = next(SeqIO.parse(fasta, 'fasta'))
seq = str(rec.seq)

# Truncate to 400aa max — ESMFold is slow on very long sequences
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
PYEOF
if [ $? -eq 0 ]; then
    update_status pymol Complete '/localdisk/home/s2837201/public_html/ICA/data/job_1/structure.pdb'
else
    update_status pymol Failed
fi

# ---- ALL DONE ----
echo 'JAHbio job 1 complete.'
