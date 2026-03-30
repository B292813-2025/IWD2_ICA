<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About — JAHbio</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
<! Same as usual -->
<!-- ===== HEADER ===== -->
<div class="header">
    <div class="container header-inner">
        <div>
            <h1><span>JAH</span>bio</h1>
            <p>Just Another Homology Tool. Except better ;)</p>
        </div>
        <img src="images/jahbio.png" alt="Web logo" style="height:100px; opacity:0.9;">
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

    <h2>About JAHbio</h2>

    <h3>Our Mission</h3>
    <p>
        JAHbio was developed as part of a project to simplify and centralise homology analysis
        within a single, user-friendly web tool. The aim is to make commonly used bioinformatics
        processes more accessible, while maintaining functionality for more experienced users.
    </p>
    <p>
        A key principle behind JAHbio is that scientific tools should be accessible to a wide
        audience. Many existing bioinformatics resources require technical expertise, which can
        create barriers for students and researchers new to the field. This platform addresses
        that by providing a clean interface and automated workflows that guide users through the
        analysis process. At the same time, JAHbio remains useful for experienced users by
        streamlining repetitive tasks and allowing them to focus on interpreting results.
    </p>
    <p>
        As this is an evolving project (which I will add to even after the project submission deadline),
         user feedback is an important part of its development.
        Suggestions for additional features or improvements can be submitted through the <a href="feedback.php">feedback form</a> 
        and will be considered in future updates.
    </p>

    <hr>

    <h3>System Architecture</h3>
    <p>
        JAHbio is built on a standard LAMP stack (Linux, Apache, MySQL, and PHP) hosted on
        the University of Edinburgh bioinformatics (MSc 8) server. The front end is written in plain HTML
        and PHP with a custom CSS stylesheet. No JavaScript frameworks are used; interactivity
        is kept minimal and purposeful, limited to live progress polling and the interactive 3D
        structure viewer.
    </p>
    <p>
        The database layer uses MySQL accessed exclusively through PHP's PDO interface with
        parameterised queries throughout, protecting against SQL injection. Four tables store
        job metadata, per-sequence data, per-analysis step statuses, and PROSITE motif hits
        respectively. Jobs are identified by a unique auto-incremented ID and associated with
        the user's PHP session for result retrieval. One table is used to store feedback.
    </p>

    <hr>

    <h3>Analysis Pipeline</h3>
    <p>
        When a user submits a search, the server validates inputs, creates a database record,
        and generates a per-job shell script which is launched as a background process using
        nohup. This allows the PHP request to return immediately to the browser while
        the analysis continues independently on the server. The progress page polls a
        JSON endpoint every three seconds to reflect the current status of each analysis step - constantly keeping
        users updated with the progress of their selected analyses.
    </p>
    <p>
        Sequence retrieval is handled by Biopython's Entrez interface, which queries the NCBI
        protein database and fetches results in FASTA format. The number of sequences returned
        is controlled by the user-specified limit passed directly to the Entrez API. 
        Sequences are then parsed and stored in the database for display on the results page.
    </p>
    <p>
        Multiple sequence alignment is performed using ClustalOmega, and the resulting alignment
        is passed to Plotcon from the EMBOSS suite to generate a per-position conservation score
        plot. PROSITE motif scanning is carried out by Patmatmotifs (also from EMBOSS) with hits
        parsed from the output and stored in the database. BLAST similarity searching uses a
        local database built from the fetched sequences, with an all-versus-all BLASTp search
        run against it. 3D structure prediction is performed using ESMFold via
        Meta's public API, which accepts a raw amino acid sequence and returns a predicted PDB
        file. The structure is rendered interactively in the browser using 3Dmol.js.
    </p>

    <hr>

    <h3>Data Storage and Privacy</h3>
    <p>
        All analysis outputs are written to a per-job directory on the server filesystem and
        are accessible via the results page for the duration of the session. Jobs are associated
        with anonymous PHP session IDs - no personal data is collected or stored. Users can
        revisit their results via the history page for as long as their session cookie remains
        valid.
    </p>

    <hr>

    <h3>External Services and Tools</h3>
    <p>
        JAHbio relies on several external tools and services. Sequence data is retrieved from
        NCBI via the Entrez API. Structure prediction uses the ESMFold API provided by Meta AI.
        3D structure rendering in the browser uses the 3Dmol.js library. All other analyses
        including: alignment, conservation plotting, motif scanning, and BLAST, are performed locally
        on the server using the pre-installed bioinformatics software.
    </p>

</div>
</div>

<!-- ===== FOOTER ===== -->
<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a></p>
    </div>
</div>

</body>
</html>
