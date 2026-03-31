<?php
// Copies parse_sequences.py and parse_motifs.py templates into the job directory, substituting job-specific values
// The parsers write JSON files; DB insertion is handled by import_sequences.php and import_motifs.php via PDO

function write_parsers($job_id, $base_dir, $username, $password, $database) {

    $templates_dir = __DIR__;

    // Only job_id and base_dir needed — no DB credentials in python - originally was
    $replacements = [
        'JOB_ID'   => $job_id,
        'BASE_DIR' => $base_dir,
    ];

    foreach (['parse_sequences.py', 'parse_motifs.py'] as $file) {
        $template = file_get_contents($templates_dir . '/' . $file);
        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }
        file_put_contents($base_dir . '/' . $file, $template);
    }
}
?>
