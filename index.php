<?php
// Start session to store user data (errors, name, results history etc.)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
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
    Specify a protein family &amp; taxonomic group and let JAHbio handle 
    everything else: from retrieving sequences at NCBI, to generating 
    publication-ready analyses. No coding required. Simply fill in the 
    form and follow the instructions as outlined below.
    Any <a href="feedback.php">feedback</a> is greatly appreciated.
</p>

<!-- Pre-check feedback divs -->
<div id="search-error" class="error" style="display:none;"></div>
<div id="search-checking" class="success" style="display:none;">Checking NCBI for matching sequences...</div>

<!-- User inputs for search - defaults in place -->
<h3>Parameters</h3>
<form id="search-form" action="search.php" method="post">
<pre>
Protein family  : <input type="text"   name="protein"  id="protein"  value="glucose-6-phosphatase"/>
Taxonomic group : <input type="text"   name="taxon"    id="taxon"    value="Aves"/>
Max sequences   : <input type="number" name="max_seqs" value="50" min="1" max="200"/>
</pre>
<pre>
Search type     : <select name="search_type" id="search_type" style="font-family:'Droid Serif',serif; font-size:1em; padding:6px 10px; border:1px solid var(--grey-border); border-radius:3px; color:var(--navy);">
                      <option value="All Fields">All Fields (broader)</option>
                      <option value="protein">Protein name only (stricter)</option>
                  </select>
</pre>
<h3>Analysis to run:</h3>
<pre>
<input type="checkbox" name="do_align" value="yes" checked/> Sequence alignment &amp; conservation plot
<input type="checkbox" name="do_motif" value="yes" checked/> PROSITE motif scan
<input type="checkbox" name="do_blast" value="yes" checked/> BLAST similarity search
<input type="checkbox" name="do_pymol" value="yes" checked/> 3D Visualisation
</pre>
<pre>
</pre>

<div style="text-align:center;">
   <input type="button" id="search-btn" value="Search and Analyse" style="font-family:'Droid Serif',serif; font-size:0.95em; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:10px 30px; background-color:#c8102e; color:#ffffff; border:none; border-radius:3px; cursor:pointer;"/>
</div>

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
        <p>JAHbio &mdash; Just Another Homology Tool &mdash; The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a> &mdash; <a href="https://github.com/B292813-2025/IWD2_ICA" target="_blank">GitHub</a></p>
    </div>
</div>

<script>
// Attach a click event listener to the search button
document.getElementById('search-btn').addEventListener('click', function() {

    // Get user input values and remove any extra whitespace
    var protein    = document.getElementById('protein').value.trim();
    var taxon      = document.getElementById('taxon').value.trim();
    var searchType = document.getElementById('search_type').value;

    // Get references to UI elements for displaying messages
    var errorDiv   = document.getElementById('search-error');
    var checkDiv   = document.getElementById('search-checking');

    // Validate that both input fields are filled in
    if (!protein || !taxon) {
        // Show an error message if either field is empty
        errorDiv.textContent = 'Please enter both a protein family and a taxonomic group.';
        errorDiv.style.display = 'block';
        return; // Stop further execution
    }

    // Hide any previous error message
    errorDiv.style.display = 'none';

    // Show a "checking" message or loading indicator
    checkDiv.style.display = 'block';

    // Disable the search button to prevent multiple submissions
    document.getElementById('search-btn').disabled = true;

    // Build the NCBI search query string
    // Format: protein[searchType] AND taxon[organism]
    var query = encodeURIComponent(
        protein + '[' + searchType + '] AND ' + taxon + '[organism]'
    );

    // Construct the API request URL for NCBI E-utilities
    // retmax=0 means we only retrieve the count, not actual records
    var url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi'
            + '?db=protein'
            + '&term=' + query
            + '&retmax=0'
            + '&retmode=json'
            + '&api_key=bc81dc27024bce567d64cb201a28e9ad8508';

    // Send the request to the NCBI API
    fetch(url)

        // Convert the response to JSON format
        .then(r => r.json())

        // Process the returned data
        .then(data => {

            // Hide the loading indicator and re-enable the button
            checkDiv.style.display = 'none';
            document.getElementById('search-btn').disabled = false;

            // Extract the number of matching sequences from the response
            var count = parseInt(data.esearchresult.count);

            // If no sequences found
            if (count === 0) {
                errorDiv.textContent =
                    'No sequences found for "' + protein +
                    '" in "' + taxon +
                    '" on NCBI. Check your spelling or try a broader search term.';
                errorDiv.style.display = 'block';

            // If less than 2 sequences found
            } else if (count < 2) {
                errorDiv.textContent =
                    'Only ' + count +
                    ' sequence found — at least 2 are needed. Try broadening your search.';
                errorDiv.style.display = 'block';

            // Enough sequences found - proceed with form submission
            } else {
                document.getElementById('search-form').submit();
            }
        })

        // Handle errors (network issues or API failure)
        .catch(function() {

            // If the NCBI check fails, allow the form to submit anyway
            checkDiv.style.display = 'none';
            document.getElementById('search-btn').disabled = false;
            document.getElementById('search-form').submit();
        });
});
</script>

</body>
</html>
