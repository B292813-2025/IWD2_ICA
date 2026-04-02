#parses the Patmatmotifs output for this job and writes motifs.json to the job directory 
# The actual DB insertion is handled by import_motifs.php via PDO to comply with the PDO requirement - was initially python

import json
import re

job_id     = 28
motif_file = '/localdisk/home/s2837201/public_html/ICA/data/job_28/motifs.txt'
out_json   = '/localdisk/home/s2837201/public_html/ICA/data/job_28/motifs.json'

output = []

current_acc = None
motif_name = start = end = None

with open(motif_file) as f:
    for line in f:
        line = line.rstrip()

        # Match sequence header line e.g. "# Sequence: KAJ7421106.1"
        m = re.match(r'^# Sequence: (\S+)', line)
        if m:
            current_acc = m.group(1)
            motif_name = start = end = None

        # Match start position
        m = re.match(r'^Start = position (\d+)', line)
        if m:
            start = int(m.group(1))

        # Match end position
        m = re.match(r'^End = position (\d+)', line)
        if m:
            end = int(m.group(1))

        # Match motif name — appears after Start and End in patmatmotifs output
        m = re.match(r'^Motif = (\S+)', line)
        if m:
            motif_name = m.group(1)

        # Once all three fields collected, record the hit
        if motif_name and start and end and current_acc:
            output.append({
                'accession':  current_acc,
                'motif_name': motif_name,
                'start_pos':  start,
                'end_pos':    end,
                'score':      0
            })
            motif_name = start = end = None

# Write to JSON — DB insertion handled by import_motifs.php via PDO
with open(out_json, 'w') as f:
    json.dump(output, f)

print(f'Wrote {len(output)} motif hits to motifs.json.')
