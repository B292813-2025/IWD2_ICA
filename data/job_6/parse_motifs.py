# parses the Patmatmotifs output for this job and writes motifs.json to the job directory. 
# the actual DB insertion is handled by import_motifs.php via PDO

import json
import re

job_id     = 6
motif_file = '/localdisk/home/s2837201/public_html/ICA/data/job_6/motifs.txt'
out_json   = '/localdisk/home/s2837201/public_html/ICA/data/job_6/motifs.json'

output = []

current_acc = None
motif_name = start = end = None

with open(motif_file) as f:
    for line in f:
        line = line.rstrip()

        # match sequence header line ("# Sequence: KAJ7421106.1")
        m = re.match(r'^# Sequence: (\S+)', line)
        if m:
            current_acc = m.group(1)
            # strip to match the accessions in sequences table
            if '.' in current_acc and '_' not in current_acc:
                current_acc = current_acc.split('.')[0]
            motif_name = start = end = None

        # match start position
        m = re.match(r'^Start = position (\d+)', line)
        if m:
            start = int(m.group(1))

        # match end position
        m = re.match(r'^End = position (\d+)', line)
        if m:
            end = int(m.group(1))

        # match motif name (appears after start and end in patmatmotifs output)
        m = re.match(r'^Motif = (\S+)', line)
        if m:
            motif_name = m.group(1)

        # Once all three are collected - record 
        if motif_name and start and end and current_acc:
            output.append({
                'accession':  current_acc,
                'motif_name': motif_name,
                'start_pos':  start,
                'end_pos':    end,
                'score':      0
            })
            motif_name = start = end = None

# write to JSON —-> DB insertion handled by import_motifs.php via PDO
with open(out_json, 'w') as f:
    json.dump(output, f)

print(f'Wrote {len(output)} motif hits to motifs.json.')
