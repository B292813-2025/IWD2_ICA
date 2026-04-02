<?php
// ============================================================
// update_status.php — JAHbio
// Updates the status of an analysis step in the DB via PDO.
// Called from run_analysis.sh as:
//   php update_status.php <job_id> <type> <status> [output_file]
// ============================================================
require_once __DIR__ . '/login.php';

$job_id      = intval($argv[1] ?? 0);
$type        = $argv[2] ?? '';
$status      = $argv[3] ?? '';
$output_file = $argv[4] ?? null;

if ($job_id <= 0 || $type === '' || $status === '') {
    fwrite(STDERR, "Usage: php update_status.php <job_id> <type> <status> [output_file]\n");
    exit(1);
}

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
