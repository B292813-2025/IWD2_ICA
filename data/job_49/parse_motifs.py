import subprocess, re

job_id     = 49
motif_file = '/localdisk/home/s2837201/public_html/ICA/data/job_49/motifs.txt'
username   = 's2837201'
password   = 'Elijah271202?'
database   = 's2837201_ICA'

# Build seq_id lookup from DB - query mySQL
result = subprocess.run(
    ['mysql', '-u', username, '-p'+password, database,
     '--batch', '--skip-column-names', '-e',
     f'SELECT seq_id, accession FROM sequences WHERE job_id={job_id};'],
    capture_output=True, text=True
)
# Empty dictionary
seq_lookup = {}
# For every line in what we queried (whitespace removed)
for line in result.stdout.strip().split('\n'):
    if '\t' in line:
        parts = line.split('\t')
        seq_lookup[parts[1]] = parts[0]
#^ parts[1] = accession, parts[0] = seq_id --> maps them
# tracks accession, motif name
current_acc = None
motif_name = start = end = None

with open(motif_file) as f:
    for line in f:
        line = line.rstrip()
        # search for lines with #....
        m = re.match(r'^# Sequence: (\S+)', line)
        if m:
            current_acc = m.group(1)
            # strip version suffix to match accessions in sequences table
            if '.' in current_acc and '_' not in current_acc:
                current_acc = current_acc.split('.')[0]
            # reset
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
            seq_id = seq_lookup.get(current_acc, 'NULL') #gets seq_id from dictionary if not there defaults to NULL
            sql = f"INSERT INTO motif_results (job_id, seq_id, motif_name, start_pos, end_pos, score) VALUES ({job_id}, {seq_id}, '{motif_name}', {start}, {end}, 0);"
            subprocess.run(['mysql', '-u', username, '-p'+password, database, '-e', sql]) #runs the SQL command
            motif_name = start = end = None # no duplicated inserts

print('Motif parsing complete.')
