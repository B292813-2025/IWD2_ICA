<?php
// Copies parse_sequences.py and parse_motifs.py templates into the job directory, substituting job-specific values
// The parsers write JSON files; DB insertion is handled by import_sequences.php and import_motifs.php via PDO

function write_parsers($job_id, $base_dir, $username, $password, $database) {
   // Get the directory where this PHP file is located
    $templates_dir = __DIR__;

    // Only job_id and base_dir needed — no DB credentials in python - originally was
    // Replace job_ID with the actual job ID and base_dir with the actual job directory path
    $replacements = [
        'JOB_ID'   => $job_id,
        'BASE_DIR' => $base_dir,
    ];

    // Loop through each parser template file
    // Read the contents of the template file into a string
    // Replace all placeholders in the template with actual values
    // And write contents to a file inside the job directory
    foreach (['parse_sequences.py', 'parse_motifs.py'] as $file) {
        $template = file_get_contents($templates_dir . '/' . $file);
        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }
        file_put_contents($base_dir . '/' . $file, $template);
    }
}
?>
