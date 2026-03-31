import sys, subprocess, re as _re
from Bio import Entrez, SeqIO

job_id   = 54
fasta    = '/localdisk/home/s2837201/public_html/ICA/data/job_54/sequences.fasta'
username = 's2837201'
password = 'Elijah271202?'
database = 's2837201_ICA'

Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'

# Parse FASTA file
records = []
with open(fasta) as f:
    header, seq = None, []
    for line in f:
        line = line.strip()
        if line.startswith('>'):
            if header:
                records.append((header, ''.join(seq)))
            header = line[1:]
            seq = []
        else:
            seq.append(line)
    if header:
        records.append((header, ''.join(seq)))

# Clean accessions
def clean_acc(raw):
    acc = raw.split(' ', 1)[0]
    if '|' in acc:
        parts = acc.split('|')
        if parts[0] == 'pdb' and len(parts) >= 3:
            acc = parts[1] + '_' + parts[2]  # e.g. 9PWQ_A
        else:
            acc = parts[1]
    if '.' in acc and '_' not in acc:
        acc = acc.split('.')[0]
    return acc

accs = [clean_acc(h) for h, s in records]

# Batch fetch organism names via GenBank records
organism_map = {}
try:
    handle  = Entrez.efetch(db='protein', id=','.join(accs), rettype='gb', retmode='text')
    gb_recs = list(SeqIO.parse(handle, 'genbank'))
    handle.close()
    for rec in gb_recs:
        org = rec.annotations.get('organism', '')
        # Map by accession (strip version)
        acc_key = rec.id.split('.')[0] if '.' in rec.id and '_' not in rec.id else rec.id
        organism_map[acc_key] = org
        # Also map the full versioned accession
        organism_map[rec.id] = org
except Exception as e:
    print(f'Warning: organism lookup failed: {e}')

# Insert into database
for (header, seq), acc in zip(records, accs):
    parts = header.split(' ', 1)
    desc  = parts[1] if len(parts) > 1 else ''

    # Get organism from GenBank lookup, fall back to header parsing
    species = organism_map.get(acc, '')
    if not species:
        os_match = _re.search(r'OS=(.+?)(?:\s+OX=|\s+GN=|\s+PE=|\s*$)', desc)
        if os_match:
            species = os_match.group(1).strip()
        elif '[' in desc and desc.endswith(']'):
            species = desc[desc.rfind('[')+1:-1]

    length    = len(seq)
    acc_s     = acc.replace("'", "\\'")
    desc_s    = desc.replace("'", "\\'")
    species_s = species.replace("'", "\\'")

    sql = f"INSERT INTO sequences (job_id, accession, description, species, seq_length) VALUES ({job_id}, '{acc_s}', '{desc_s}', '{species_s}', {length});"
    subprocess.run(['mysql', '-u', username, '-p'+password, database, '-e', sql]) #add to mySQL DB

print(f'Inserted {len(records)} sequences.')
