<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example Dataset — JAHbio</title>
    <link rel="stylesheet" href="style2.css">
    <style>
        .result-section { margin-bottom: 36px; }
        .result-img { max-width: 100%; border: 1px solid var(--grey-border); border-radius: 4px; margin-top: 12px; }
        .bio-note { background: var(--navy-light); border-left: 3px solid var(--navy); padding: 14px 18px; font-size: 0.92em; color: var(--grey-text); margin-top: 14px; border-radius: 0 3px 3px 0; }
        .badge { display: inline-block; font-size: 0.7em; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 3px 10px; border-radius: 3px; margin-left: 10px; vertical-align: middle; }
        .badge-complete { background: #eef7ee; color: #2d7a2d; }
        .ext-links a { font-size: 0.8em; margin-right: 6px; }
        @media (max-width: 600px) { table { font-size: 0.75em; display: block; overflow-x: auto; } td, th { padding: 8px 10px; white-space: nowrap; } }
    </style>
</head>
<body>

<div class="header">
    <div class="container header-inner">
        <div>
            <h1><span>JAH</span>bio</h1>
            <p>Just Another Homology Tool. Except better&nbsp;;)</p>
        </div>
        <img src="images/jahbio.png" alt="Web logo" style="height:100px; opacity:0.9;">
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

    <h2>Example: Glucose-6-phosphatase in Aves</h2>
    <p>
        This page demonstrates all JAHbio features using a pre-processed dataset of
        <strong>50 glucose-6-phosphatase sequences</strong> retrieved from <strong>Aves</strong> (birds).
        All analyses have been run in advance. Use this page to explore what JAHbio produces
        before running your own search.
    </p>

    <hr>

    <!-- Sequence Summary -->
    <div class="result-section">
        <h3>Sequence Summary <span class="badge badge-complete">Complete</span></h3>
        <table>
            <tr><th>#</th><th>Accession</th><th>Species</th><th>Length (aa)</th><th>External Links</th></tr>
            <?php
            $seqs = [
                ['KAN1727817.1','Threskiornis aethiopicus',358],
                ['KAJ7421106.1','Willisornis vidua',358],
                ['KAJ7396366.1','Pitangus sulphuratus',358],
                ['KAI6072612.1','Aix galericulata',358],
                ['KAI1230272.1','Lamprotornis superbus',370],
                ['XP_040473657.1','Falco naumanni',358],
                ['XP_040392851.1','Cygnus olor',358],
                ['XP_039553177.1','Passer montanus',358],
                ['XP_010411101.1','Corvus cornix cornix',358],
                ['XP_027601652.2','Pipra filicauda',358],
                ['XP_038019160.1','Motacilla alba alba',358],
                ['XP_037266979.1','Falco rusticolus',358],
                ['KAF4787459.1','Turdus rufiventris',358],
                ['XP_015506695.1','Parus major',358],
                ['XP_030364364.1','Strigops habroptila',358],
                ['XP_032566992.1','Chiroxiphia lanceolata',358],
                ['XP_015741500.1','Coturnix japonica',432],
                ['KAF1671963.1','Pygoscelis papua',358],
                ['KAF1663616.1','Aptenodytes patagonicus',358],
                ['KAF1642821.1','Eudyptes chrysocome',358],
                ['KAF1627266.1','Eudyptes filholi',358],
                ['KAF1604860.1','Eudyptes chrysolophus',358],
                ['KAF1584371.1','Eudyptes moseleyi',358],
                ['KAF1578265.1','Eudyptes robustus',358],
                ['KAF1561075.1','Eudyptes pachyrhynchus',358],
                ['KAF1548696.1','Eudyptula albosignata',358],
                ['KAF1546409.1','Eudyptula minor',358],
                ['KAF1524284.1','Eudyptes sclateri',358],
                ['KAF1488921.1','Eudyptula minor novaehollandiae',358],
                ['KAF1487788.1','Pygoscelis antarcticus',358],
                ['KAF1473286.1','Megadyptes antipodes antipodes',358],
                ['KAF1471003.1','Megadyptes antipodes antipodes',358],
                ['KAF1464176.1','Megadyptes antipodes antipodes',358],
                ['KAF1464082.1','Megadyptes antipodes antipodes',358],
                ['KAF1459431.1','Spheniscus mendiculus',358],
                ['KAF1440782.1','Spheniscus demersus',358],
                ['KAF1432837.1','Spheniscus magellanicus',358],
                ['KAF1404242.1','Spheniscus humboldti',358],
                ['XP_031990407.1','Corvus moneduloides',358],
                ['XP_019478895.1','Meleagris gallopavo',220],
                ['XP_031456423.1','Phasianus colchicus',432],
                ['XP_005429668.1','Geospiza fortis',413],
                ['XP_030822190.1','Camarhynchus parvulus',359],
                ['XP_030321679.1','Calypte anna',358],
                ['XP_008503927.1','Calypte anna',358],
                ['XP_008922173.2','Manacus vitellinus',358],
                ['XP_027751872.1','Empidonax traillii',358],
                ['XP_027520533.1','Corapipo altera',358],
                ['XP_027558020.1','Neopelma chrysocephalum',358],
                ['XP_026720573.1','Athene cunicularia',417],
            ];
            foreach ($seqs as $i => $seq):
                $acc     = htmlspecialchars($seq[0]);
                $species = htmlspecialchars($seq[1]);
                $len     = $seq[2];
            ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo $acc; ?></td>
                <td><em><?php echo $species; ?></em></td>
                <td><?php echo $len; ?></td>
                <td class="ext-links">
                    <a href="https://www.ncbi.nlm.nih.gov/protein/<?php echo $acc; ?>" target="_blank">NCBI</a>
                    <a href="https://www.uniprot.org/uniprot/<?php echo $acc; ?>" target="_blank">UniProt</a>
                    <a href="https://www.ebi.ac.uk/interpro/search/sequence/?q=<?php echo $acc; ?>" target="_blank">InterPro</a>
                    <a href="https://alphafold.ebi.ac.uk/entry/<?php echo $acc; ?>" target="_blank">AlphaFold</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="bio-note">
            The table above summarises all 50 protein sequences retrieved from NCBI for
            glucose-6-phosphatase in Aves. The variation in sequence length across species
            may indicate the presence of lineage-specific isoforms or alternative splice variants.
        </div>
    </div>

    <hr>

    <!-- Histogram -->
    <div class="result-section">
        <h3>Sequence Length Distribution <span class="badge badge-complete">Complete</span></h3>
        <img src="data/job_1/histogram.png" alt="Sequence length histogram" class="result-img">
        <div class="bio-note">
            This histogram shows the distribution of sequence lengths across all 50 retrieved
            glucose-6-phosphatase sequences. The dominant peak around 355–360 aa corresponds
            to the canonical full-length G6Pase catalytic subunit. Shorter sequences may
            represent partial or alternatively spliced isoforms, while longer sequences may
            contain N- or C-terminal extensions.
        </div>
    </div>

    <hr>

    <!-- Conservation Plot -->
    <div class="result-section">
        <h3>Conservation Plot <span class="badge badge-complete">Complete</span></h3>
        <img src="data/job_1/conservation.1.png" alt="Conservation plot" class="result-img">
        <div class="bio-note">
            This conservation plot was generated by aligning all sequences using ClustalOmega
            and passing the alignment through Plotcon. Higher peaks indicate positions that are
            conserved across species, corresponding to functionally or structurally important
            residues. Regions of low conservation may represent lineage-specific loops or
            variable surface-exposed domains.
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_1/aligned.fasta" download>Download aligned FASTA</a>
        </p>
    </div>

    <hr>

    <!-- motifs -->
    <div class="result-section">
        <h3>PROSITE Motif Scan <span class="badge badge-complete">Complete</span></h3>
        <div class="infobox">
            No PROSITE motifs were detected in the retrieved sequences. This is a valid result,
            not all proteins contain PROSITE-catalogued motifs, particularly if their functional
            signatures are not yet represented in the PROSITE database.
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_1/motifs.txt" target="_blank">View raw Patmatmotifs output</a>
        </p>
    </div>

    <hr>

    <!-- BLAST -->
    <div class="result-section">
        <h3>BLAST Similarity Search <span class="badge badge-complete">Complete</span></h3>
        <?php
        $blast_file = '/home/s2837201/public_html/ICA/data/job_1/blast.txt';
        $blast_lines = file($blast_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = [];
        foreach ($blast_lines as $line) {
            if (strpos($line, '#') === 0) continue;
            $cols = preg_split('/\t/', $line);
            if (count($cols) >= 12) $rows[] = $cols;
            if (count($rows) >= 20) break;
        }
        ?>
        <?php if (!empty($rows)): ?>
        <table>
            <tr><th>Query</th><th>Subject</th><th>% Identity</th><th>Alignment Length</th><th>E-value</th><th>Bit Score</th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r[0]); ?></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
                <td><?php echo number_format((float)$r[2], 2); ?>%</td>
                <td><?php echo htmlspecialchars($r[3]); ?></td>
                <td><?php echo htmlspecialchars($r[10]); ?></td>
                <td><?php echo htmlspecialchars($r[11]); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <div class="bio-note">
            BLAST results show pairwise sequence similarity between all retrieved sequences.
            High percentage identity across species confirms orthologous relationships, while
            lower identity scores may indicate paralogues or more distantly related family members.
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_1/blast.txt" download>Download full BLAST output</a>
        </p>
    </div>

    <hr>

    <!-- 3D prediction -->
    <div class="result-section">
        <h3>3D Structure Visualisation <span class="badge badge-complete">Complete</span></h3>
        <div id="mol-viewer" style="width:100%; height:500px; border:1px solid var(--grey-border); border-radius:4px; margin-top:12px; background:#1a1a2e; position:relative;"></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://3dmol.org/build/3Dmol-min.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var viewer = $3Dmol.createViewer("mol-viewer", { backgroundColor: "#1a1a2e" });
            jQuery.ajax({
                url: "/~s2837201/ICA/data/job_1/structure.pdb",
                success: function(pdbData) {
                    viewer.addModel(pdbData, "pdb");
                    viewer.setStyle({}, { cartoon: { colorscheme: "ssJmol" } });
                    viewer.zoomTo();
                    viewer.render();
                }
            });
        });
        </script>
        <div class="bio-note">
            The 3D structure above was predicted using ESMFold (Meta AI) from the first retrieved
            sequence. Secondary structures are coloured: red for alpha helices, yellow for beta
            sheets, and green for loops. You can rotate by dragging, zoom with the scroll wheel,
            and pan by right-click dragging.
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_1/structure.pdb" download>Download PDB file</a>
        </p>
    </div>

    <hr>

    <!-- Downloadable files -->
    <div class="result-section">
        <h3>Downloads</h3>
        <p>Raw output files for this example dataset:</p>
        <ul style="margin-left:24px; line-height:2.2;">
            <li><a href="data/job_1/sequences.fasta" download>sequences.fasta</a> - All 50 retrieved sequences</li>
            <li><a href="data/job_1/aligned.fasta" download>aligned.fasta</a> - ClustalOmega alignment</li>
            <li><a href="data/job_1/motifs.txt" download>motifs.txt</a> - Patmatmotifs output</li>
            <li><a href="data/job_1/blast.txt" download>blast.txt</a> - BLAST output</li>
            <li><a href="data/job_1/structure.pdb" download>structure.pdb</a> - ESMFold predicted 3D structure</li>
            <li><a href="data/job_1/log.txt" target="_blank">log.txt</a> - Analysis log</li>
        </ul>
    </div>

    <hr>

    <div class="infobox">
        Ready to run your own analysis? <a href="index.php">Search JAHbio &rarr;</a>
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
