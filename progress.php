<?php
session_start();
require_once 'login.php';

// get job id
$job_id = intval($_GET['job_id'] ?? 0);
if ($job_id <= 0) {
    header('Location: index.php');
    exit();
}

// connecting to mySQL
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// get job details
$stmt = $conn->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: index.php');
    exit();
}

// which analysis to perform
$stmt = $conn->prepare('SELECT * FROM analysis WHERE job_id = ? ORDER BY analysis_id ASC');
$stmt->execute([$job_id]);
$steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// labels for each analysis type
$labels = [
    'fetch'        => 'Fetching sequences from NCBI',
    'histogram'    => 'Generating sequence length histogram',
    'alignment'    => 'Running multiple sequence alignment (ClustalOmega)',
    'conservation' => 'Plotting conservation (Plotcon)',
    'motif'        => 'Scanning for PROSITE motifs (Patmatmotifs)',
    'blast'        => 'Running BLAST similarity search',
    'pymol'        => 'Generating 3D structure visualisation (Prediction)',
];

// thank you to claude for these icons 
$icons = [
    'Pending'  => '○',
    'Running'  => '◉',
    'Complete' => '✓',
    'Failed'   => '✗',
];

$colours = [
    'Pending'  => '#8898aa',
    'Running'  => '#c8102e',
    'Complete' => '#2a7d4f',
    'Failed'   => '#c8102e',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>JAHbio — Analysis Running</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style2.css">
    <style>
        .step {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid var(--grey-border);
            font-size: 0.95em;
        }
        .step:last-child {
            border-bottom: none;
        }
        .step-icon {
            font-size: 1.3em;
            width: 28px;
            text-align: center;
            flex-shrink: 0;
        }
        .step-label {
            flex: 1;
            color: var(--grey-text);
        }
        .step-status {
            font-size: 0.78em;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .progress-bar-outer {
            background-color: var(--grey-border);
            border-radius: 4px;
            height: 10px;
            margin: 24px 0 8px;
            overflow: hidden;
        }
        .progress-bar-inner {
            height: 100%;
            background-color: var(--red);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .progress-label {
            font-size: 0.82em;
            color: var(--grey-text);
            margin-bottom: 24px;
        }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid var(--grey-border);
            border-top-color: var(--red);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .failed-box {
            background-color: var(--red-wash);
            border-left: 3px solid var(--red);
            padding: 13px 20px;
            border-radius: 0 3px 3px 0;
            color: var(--red-hover);
            font-size: 0.92em;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="container header-inner">
        <div>
            <h1><span>JAH</span>bio</h1>
            <p>Just Another Homology Tool. Except better ;)</p>
        </div>
        <img src="images/jahbio.png" alt="JAHbio logo" style="height:100px; opacity:0.9;">
    </div>
</div>

<div class="menu">
    <div class="container">
        <a href="index.php">Home</a>
        <a href="index.php">New Search</a>
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

    <h2>Analysis in Progress</h2>
    <p>
        Running analyses for <strong><?php echo htmlspecialchars($job['protein']); ?></strong>
        in <strong><?php echo htmlspecialchars($job['taxon']); ?></strong>.
        Please do not close this page.
    </p>

    <!-- Progress bar -->
    <div class="progress-bar-outer">
        <div class="progress-bar-inner" id="progress-bar" style="width:0%"></div>
    </div>
    <p class="progress-label" id="progress-label">
        <span class="spinner"></span> Starting up...
    </p>

    <!-- Step list -->
    <div id="steps-list">
    <?php foreach ($steps as $step): 
        $status = $step['status'];
        $type   = $step['analysis_type'];
        $label  = $labels[$type] ?? ucfirst($type);
        $icon   = $icons[$status]   ?? '○';
        $colour = $colours[$status] ?? '#8898aa';
    ?>
        <div class="step" id="step-<?php echo htmlspecialchars($type); ?>">
            <div class="step-icon" style="color:<?php echo $colour; ?>">
                <?php echo $icon; ?>
            </div>
            <div class="step-label"><?php echo htmlspecialchars($label); ?></div>
            <div class="step-status" style="color:<?php echo $colour; ?>">
                <?php echo htmlspecialchars($status); ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div id="failed-message" style="display:none;" class="failed-box">
        One or more analyses failed. You can still
        <a href="results.php?job_id=<?php echo $job_id; ?>">view partial results</a>
        or <a href="index.php">start a new search</a>.
    </div>

</div>
</div>

<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a></p>
    </div>
</div>

<!-- JS — polls poll.php every 3 seconds - thank you to Claude for help here -->
<script>
const jobId    = <?php echo $job_id; ?>;
const labels   = <?php echo json_encode($labels); ?>;
const totalSteps = <?php echo count($steps); ?>;

// icons and colours mirrored from PHP above
const icons   = { Pending: '○', Running: '◉', Complete: '✓', Failed: '✗' };
const colours = { Pending: '#8898aa', Running: '#c8102e', Complete: '#2a7d4f', Failed: '#c8102e' };

function poll() {
    fetch('poll.php?job_id=' + jobId)
        .then(r => r.json())
        .then(data => {
            let complete = 0;
            let anyFailed = false;
            let allDone = true;

            data.steps.forEach(step => {
                const el = document.getElementById('step-' + step.analysis_type);
                if (!el) return;

                const icon   = icons[step.status]   || '○';
                const colour = colours[step.status] || '#8898aa';

                el.querySelector('.step-icon').textContent  = icon;
                el.querySelector('.step-icon').style.color  = colour;
                el.querySelector('.step-status').textContent = step.status;
                el.querySelector('.step-status').style.color = colour;

                if (step.status === 'Complete') complete++;
                if (step.status === 'Failed')   anyFailed = true;
                if (step.status === 'Pending' || step.status === 'Running') allDone = false;
            });

            // update progress bar
            const pct = Math.round((complete / totalSteps) * 100);
            document.getElementById('progress-bar').style.width = pct + '%';
            document.getElementById('progress-label').innerHTML =
                '<span class="spinner"></span> ' + complete + ' of ' + totalSteps + ' steps complete (' + pct + '%)';

            if (anyFailed) {
                document.getElementById('failed-message').style.display = 'block';
            }

            if (allDone) {
                // all steps finished — redirect to results
                document.getElementById('progress-label').innerHTML = '✓ Complete! Redirecting to results...';
                setTimeout(() => {
                    window.location.href = 'results.php?job_id=' + jobId;
                }, 1500);
            } else {
                // poll again in 3 seconds
                setTimeout(poll, 3000);
            }
        })
        .catch(() => {
            // network error — try again in 5 seconds
            setTimeout(poll, 5000);
        });
}

// begin polling after 1 second
setTimeout(poll, 1000);
</script>

</body>
</html>
