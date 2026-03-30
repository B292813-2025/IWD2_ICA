<?php session_start(); ?>
<!DOCTYPE html>
<! Uses job_38 as as example >
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
        .badge-na { background: var(--navy-light); color: var(--grey-text); }
        .ext-links a { font-size: 0.8em; margin-right: 6px; }
        @media (max-width: 600px) { table { font-size: 0.75em; display: block; overflow-x: auto; } td, th { padding: 8px 10px; white-space: nowrap; } }
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <div class="header-inner">
            <div>
                <h1><span>JAH</span>bio</h1>
                <p>Just Another Homology Tool. Except better&nbsp;;)</p>
            </div>
        </div>
    </div>
</div>

<!-- Nav links to different pages -->
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
        before running your own search. In this particular instance, no motif sites were found.
    </p>

    <hr>

    <!-- Sequence Summary (copy-pasted from results of a run matching these paramters) -->
    <div class="result-section">
        <h3>Sequence Summary <span class="badge badge-complete">Complete</span></h3>
        <table>
            <tr><th>#</th><th>Accession</th><th>Species</th><th>Length (aa)</th><th>External Links</th></tr>
            <?php
            $seqs = [
                ['KAN1728004','Threskiornis aethiopicus',342],
                ['KAN1727817','Threskiornis aethiopicus',358],
                ['XP_081252512.1','Grus grus',358],
                ['XP_081251926.1','Grus grus',342],
                ['XP_081251925.1','Grus grus',354],
                ['XP_081279448.1','Grus grus',267],
                ['XP_081279447.1','Grus grus',295],
                ['XP_081279446.1','Grus grus',295],
                ['XP_081279445.1','Grus grus',299],
                ['XP_081279444.1','Grus grus',355],
                ['XP_080421443.1','Anser brachyrhynchus',358],
                ['XP_080457172.1','Anser brachyrhynchus',355],
                ['XP_080489744.1','Psittacula echo',358],
                ['XP_080489380.1','Psittacula echo',373],
                ['XP_080478064.1','Psittacula echo',264],
                ['XP_080478071.1','Psittacula echo',287],
                ['XP_080478070.1','Psittacula echo',299],
                ['XP_080478069.1','Psittacula echo',299],
                ['XP_080478068.1','Psittacula echo',299],
                ['XP_080478067.1','Psittacula echo',299],
                ['XP_080478066.1','Psittacula echo',299],
                ['XP_080478065.1','Psittacula echo',306],
                ['XP_080478063.1','Psittacula echo',355],
                ['XP_080226139.1','Falco punctatus',358],
                ['XP_080226138.1','Falco punctatus',489],
                ['XP_080225761.1','Falco punctatus',355],
                ['XP_080225760.1','Falco punctatus',365],
                ['XP_080255093.1','Falco punctatus',299],
                ['XP_080255092.1','Falco punctatus',420],
                ['XP_080216133.1','Nesoenas mayeri',341],
                ['XP_080215990.1','Nesoenas mayeri',358],
                ['XP_080202477.1','Nesoenas mayeri',355],
                ['XP_079403352.1','Tetrao urogallus',358],
                ['XP_079395065.1','Tetrao urogallus',355],
                ['XP_079184151.1','Erithacus rubecula',359],
                ['XP_079167338.1','Erithacus rubecula',231],
                ['XP_079167337.1','Erithacus rubecula',299],
                ['XP_079167336.1','Erithacus rubecula',299],
                ['XP_079167335.1','Erithacus rubecula',310],
                ['XP_079167334.1','Erithacus rubecula',355],
                ['XP_079054589.1','Caprimulgus europaeus',299],
                ['XP_079054588.1','Caprimulgus europaeus',306],
                ['XP_079054587.1','Caprimulgus europaeus',355],
                ['XP_079064106.1','Caprimulgus europaeus',342],
                ['XP_079064077.1','Caprimulgus europaeus',358],
                ['KAM9657203','Morphnus guianensis',358],
                ['KAM9656907','Morphnus guianensis',342],
                ['KAM9656906','Morphnus guianensis',389],
                ['KAM9629049','Morphnus guianensis',299],
                ['KAM9629048','Morphnus guianensis',299],
            ];
            foreach ($seqs as $i => $seq):
                $acc     = htmlspecialchars($seq[0]);
                $species = htmlspecialchars($seq[1]);
                $len     = $seq[2];
                $acc_url = preg_replace('/\.\d+$/', '', $seq[0]);
            ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo $acc; ?></td>
                <td><em><?php echo $species; ?></em></td>
                <td><?php echo $len; ?></td>
                <td class="ext-links">
                    <a href="https://www.ncbi.nlm.nih.gov/protein/<?php echo $acc; ?>" target="_blank">NCBI</a>
                    <a href="https://www.uniprot.org/uniprot/<?php echo $acc_url; ?>" target="_blank">UniProt</a>
                    <a href="https://www.ebi.ac.uk/interpro/search/sequence/?q=<?php echo $acc; ?>" target="_blank">InterPro</a>
                    <a href="https://alphafold.ebi.ac.uk/entry/<?php echo $acc_url; ?>" target="_blank">AlphaFold</a>
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

    <!-- HISTOGRAM -->
    <div class="result-section">
        <h3>Sequence Length Distribution <span class="badge badge-complete">Complete</span></h3>
        <img src="data/job_38/histogram.png" alt="Sequence length histogram" class="result-img">
        <div class="bio-note">
            This histogram shows the distribution of sequence lengths across all 50 retrieved
            glucose-6-phosphatase sequences. The dominant peak around 355–360 aa corresponds
            to the canonical full-length G6Pase catalytic subunit. Shorter sequences may
            represent partial or alternatively spliced isoforms, while longer sequences may
            contain N- or C-terminal extensions.
        </div>
    </div>

    <hr>

    <!-- CONSERVATION -->
    <div class="result-section">
        <h3>Conservation Plot <span class="badge badge-complete">Complete</span></h3>
        <img src="data/job_38/conservation.1.png" alt="Conservation plot" class="result-img">
        <div class="bio-note">
            This conservation plot was generated by aligning all sequences using ClustalOmega
            and passing the alignment through Plotcon. Higher peaks indicate positions that are
            conserved across species, corresponding to functionally or structurally important
            residues. Regions of low conservation may represent lineage-specific loops or
            variable surface-exposed domains.
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_38/aligned.fasta" download>Download aligned FASTA</a>
        </p>
    </div>

    <hr>

    <!-- MOTIFS -->
    <div class="result-section">
        <h3>PROSITE Motif Scan <span class="badge badge-complete">Complete</span></h3>
        <div class="infobox">
            No PROSITE motifs were detected in the retrieved sequences. This is a valid result —
            not all proteins contain PROSITE-catalogued motifs, particularly if their functional
            signatures are not yet represented in the PROSITE database. See what results this yields for a different search :)
        </div>
        <p style="margin-top:12px;">
            <a href="data/job_38/motifs.txt" target="_blank">View raw Patmatmotifs output</a>
        </p>
    </div>

    <hr>

    <!-- BLAST -->
    <div class="result-section">
        <h3>BLAST Similarity Search <span class="badge badge-complete">Complete</span></h3>
        <?php
        $blast_file = '/home/s2837201/public_html/ICA/data/job_38/blast.txt';
        $blast_lines = file($blast_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = [];
        foreach ($blast_lines as $line) {
            if (strpos($line, '#') === 0) continue;
            $cols = preg_split('/\t/', $line);
            if (count($cols) >= 10) $rows[] = $cols;
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
            <a href="data/job_38/blast.txt" download>Download full BLAST output</a>
        </p>
    </div>

    <hr>

    <!-- 3D STRUCTURE PREDICTION -->
    <div class="result-section">
        <h3>3D Structure Visualisation <span class="badge badge-complete">Complete</span></h3>
        <div id="mol-viewer" style="width:100%; height:500px; border:1px solid var(--grey-border); border-radius:4px; margin-top:12px; background:#1a1a2e; position:relative;"></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://3dmol.org/build/3Dmol-min.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var viewer = $3Dmol.createViewer("mol-viewer", { backgroundColor: "#1a1a2e" });
            jQuery.ajax({
                url: "/~s2837201/ICA/data/job_38/structure.pdb",
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
            <a href="data/job_38/structure.pdb" download>Download PDB file</a>
        </p>
    </div>

    <hr>

    <!-- DOWNLOAD LINKS -->
    <div class="result-section">
        <h3>Downloads</h3>
        <p>Raw output files for this example dataset:</p>
        <ul style="margin-left:24px; line-height:2.2;">
            <li><a href="data/job_38/sequences.fasta" download>sequences.fasta</a> — All 50 retrieved sequences</li>
            <li><a href="data/job_38/aligned.fasta" download>aligned.fasta</a> — ClustalOmega alignment</li>
            <li><a href="data/job_38/motifs.txt" download>motifs.txt</a> — Patmatmotifs output</li>
            <li><a href="data/job_38/blast.txt" download>blast.txt</a> — BLAST output</li>
            <li><a href="data/job_38/structure.pdb" download>structure.pdb</a> — ESMFold predicted 3D structure</li>
            <li><a href="data/job_38/log.txt" target="_blank">log.txt</a> — Analysis log</li>
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
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a></p>
    </div>
</div>

</body>
</html>
