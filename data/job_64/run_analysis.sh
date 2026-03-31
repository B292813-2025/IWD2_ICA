#!/bin/bash
export PATH=/localdisk/home/s2837201/edirect:/usr/bin:/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin:/bin
export MPLCONFIGDIR=/tmp
export NCBI_API_KEY=bc81dc27024bce567d64cb201a28e9ad8508
# run_analysis.sh — JAHbio job 64

JOB_ID=64
BASE=/localdisk/home/s2837201/public_html/ICA/data/job_64
PROTEIN='glucose-6-phosphatase'
TAXON='Aves'
MAX_SEQS=20
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
update_status fetch Running
python3 - <<'PYEOF'
from Bio import Entrez, SeqIO
import sys

Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'
search_term = 'glucose-6-phosphatase[All Fields] AND Aves[organism]'
fasta_out   = '/localdisk/home/s2837201/public_html/ICA/data/job_64/sequences.fasta'
max_seqs    = 20

tax_handle = Entrez.esearch(db='taxonomy', term='Aves')
tax_record = Entrez.read(tax_handle)
tax_handle.close()
if tax_record['Count'] == '0':
    print('Taxon not found in NCBI taxonomy: Aves')
    sys.exit(1)

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
PYEOF
if [ $? -ne 0 ]; then update_status fetch Failed; exit 1; fi

N_SEQS=$(grep -c '^>' "/localdisk/home/s2837201/public_html/ICA/data/job_64/sequences.fasta" 2>/dev/null)
N_SEQS=${N_SEQS:-0}
$DB_UPDATE "UPDATE jobs SET n_returned=$N_SEQS WHERE job_id=$JOB_ID;"
update_status fetch Complete "/localdisk/home/s2837201/public_html/ICA/data/job_64/sequences.fasta"
python3 $BASE/parse_sequences.py

update_status histogram Running
sleep 1
python3 - <<'PYEOF'
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_64/sequences.fasta'
out_png = '/localdisk/home/s2837201/public_html/ICA/data/job_64/histogram.png'

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
update_status histogram Complete '/localdisk/home/s2837201/public_html/ICA/data/job_64/histogram.png'

update_status alignment Running
clustalo -i "$FASTA" -o "$ALIGNED" --outfmt=fasta --force 
if [ $? -eq 0 ]; then
    update_status alignment Complete "$ALIGNED"
else
    update_status alignment Failed
fi

# Plotcon
update_status conservation Running
plotcon -sequence "$ALIGNED" -winsize 4 -graph png -goutfile "$BASE/conservation" -auto 
if [ $? -eq 0 ]; then
    update_status conservation Complete "$BASE/conservation.1.png"
else
    update_status conservation Failed
fi
update_status motif Running
MOTIF_OUT="$BASE/motifs.txt"
patmatmotifs -sequence "$FASTA" -outfile "$MOTIF_OUT" -auto 
if [ $? -eq 0 ]; then
    update_status motif Complete "$MOTIF_OUT"
    python3 $BASE/parse_motifs.py
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
update_status pymol Running
python3 - <<'PYEOF'
from Bio import SeqIO
import subprocess, sys

fasta   = '/localdisk/home/s2837201/public_html/ICA/data/job_64/sequences.fasta'
pdb_out = '/localdisk/home/s2837201/public_html/ICA/data/job_64/structure.pdb'

rec = next(SeqIO.parse(fasta, 'fasta'))
seq = str(rec.seq)

seq = seq[:400] 

result = subprocess.run(
    ['curl', '-s', '-X', 'POST',
     'https://api.esmatlas.com/foldSequence/v1/pdb/',
     '-H', 'Content-Type: application/x-www-form-urlencoded',
     '-d', seq,
     '-o', pdb_out],
    capture_output=True, text=True
)

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
    update_status pymol Complete '/localdisk/home/s2837201/public_html/ICA/data/job_64/structure.pdb'
else
    update_status pymol Failed
fi

echo 'JAHbio job 64 complete.'
