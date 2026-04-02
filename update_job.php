<?php
// Updates the n_returned count for a job in the DB via PDO.
// Called from run_analysis.sh as:
// php update_job.php <job_id> <n_returned>
require_once __DIR__ . '/login.php';

// Read command-line arguments:
// $argv[1] = job_id
// $argv[2] = number of returned items - default to 0
$job_id     = intval($argv[1] ?? 0);
$n_returned = intval($argv[2] ?? 0);

// if job is not inserted (or somehow a -ve number)
// output usage instructions to STDERR 
if ($job_id <= 0) {
    fwrite(STDERR, "Usage: php update_job.php <job_id> <n_returned>\n");
    exit(1);
}

// create DSN for MySQL 
// make new PDO connection using credentials
// prep SQL statement to update the job record
// using placeholders prevents SQL injection (advised by AI tools to implement throughout)
// insert statements + execute
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare('UPDATE jobs SET n_returned = ? WHERE job_id = ?');
    $stmt->execute([$n_returned, $job_id]);
} catch (PDOException $e) {
    fwrite(STDERR, "DB error: " . $e->getMessage() . "\n");
    exit(1);
}
// PDOException handles DB errors
?>
