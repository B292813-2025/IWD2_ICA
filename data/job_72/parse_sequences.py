# Parses the FASTA file for this job and writes sequences.json
# to the job directory. The actual DB insertion is handled by
# import_sequences.php via PDO - I updated this file to comply with the PDO requirement

import json
import re as _re
from Bio import Entrez, SeqIO

job_id   = 72
fasta    = '/localdisk/home/s2837201/public_html/ICA/data/job_72/sequences.fasta' #file path
out_json = '/localdisk/home/s2837201/public_html/ICA/data/job_72/sequences.json' #file path

Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'

# Parse FASTA file into records
records = []
with open(fasta) as f:
    header, seq = None, []
    for line in f:
        line = line.strip()
        if line.startswith('>'): # detect sequence
            if header:
                records.append((header, ''.join(seq)))
            header = line[1:]
            seq = []
        else:
            seq.append(line)
    if header:
        records.append((header, ''.join(seq)))

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

# Fetch organism names via GenBank format
organism_map = {}
try:
    handle  = Entrez.efetch(db='protein', id=','.join(accs), rettype='gb', retmode='text')
    gb_recs = list(SeqIO.parse(handle, 'genbank'))
    handle.close()
    for rec in gb_recs:
        org     = rec.annotations.get('organism', '')
        acc_key = rec.id.split('.')[0] if '.' in rec.id and '_' not in rec.id else rec.id
        organism_map[acc_key] = org
        organism_map[rec.id] = org
except Exception as e:
    print(f'Warning: organism lookup failed: {e}')

# Build list of sequence dicts to write to JSON
output = []
for (header, seq), acc in zip(records, accs):
    parts   = header.split(' ', 1)
    desc    = parts[1] if len(parts) > 1 else ''

    # get species from GenBank lookup, fall back to header parsing
    species = organism_map.get(acc, '')
    if not species:
        os_match = _re.search(r'OS=(.+?)(?:\s+OX=|\s+GN=|\s+PE=|\s*$)', desc)
        if os_match:
            species = os_match.group(1).strip()
        elif '[' in desc and desc.endswith(']'):
            species = desc[desc.rfind('[')+1:-1]

    output.append({
        'accession': acc,
        'description': desc,
        'species': species,
        'seq_length': len(seq)
    })

# write to JSON
# DB insertion handled by import_sequences.php via PDO
with open(out_json, 'w') as f:
    json.dump(output, f)

print(f'Wrote {len(output)} sequences to sequences.json.')
