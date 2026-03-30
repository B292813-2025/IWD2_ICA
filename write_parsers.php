<?php
function write_parsers($job_id, $base_dir, $username, $password, $database) {

    $templates_dir = __DIR__; //templates stored alongside this file
    // creating an assosciative array
    $replacements = [
        'JOB_ID'   => $job_id,
        'BASE_DIR' => $base_dir,
        'DB_USER'  => $username,
        'DB_PASS'  => $password,
        'DB_NAME'  => $database,
    ];
// iterates over these scripts
    foreach (['parse_sequences.py', 'parse_motifs.py'] as $file) {
        $template = file_get_contents($templates_dir . '/' . $file); // file path as string
        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        } //loops through each pair - replaces all placeholder values
        file_put_contents($base_dir . '/' . $file, $template); //saves modified script too (in job directory)
    }
}
?>
