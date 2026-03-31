<?php
// Reads sequences.json written by parse_sequences.py + inserts each sequence into the DB via PDO.
// Called from run_analysis.sh as: php import_sequences.php <job_id> <base_dir>
// Very similar code to import_motifs.php
require_once __DIR__ . '/login.php';

// Arguments from shell script
$job_id   = intval($argv[1] ?? 0);
$base_dir = $argv[2] ?? '';

if ($job_id <= 0 || $base_dir === '') {
    fwrite(STDERR, "Usage: php import_sequences.php <job_id> <base_dir>\n");
    exit(1);
}

$json_file = $base_dir . '/sequences.json';
if (!file_exists($json_file)) {
    fwrite(STDERR, "sequences.json not found at $json_file\n");
    exit(1);
}

// Read JSON
$sequences = json_decode(file_get_contents($json_file), true);
if (!is_array($sequences)) {
    fwrite(STDERR, "Failed to parse sequences.json\n");
    exit(1);
}

// Connect via PDO
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Insert the sequences
$stmt = $conn->prepare('
    INSERT INTO sequences (job_id, accession, description, species, seq_length)
    VALUES (?, ?, ?, ?, ?)
');

$count = 0;
foreach ($sequences as $seq) {
    $stmt->execute([
        $job_id,
        $seq['accession'],
        $seq['description'],
        $seq['species'],
        $seq['seq_length']
    ]);
    $count++;
}

echo "Inserted $count sequences into DB.\n";
?>
