import subprocess, re

job_id     = 17
motif_file = '/localdisk/home/s2837201/public_html/ICA/data/job_17/motifs.txt'
username   = 's2837201'
password   = 'Elijah271202?'
database   = 's2837201_ICA'

# Build seq_id lookup from DB
result = subprocess.run(
    ['mysql', '-u', username, '-p'+password, database,
     '--batch', '--skip-column-names', '-e',
     f'SELECT seq_id, accession FROM sequences WHERE job_id={job_id};'],
    capture_output=True, text=True
)
seq_lookup = {}
for line in result.stdout.strip().split('\n'):
    if '\t' in line:
        parts = line.split('\t')
        seq_lookup[parts[1]] = parts[0]

current_acc = None
motif_name = start = end = None

with open(motif_file) as f:
    for line in f:
        line = line.rstrip()
        m = re.match(r'^# Sequence: (\S+)', line)
        if m:
            current_acc = m.group(1)
            # Strip version suffix to match accessions in sequences table
            if '.' in current_acc and '_' not in current_acc:
                current_acc = current_acc.split('.')[0]
            motif_name = start = end = None
        m = re.match(r'^Start = position (\d+)', line)
        if m:
            start = m.group(1)
        m = re.match(r'^End = position (\d+)', line)
        if m:
            end = m.group(1)
        m = re.match(r'^Motif = (\S+)', line)
        if m:
            motif_name = m.group(1)
        if motif_name and start and end and current_acc:
            seq_id = seq_lookup.get(current_acc, 'NULL')
            sql = f"INSERT INTO motif_results (job_id, seq_id, motif_name, start_pos, end_pos, score) VALUES ({job_id}, {seq_id}, '{motif_name}', {start}, {end}, 0);"
            subprocess.run(['mysql', '-u', username, '-p'+password, database, '-e', sql])
            motif_name = start = end = None

print('Motif parsing complete.')
