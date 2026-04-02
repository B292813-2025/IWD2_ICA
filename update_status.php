<?php
// updates the status of an analysis step in the DB via PDO.
// called from run_analysis.sh as:
// php update_status.php <job_id> <type> <status> [output_file]

require_once __DIR__ . '/login.php';

$job_id      = intval($argv[1] ?? 0); //job id (must be >0)
$type        = $argv[2] ?? ''; //analysis type
$status      = $argv[3] ?? ''; //status value 
$output_file = $argv[4] ?? null; //output file path

//if job id is invalid - exit
if ($job_id <= 0 || $type === '' || $status === '') {
    fwrite(STDERR, "Usage: php update_status.php <job_id> <type> <status> [output_file]\n");
    exit(1);
}

// if an output file is provided - update status and output_file columns
// else update only the status column
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($output_file) {
        $stmt = $conn->prepare('
            UPDATE analysis
            SET status = ?, output_file = ?
            WHERE job_id = ? AND analysis_type = ?
        ');
        $stmt->execute([$status, $output_file, $job_id, $type]);
    } else {
        $stmt = $conn->prepare('
            UPDATE analysis
            SET status = ?
            WHERE job_id = ? AND analysis_type = ?
        ');
        $stmt->execute([$status, $job_id, $type]);
    }
} catch (PDOException $e) {
    fwrite(STDERR, "DB error: " . $e->getMessage() . "\n");
    exit(1);
}
?>
