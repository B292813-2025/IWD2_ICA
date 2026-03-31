<?php
// Reads motifs.json written by parse_motifs.py and inserts each motif hit into the DB via PDO.
// Called from run_analysis.sh as php import_motifs.php <job_id> <base_dir>
require_once __DIR__ . '/login.php';

// Arguments from shell script
$job_id   = intval($argv[1] ?? 0);
$base_dir = $argv[2] ?? '';

if ($job_id <= 0 || $base_dir === '') {
    fwrite(STDERR, "Usage: php import_motifs.php <job_id> <base_dir>\n");
    exit(1);
}

$json_file = $base_dir . '/motifs.json';
if (!file_exists($json_file)) {
    fwrite(STDERR, "motifs.json not found at $json_file\n");
    exit(1);
}

// Read JSON
$motifs = json_decode(file_get_contents($json_file), true);
if (!is_array($motifs)) {
    fwrite(STDERR, "Failed to parse motifs.json\n");
    exit(1);
}

if (empty($motifs)) {
    echo "No motif hits to insert.\n";
    exit(0);
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

// Build accession -> seq_id lookup
$stmt      = $conn->prepare('SELECT seq_id, accession FROM sequences WHERE job_id = ?');
$stmt->execute([$job_id]);
$seq_lookup = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $seq_lookup[$row['accession']] = $row['seq_id'];
}

// Insert motif hits
$stmt = $conn->prepare('
    INSERT INTO motif_results (job_id, seq_id, motif_name, start_pos, end_pos, score)
    VALUES (?, ?, ?, ?, ?, ?)
');

$count = 0;
foreach ($motifs as $motif) {
    $seq_id = $seq_lookup[$motif['accession']] ?? null;
    if ($seq_id === null) {
        fwrite(STDERR, "Warning: accession {$motif['accession']} not found in sequences table\n");
        continue;
    }
    $stmt->execute([
        $job_id,
        $seq_id,
        $motif['motif_name'],
        $motif['start_pos'],
        $motif['end_pos'],
        $motif['score']
    ]);
    $count++;
}

echo "Inserted $count motif hits into DB.\n";
?>
