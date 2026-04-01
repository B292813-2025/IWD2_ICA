<?php
require_once 'login.php';

// lets do some json - tells the browser
header('Content-Type: application/json');

// gets job ID as int default to 0 if not detected
$job_id = intval($_GET['job_id'] ?? 0);
// if it is set to 0 (or somehow -ve) - return an error message and stop the script
if ($job_id <= 0) {
    echo json_encode(['error' => 'Invalid job ID']);
    exit();
}

//connect to mySQL
//create a new PDO database connection
//if the database connection fails, return an error message
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

// prepare a SQL query to retrieve analysis steps for the given job ID
// ordered by analysis_id so steps appear in sequence
$stmt = $conn->prepare('
    SELECT analysis_type, status, output_file
    FROM analysis
    WHERE job_id = ?
    ORDER BY analysis_id ASC
');
//safelt insert the job ID
$stmt->execute([$job_id]);

//fetch all matching rows as an associative array
$steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

//returns the results (json format)
echo json_encode(['steps' => $steps]);
?>
