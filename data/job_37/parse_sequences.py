import sys, subprocess, re as _re
from Bio import Entrez

# Selects corresponding fasta file
job_id   = 37
fasta    = '/localdisk/home/s2837201/public_html/ICA/data/job_37/sequences.fasta'
username = 's2837201'
password = 'Elijah271202?'
database = 's2837201_ICA'

# Set up NCBI
Entrez.email   = 's2837201@ed.ac.uk'
Entrez.api_key = 'bc81dc27024bce567d64cb201a28e9ad8508'

records = []
with open(fasta) as f:
    header, seq = None, []
    for line in f:
        line = line.strip()
        if line.startswith('>'): #fasta header
            if header:
                records.append((header, ''.join(seq))) # joins into single string
            header = line[1:] # excluding '>'
            seq = []
        else:
            seq.append(line)
    if header: # final seq saved
        records.append((header, ''.join(seq)))

for header, seq in records:
    parts = header.split(' ', 1) # splits into accession then description
    acc   = parts[0]
    desc  = parts[1] if len(parts) > 1 else ''

    # Clean SwissProt format: e.g. sp|P35575|G6PC1_MOUSE --> P35575
    if '|' in acc:
        acc = acc.split('|')[1]

    # Remove version suffix but keep XP_/NP_ style accessions intact e.g. ABC123.1 --> ABC123
    if '.' in acc and '_' not in acc:
        acc = acc.split('.')[0]

    # Extract species: try OS= field (SwissProt) then [brackets] (RefSeq) e.g. OS=Homo sapiens OX=9606 ...
    os_match = _re.search(r'OS=(.+?)(?:\s+OX=|\s+GN=|\s+PE=|\s*$)', desc)
    if os_match:
        species = os_match.group(1).strip() # gets species name
    elif '[' in desc and desc.endswith(']'): # for this format protein kinase [Homo sapiens] --> get species name
        species = desc[desc.rfind('[')+1:-1]
    else:
        # Fall back to esummary + taxonomy lookup
        try:
            handle = Entrez.esummary(db='protein', id=acc)
            summary = Entrez.read(handle)
            handle.close()
            tax_id = str(summary[0].get('TaxId', ''))
            if tax_id:
                tax_handle = Entrez.efetch(db='taxonomy', id=tax_id, rettype='xml')
                tax_record = Entrez.read(tax_handle)
                tax_handle.close()
                species = tax_record[0].get('ScientificName', '') # gets scientific name
            else:
                species = ''
        except:
            species = '' #if all else fails just leave it blank

    length    = len(seq)
    # no single quotes --> cleaner look
    acc_s     = acc.replace("'", "\\'")
    desc_s    = desc.replace("'", "\\'")
    species_s = species.replace("'", "\\'")

    sql = f"INSERT INTO sequences (job_id, accession, description, species, seq_length) VALUES ({job_id}, '{acc_s}', '{desc_s}', '{species_s}', {length});"
    subprocess.run(['mysql', '-u', username, '-p'+password, database, '-e', sql]) # run the SQL command

print(f'Inserted {len(records)} sequences.')
