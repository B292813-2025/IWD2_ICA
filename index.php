<?php
// Start session to store user data (errors, name, results history etc.)
session_start();
?>
<html>
<head>
    <title>JAHbio — Just Another Homology Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link to external stylesheet -->
    <link rel="stylesheet" href="style2.css">
</head>
<body>

<div class="header">
    <div class="container header-inner">
        <div>
            <h1><span>JAH</span>bio</h1>
            <p>Just Another Homology Tool. Except better ;)</p>
        </div>
        <!-- Logo image (stored locally) -->
        <img src="images/jahbio.png" alt="Web logo" style="height:100px; opacity:0.9;">
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
<?php
// Display any stored error messages from previous runs
if (!empty($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo '<div class="error">' . htmlspecialchars($error) . '</div>';
    }
    unset($_SESSION['errors']);
}
?>

<h2>Welcome to JAHbio</h2>
<p>
    JAHbio is a protein sequence analysis platform built for biologists. 
    Specify a protein family & taxonomic group and let JAHbio handle 
    everything else: from retrieving sequences at NCBI, to generating 
    publication-ready analyses. No coding required. Simply fill in the 
    form and follow the instructions as outlined below.
    Any <a href="feedback.php">feedback</a> is greatly appreciated.
</p>
<!-- User inputs for search - defaults in place -->
<h3>Parameters</h3>
    <form action="search.php" method="post">
<pre>
Protein family  : <input type="text"   name="protein"  value="glucose-6-phosphatase"/>
Taxonomic group : <input type="text"   name="taxon"    value="Aves"/>
Max sequences   : <input type="number" name="max_seqs" value="50" min="1" max="200"/>
</pre>
<h3>Analysis to run:</h3>
<pre>
<input type ="checkbox" name="do_align" value="yes" checked/> Sequence alignment &amp; conservation plot
<input type ="checkbox" name="do_motif" value="yes" checked/> PROSITE motif scan
<input type ="checkbox" name="do_blast" value="yes" checked/> BLAST similarity search
<input type ="checkbox" name="do_pymol" value="yes" checked/> 3D Visualisation
</pre>


<div style="text-align:center;">
    <input type="submit" value="Search and Analyse"/>
</div>

</pre>
</form>

    <hr>

    <h3>What JAHbio does</h3>
    <div class="infobox">
    1. &nbsp; Fetches the corresponding protein sequences from the NCBI database (always)<br>
    2. &nbsp; Generates a sequence length histogram (always)<br>
    3. &nbsp; Aligns the sequences with ClustalOmega<br>
    4. &nbsp; Generates a plot of the sequence conservation<br>
    5. &nbsp; Scans for known PROSITE motifs using patmatmotifs<br>
    6. &nbsp; Provides a 3D visualisation (predicted structure)<br>
    7. &nbsp; Stores your results so you can revisit them later<br>
    NOTE: You can select which of these analyses you would like to perform
    </div>
    <hr>

    <h3 style="margin-bottom:6px;">Not sure where to start?</h3>
    <p>
    <!-- Link to example dataset -->
        Try the <a href="example.php">pre-loaded example dataset:</a> 
        glucose-6-phosphatase sequences from Aves (birds). All analyses are 
        already run so you can see exactly what to expect.
    </p>

    <?php
    // Not sure why this doesn't work yet but not a major issue at all
    if (isset($_SESSION['forename'])) {
        echo '<div class="success">';
        echo 'Welcome back, <b>' . htmlspecialchars($_SESSION['forename']) . '</b>. ';
        echo '<a href="history.php">View your previous results &rarr;</a>';
        echo '</div>';
    }
    ?>

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
