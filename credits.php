<?php
session_start();
// The same usual setup for consistency (this is mostly just plain text with some links to the mentioned tools/websites/resources - not many comments to add
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credits — JAHbio</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>

<!-- ===== HEADER ===== -->
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

<!-- ===== NAV ===== -->
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

<!-- ===== CONTENT ===== -->
<div class="container">
<div class="content">

    <h2>Statement of Credits</h2>

    <h3>Generative AI Usage</h3>
    <p>
        The following AI tools were used during the development of JAHbio. In accordance with
        the assessment guidelines, each use is described in specific detail below.
    </p>

    <p>
        <strong><a href="https://claude.ai" target="_blank">Anthropic Claude</a></strong>
        (Claude Sonnet 4.5) was the primary AI assistant used throughout development.
        It was used to:</br>
        1. &nbsp Generate the initial CSS stylesheet and certian local style additions, which was subsequently modified by hand</br>
        2. &nbsp Debug (and in some cases improve) the PHP pipeline orchestration, per-job shell script generation, and PDO handling</br> 
        3. &nbsp Implement the live progress polling system</br>
        4. &nbsp Help build the results page (linking to specific files/scripts and fixing the displays)</br> 
        5. &nbsp Integrate the animated JavaScript used for the  404 error page as well as making
        the red pulse effect for "404"
    </p>

    <p>
        <strong><a href="https://chatgpt.com" target="_blank">OpenAI ChatGPT</a></strong>
        (GPT-4o) was used for generating the JAHbio logo image displayed in the top right of
        every page using DALL-E image generation and resolving specific syntax errors encountered during development. It was also used
        to help refine the text used in the "About", "Help" and "Credits" page.
    </p>

    <hr>

    <h3>External APIs</h3>
    <p>
        <strong><a href="https://www.ncbi.nlm.nih.gov/home/develop/api/" target="_blank">NCBI API</a></strong>:
        Used via Biopython's Entrez module to search the NCBI protein database and retrieve
        protein sequences in FASTA format. Also used for pre-submission sequence count
        validation and taxonomy lookups to resolve species names from TaxIDs.
    </p>
    <p>
        <strong><a href="https://esmatlas.com/about#fold-sequence" target="_blank">ESMFold API</a></strong>
        (Meta AI): Used to predict 3D protein structures from amino acid sequences. The first
        retrieved sequence for each job is submitted to the ESMFold REST API which returns a
        PDB-format structure file.
    </p>

    <hr>

    <h3>Third-Party Libraries</h3>
    <p>
        <strong><a href="https://3dmol.csb.pitt.edu" target="_blank">3Dmol.js</a></strong>:
        A JavaScript library used to render predicted PDB structures interactively in the
        browser on the results page. Loaded from the 3Dmol.js CDN.
    </p>
    <p>
        <strong><a href="https://jquery.com" target="_blank">jQuery 3.6.0</a></strong>:
        Used on the results page to perform an AJAX request to load the PDB file for the
        3Dmol.js viewer. Loaded from the Cloudflare CDN.
    </p>
    <p>
        <strong><a href="https://matplotlib.org" target="_blank">Matplotlib</a></strong>:
        A Python plotting library used server-side to generate the sequence length histogram
        PNG for each job.
    </p>
    <p>
        <strong><a href="https://biopython.org" target="_blank">Biopython</a></strong>:
        Used server-side for all NCBI Entrez interactions including sequence search, fetch,
        taxonomy lookup, and pre-submission result count validation.
    </p>
    <p>
        <strong><a href="https://fonts.google.com" target="_blank">Google Fonts</a></strong>:
        Droid Serif and Source Code Pro typefaces are loaded from Google Fonts CDN and used
        throughout the site for headings, body text, and form elements respectively.
    </p>
    <p>
       <strong><a href="https://www.youtube.com/watch?v=kPtS4vO42II" target="_blank">Youtube</a></strong>:
       This video by Dani Krossing taught me the basic outline of how to create an error 404 page. Though not technically
       a third party library, I thought it was worth mentioning.
    </p>

    <hr>

    <h3>Bioinformatics Tools</h3>
    <p>The following command-line tools are executed server-side as part of the analysis pipeline:</p>
    <div class="infobox">
        <strong><a href="http://www.clustal.org/omega/" target="_blank">ClustalOmega</a></strong>:
        Multiple sequence alignment of retrieved protein sequences.<br>
        <strong><a href="http://emboss.sourceforge.net/apps/cvs/emboss/apps/plotcon.html" target="_blank">Plotcon</a></strong>
        (EMBOSS): Conservation scoring and plotting across the multiple sequence alignment.<br>
        <strong><a href="http://emboss.sourceforge.net/apps/cvs/emboss/apps/patmatmotifs.html" target="_blank">Patmatmotifs</a></strong>
        (EMBOSS): Scanning retrieved sequences against the PROSITE motif database to
        identify known functional sequence patterns.<br>
        <strong><a href="https://blast.ncbi.nlm.nih.gov/Blast.cgi" target="_blank">NCBI BLAST</a></strong>
        (v2.17.0+): All-versus-all BLASTp similarity search using a locally constructed
        database from the retrieved sequences.
    </div>

</div>
</div>

<!-- ===== FOOTER ===== -->
<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool &mdash; The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a> &mdash; <a href="https://github.com/B292813-2025/IWD2_ICA" target="_blank">GitHub</a></p>
    </div>
</div>

</body>
</html>
