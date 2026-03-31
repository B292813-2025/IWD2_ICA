<?php
session_start();
require_once 'login.php';

// Get job ID
$job_id = intval($_GET['job_id'] ?? 0);
if ($job_id <= 0) {
    header('Location: index.php');
    exit();
}

// Connect to mySQL
try {
    $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// Fetch job
$stmt = $conn->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: index.php');
    exit();
}

// Get statuses
$stmt = $conn->prepare('SELECT * FROM analysis WHERE job_id = ? ORDER BY analysis_id ASC');
$stmt->execute([$job_id]);
$analyses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a lookup: analysis_type => row
$analysis = [];
foreach ($analyses as $a) {
    $analysis[$a['analysis_type']] = $a;
}

// Fetch the sequences stored in the database
$stmt = $conn->prepare('SELECT * FROM sequences WHERE job_id = ? ORDER BY seq_id ASC');
$stmt->execute([$job_id]);
$sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get motif results
$stmt = $conn->prepare('
    SELECT mr.*, s.accession, s.species
    FROM motif_results mr
    JOIN sequences s ON mr.seq_id = s.seq_id
    WHERE mr.job_id = ?
    ORDER BY mr.seq_id ASC, mr.start_pos ASC
');
$stmt->execute([$job_id]);
$motifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Web path
function web_path($abs_path) {
    // Convert /localdisk/home/s2837201/public_html/ICA/... 
    // to the relative path from ICA/
    $pos = strpos($abs_path, '/ICA/');
    if ($pos !== false) {
        return substr($abs_path, $pos + 5); // strip everything up to and including /ICA/
    }
    return $abs_path;
}

$base_dir = '/home/s2837201/public_html/ICA/data/job_' . $job_id;
$base_url  = 'data/job_' . $job_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>JAHbio — Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style2.css">
    <style>
        .result-section {
            margin-top: 36px;
        }
        .result-img {
            max-width: 100%;
            border: 1px solid var(--grey-border);
            border-radius: 4px;
            margin-top: 12px;
        }
        .bio-note {
            background-color: var(--navy-light);
            border-left: 3px solid var(--navy);
            border-radius: 0 3px 3px 0;
            padding: 14px 20px;
            font-size: 0.88em;
            color: var(--grey-text);
            margin-top: 14px;
            line-height: 1.7;
        }
        .failed-note {
            background-color: var(--red-wash);
            border-left: 3px solid var(--red);
            border-radius: 0 3px 3px 0;
            padding: 14px 20px;
            font-size: 0.88em;
            color: var(--red-hover);
            margin-top: 14px;
        }
        .badge {
            display: inline-block;
            font-size: 0.72em;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }
        .badge-complete { background-color: #d4edda; color: #2a7d4f; }
        .badge-failed   { background-color: var(--red-wash); color: var(--red); }
        .badge-pending  { background-color: var(--navy-light); color: var(--navy); }
        .ext-links a {
            display: inline-block;
            margin-right: 12px;
            font-size: 0.85em;
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

    <h2>Results: <?php echo htmlspecialchars($job['protein']); ?> in <?php echo htmlspecialchars($job['taxon']); ?></h2>
    <p>
        Analysis run on <strong><?php echo $job['created_at']; ?></strong> -
        <strong><?php echo $job['n_returned']; ?></strong> sequences retrieved from NCBI.
    </p>

    <div class="result-section">
        <h3>Sequence Summary</h3>

        <?php if (empty($sequences)): ?>
            <div class="failed-note">No sequences were stored in the database for this job.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Accession</th>
                        <th>Species</th>
                        <th>Length (aa)</th>
                        <th>External Links</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sequences as $i => $seq): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($seq['accession']); ?></td>
                        <td><em><?php echo htmlspecialchars($seq['species']); ?></em></td>
                        <td><?php echo htmlspecialchars($seq['seq_length']); ?></td>
                        <td class="ext-links">
                            <a href="https://www.ncbi.nlm.nih.gov/protein/<?php echo urlencode($seq['accession']); ?>" target="_blank">NCBI</a>
                            <a href="https://www.uniprot.org/uniprot/?query=<?php echo urlencode($seq['accession']); ?>" target="_blank">UniProt</a>
                            <a href="https://www.ebi.ac.uk/interpro/search/sequence/?searchId=<?php echo urlencode($seq['accession']); ?>" target="_blank">InterPro</a>
                            <a href="https://alphafold.ebi.ac.uk/search/text/<?php echo urlencode($seq['accession']); ?>" target="_blank">AlphaFold</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="bio-note">
                The table above summarises all protein sequences retrieved from NCBI for
                <em><?php echo htmlspecialchars($job['protein']); ?></em> in
                <em><?php echo htmlspecialchars($job['taxon']); ?></em>.
                Sequence length variation across species can indicate the presence of lineage-specific
                insertions, deletions, or alternative isoforms. External links provide access to
                functional annotations (UniProt), domain predictions (InterPro), and predicted
                3D structures (AlphaFold).
            </div>
        <?php endif; ?>
    </div>

    <hr>

    <div class="result-section">
        <h3>
            Sequence Length Distribution
            <?php if (isset($analysis['histogram'])): ?>
                <span class="badge badge-<?php echo strtolower($analysis['histogram']['status']); ?>">
                    <?php echo $analysis['histogram']['status']; ?>
                </span>
            <?php endif; ?>
        </h3>

        <?php 
        $hist_png = $base_dir . '/histogram.png';
        if (file_exists($hist_png)): ?>
            <img src="<?php echo $base_url; ?>/histogram.png" alt="Sequence length histogram" class="result-img">
            <div class="bio-note">
                This histogram shows the distribution of sequence lengths across all retrieved
                <?php echo htmlspecialchars($job['protein']); ?> sequences in
                <?php echo htmlspecialchars($job['taxon']); ?>.
                A narrow distribution suggests high conservation of protein length across species,
                while a broad distribution may indicate the presence of isoforms, partial sequences,
                or serve as evidence for evolutionary variation in domain composition.
            </div>
        <?php else: ?>
            <div class="failed-note">Histogram could not be generated.</div>
        <?php endif; ?>
    </div>

    <hr>

    <?php if ($job['do_align']): ?>
    <div class="result-section">
        <h3>
            Conservation Plot
            <?php if (isset($analysis['conservation'])): ?>
                <span class="badge badge-<?php echo strtolower($analysis['conservation']['status']); ?>">
                    <?php echo $analysis['conservation']['status']; ?>
                </span>
            <?php endif; ?>
        </h3>

        <?php 
        $cons_png = $base_dir . '/conservation.1.png';
        if (file_exists($cons_png)): ?>
            <img src="<?php echo $base_url; ?>/conservation.1.png" alt="Conservation plot" class="result-img">
            <div class="bio-note">
                This conservation plot was generated by aligning all sequences using ClustalOmega
                and passing the alignment to Plotcon (EMBOSS). The y-axis shows the conservation
                score at each position in the alignment: higher peaks indicate positions that are
                conserved across species, which typically correspond to functionally or structurally
                important residues. Regions with low conservation may represent variable loops or
                lineage-specific adaptations.
            </div>
        <?php else: ?>
            <div class="failed-note">Conservation plot could not be/is currently still being generated. The alignment may have failed - check that at least 2 sequences were retrieved.</div>
        <?php endif; ?>

        <?php
        // Offer the aligned FASTA for download
        $aligned_fasta = $base_dir . '/aligned.fasta';
        if (file_exists($aligned_fasta)): ?>
            <p style="margin-top:12px;">
                <a href="<?php echo $base_url; ?>/aligned.fasta" download>Download aligned FASTA</a>
            </p>
        <?php endif; ?>
    </div>
    <hr>
    <?php endif; ?>

    <?php if ($job['do_motif']): ?>
    <div class="result-section">
        <h3>
            PROSITE Motif Scan
            <?php if (isset($analysis['motif'])): ?>
                <span class="badge badge-<?php echo strtolower($analysis['motif']['status']); ?>">
                    <?php echo $analysis['motif']['status']; ?>
                </span>
            <?php endif; ?>
        </h3>

        <?php if (!empty($motifs)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Accession</th>
                        <th>Species</th>
                        <th>Motif</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($motifs as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['accession']); ?></td>
                        <td><em><?php echo htmlspecialchars($m['species']); ?></em></td>
                        <td><?php echo htmlspecialchars($m['motif_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['start_pos']); ?></td>
                        <td><?php echo htmlspecialchars($m['end_pos']); ?></td>
                        <td><?php echo htmlspecialchars($m['score']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="bio-note">
                PROSITE motif scanning using Patmatmotifs identifies known functional sequence
                patterns within each retrieved protein. Motifs conserved across multiple species
                are particularly significant as they are likely essential for protein function.
                The start and end positions indicate where each motif occurs within the protein
                sequence, which can be cross-referenced with structural data to identify active
                sites or binding domains.
            </div>
        <?php else: ?>
            <?php 
            $motif_file = $base_dir . '/motifs.txt';
            if (file_exists($motif_file)): ?>
                <div class="bio-note">No PROSITE motifs were detected in the retrieved sequences, or motif results have not yet been parsed into the database.</div>
                <p><a href="<?php echo $base_url; ?>/motifs.txt" target="_blank">View raw Patmatmotifs output</a></p>
            <?php else: ?>
                <div class="failed-note">Motif scan did not complete successfully or is still being generated.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <hr>
    <?php endif; ?>

    <?php if ($job['do_blast']): ?>
    <div class="result-section">
        <h3>
            BLAST Similarity Search
            <?php if (isset($analysis['blast'])): ?>
                <span class="badge badge-<?php echo strtolower($analysis['blast']['status']); ?>">
                    <?php echo $analysis['blast']['status']; ?>
                </span>
            <?php endif; ?>
        </h3>

        <?php 
        $blast_file = $base_dir . '/blast.txt';
        if (file_exists($blast_file)): 
            // Parse BLAST output — skip comment lines starting with #
            $blast_lines = file($blast_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $blast_hits = [];
            foreach ($blast_lines as $line) {
                if ($line[0] === '#') continue;
                $cols = explode("\t", $line);
                if (count($cols) >= 11) {
                    $blast_hits[] = $cols;
                }
            }
        ?>
            <?php if (!empty($blast_hits)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Query</th>
                        <th>Subject</th>
                        <th>% Identity</th>
                        <th>Alignment Length</th>
                        <th>E-value</th>
                        <th>Bit Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($blast_hits, 0, 20) as $hit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($hit[0]); ?></td>
                        <td><?php echo htmlspecialchars($hit[1]); ?></td>
                        <td><?php echo htmlspecialchars($hit[2]); ?>%</td>
                        <td><?php echo htmlspecialchars($hit[3]); ?></td>
                        <td><?php echo htmlspecialchars($hit[10]); ?></td>
                        <td><?php echo htmlspecialchars($hit[11] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="bio-note">
                BLAST results show pairwise sequence similarity between all retrieved
                <?php echo htmlspecialchars($job['protein']); ?> sequences.
                The E-value indicates the probability that a match of this quality would occur
                by chance. Lower E-values (e.g. &lt;1e-10) indicate highly significant similarity.
                High percentage identity across species confirms orthologous relationships,
                while lower identity scores may indicate paralogues or more distantly related
                family members.
            </div>
            <p style="margin-top:12px;">
                <a href="<?php echo $base_url; ?>/blast.txt" target="_blank">Download full BLAST output</a>
            </p>
            <?php else: ?>
                <div class="bio-note">BLAST completed but no significant hits were found, or the output could not be parsed.</div>
            <?php endif; ?>

        <?php else: ?>
            <div class="failed-note">BLAST output file not found. The analysis may still be running.</div>
        <?php endif; ?>
    </div>
    <hr>
    <?php endif; ?>

    <?php if ($job['do_pymol']): ?>
    <div class="result-section">
        <h3>
            3D Structure Visualisation
            <?php if (isset($analysis['pymol'])): ?>
                <span class="badge badge-<?php echo strtolower($analysis['pymol']['status']); ?>">
                    <?php echo $analysis['pymol']['status']; ?>
                </span>
            <?php endif; ?>
        </h3>

        <?php $pdb_file = $base_dir . '/structure.pdb'; ?>
        <?php if (file_exists($pdb_file) && filesize($pdb_file) > 1000): ?>

            <div id="mol-viewer" style="width:100%; height:500px; border:1px solid var(--grey-border); border-radius:4px; margin-top:12px; background:#1a1a2e; position:relative;"></div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
            <script src="https://3dmol.org/build/3Dmol-min.js"></script>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var viewer = $3Dmol.createViewer("mol-viewer", { backgroundColor: "#1a1a2e" });
                jQuery.ajax({
                    url: "/~s2837201/ICA/data/job_<?php echo $job_id; ?>/structure.pdb",
                    success: function(pdbData) {
                        viewer.addModel(pdbData, "pdb");
                        viewer.setStyle({}, { cartoon: { color: 'spectrum' } });
                        viewer.zoomTo();
                        viewer.render();
                    },
                    error: function() {
                        document.getElementById("mol-viewer").innerHTML =
                            "<p style='color:#fff;padding:20px;'>Could not load structure file.</p>";
                    }
                });
            });
            </script>

            <div class="bio-note" style="margin-top:14px;">
                The 3D structure above was predicted using
                <a href="https://esmatlas.com" target="_blank">ESMFold</a> (Meta AI) directly
                from the first retrieved protein sequence. You can <strong>rotate</strong> by dragging and
                <strong>zoom</strong> with the scroll wheel. ESMFold predictions are computationally generated;
                for experimentally determined structures consult the
                <a href="https://www.rcsb.org" target="_blank">RCSB Protein Data Bank</a>.
            </div>

        <?php else: ?>
            <div class="failed-note">
                3D structure prediction failed or is still running. ESMFold requires a valid
                protein sequence and may take up to 30 seconds. You can view predicted structures
                manually at <a href="https://esmatlas.com" target="_blank">esmatlas.com</a>.
            </div>
        <?php endif; ?>
    </div>
    <hr>
    <?php endif; ?>

    <div class="result-section">
        <h3>Downloads</h3>
        <p>Raw output files for this analysis:</p>
        <ul style="margin-left:24px; line-height:2.2;">
            <?php if (file_exists($base_dir . '/sequences.fasta')): ?>
                <li><a href="<?php echo $base_url; ?>/sequences.fasta" download>sequences.fasta</a> - All retrieved sequences</li>
            <?php endif; ?>
            <?php if (file_exists($base_dir . '/aligned.fasta')): ?>
                <li><a href="<?php echo $base_url; ?>/aligned.fasta" download>aligned.fasta</a> - ClustalOmega alignment</li>
            <?php endif; ?>
            <?php if (file_exists($base_dir . '/motifs.txt')): ?>
                <li><a href="<?php echo $base_url; ?>/motifs.txt" download>motifs.txt</a> - Patmatmotifs output</li>
            <?php endif; ?>
            <?php if (file_exists($base_dir . '/blast.txt')): ?>
                <li><a href="<?php echo $base_url; ?>/blast.txt" download>blast.txt</a> - BLAST output</li>
            <?php endif; ?>
            <?php if (file_exists($base_dir . '/log.txt')): ?>
                <li><a href="<?php echo $base_url; ?>/log.txt" target="_blank">log.txt</a> - Analysis log</li>
            <?php endif; ?>
            <?php if (file_exists($base_dir . '/structure.pdb') && filesize($base_dir . '/structure.pdb') > 1000): ?>
                <li><a href="<?php echo $base_url; ?>/structure.pdb" download>structure.pdb</a> - ESMFold predicted 3D structure (can be viewed in tools such as PyMol for further analysis)</li>
            <?php endif; ?>
        </ul>
    </div>

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
