<?php
session_start();
require_once 'login.php';

// Connect to mySQL
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Get the session ID for the current session
$session_id = session_id();

// Query mySQL
$stmt = $conn->prepare('
    SELECT job_id, protein, taxon, max_seqs, n_returned, created_at,
           do_align, do_motif, do_blast, do_pymol
    FROM jobs
    WHERE session_id = ?
    ORDER BY created_at DESC
');
// Run the query with the current session ID
$stmt->execute([$session_id]);

// Gets all the associated arrays
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialises empty status array - if empty - fill with the associated info
$statuses = [];
if (!empty($jobs)) {
    $job_ids      = array_column($jobs, 'job_id');
    $placeholders = implode(',', array_fill(0, count($job_ids), '?'));
    $stmt         = $conn->prepare("SELECT job_id, analysis_type, status FROM analysis WHERE job_id IN ($placeholders)");
    $stmt->execute($job_ids);
    // Creates a nested array e.g. $statuses[5]['alignment'] = 'complete';
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $statuses[$row['job_id']][$row['analysis_type']] = $row['status'];
    }
}
// Style below heavily assisted by Claude
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results — JAHbio</title>
    <link rel="stylesheet" href="style2.css">
    <style>
        .job-card {
            border: 1px solid var(--grey-border);
            border-radius: 4px;
            padding: 20px 24px;
            margin-bottom: 20px;
            transition: box-shadow 0.18s;
        }
        .job-card:hover {
            box-shadow: var(--shadow-md);
        }
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }
        .job-title {
            font-size: 1.1em;
            font-weight: 700;
            color: var(--navy);
        }
        .job-meta {
            font-size: 0.85em;
            color: var(--grey-light);
            margin-top: 4px;
        }
        .job-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .badge-small {
            font-size: 0.72em;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 3px;
        }
        .badge-complete { background: #eef7ee; color: #2d7a2d; }
        .badge-failed   { background: var(--red-wash); color: var(--red); }
        .badge-pending  { background: var(--navy-light); color: var(--navy); }
        .badge-running  { background: #fff8e1; color: #b36d00; }
        .view-btn {
            display: inline-block;
            background: var(--red);
            color: #fff;
            padding: 8px 20px;
            border-radius: 3px;
            font-size: 0.85em;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            border-bottom: none;
            transition: background 0.18s;
            white-space: nowrap;
        }
        .view-btn:hover {
            background: var(--red-hover);
            color: #fff;
            border-bottom: none;
        }
    </style>
</head>
<body>

<!-- The usual setup -->

<div class="header">
    <div class="container">
        <div class="header-inner">
            <div>
                <h1><span>JAH</span>bio</h1>
                <p>Just Another Homology Tool. Except better&nbsp;;)</p>
                </div>
                <img src="images/jahbio.png" alt="Web logo" style="height:100px; opacity:0.9;">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="menu">
    <div class="container">
        <a href="index.php">Home</a>
        <a href="search.php">New Search</a>
        <a href="example.php">Example Dataset</a>
        <a href="history.php">My Results</a>
        <a href="about.php">About</a>
        <a href="help.php">Help</a>
        <a href="credits.php">Credits</a>
        <a href="feedback.php">Feedback</a>
    </div>
</div>

<div class="container">
<div class="content">

    <h2>My Results</h2>

    <!-- If there are no jobs display this message and offer to take the user back -->
    <?php if (empty($jobs)): ?>
        <div class="infobox">
            No analyses found for this session. <a href="index.php">Run a search</a> to get started.
        </div>

    <!-- If there are jobs -->
    <?php else: ?>
        <!-- Shows how many have been run -->
        <p><?php echo count($jobs); ?> analysis run<?php echo count($jobs) !== 1 ? 's' : ''; ?> found for this session.</p>

        <!-- Iterate through every job -->
        <?php foreach ($jobs as $job): ?>

        <!-- Get the statuses -->
        <?php $s = $statuses[$job['job_id']] ?? []; ?>

        <!-- Every job shown as a "job card" -->
        <div class="job-card">
            <div class="job-header">
                <div>
                    <!-- Title of job -->
                    <div class="job-title">
                        <?php echo htmlspecialchars(ucfirst($job['protein'])); ?>
                        in <em><?php echo htmlspecialchars(ucfirst($job['taxon'])); ?></em>
                    </div>

                    <div class="job-meta">
                        <!-- Formatted date and time -->
                        Job #<?php echo $job['job_id']; ?>
                        &nbsp;&middot;&nbsp;
                        <?php echo date('d M Y, H:i', strtotime($job['created_at'])); ?>
                        &nbsp;&middot;&nbsp;
                        <?php echo $job['n_returned'] ?? '0'; ?> sequences retrieved
                    </div>

                    <!-- Shows badges for each analysis -->
                    <div class="job-badges">
                        <?php
                        $steps = ['fetch' => 'Fetch', 'histogram' => 'Histogram'];
                        if ($job['do_align']) { $steps['alignment'] = 'Alignment'; $steps['conservation'] = 'Conservation'; }
                        if ($job['do_motif']) $steps['motif'] = 'Motifs';
                        if ($job['do_blast']) $steps['blast'] = 'BLAST';
                        if ($job['do_pymol']) $steps['pymol'] = '3D Structure';
                        foreach ($steps as $key => $label):
                            $status = strtolower($s[$key] ?? 'pending'); // pending by default
                        ?>
                        <span class="badge-small badge-<?php echo $status; ?>">
                            <?php echo $label; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Button to view results -->
                <a href="results.php?job_id=<?php echo $job['job_id']; ?>" class="view-btn">
                    View Results &rarr;
                </a>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>
</div>

<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool &mdash; The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a> &mdash; <a href="https://github.com/B292813-2025/IWD2_ICA" target="_blank">GitHub</a></p>
    </div>
</div>

</body>
</html>
