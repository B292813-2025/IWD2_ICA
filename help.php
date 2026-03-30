<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help — JAHbio</title>
    <link rel="stylesheet" href="style2.css">
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

    <h2>Help &amp; Biological Background</h2>
    <p>
        This page explains what JAHbio does and why each analysis is biologically meaningful.
        No programming knowledge is required to use this tool - just an interest in proteins
        and the organisms that make them.
    </p>

    <hr>

    <h3>What is Homology Analysis?</h3>
    <p>
        Two proteins are said to be homologous if they share a common evolutionary ancestor.
        Homology is typically inferred from sequence similarity; if two protein sequences are
        sufficiently similar, they are likely to have descended from the same ancestral gene.
        Homologous proteins often share similar 3D structures and perform related
        biological functions, even across very distantly related species.
    </p>
    <p>
        Homology analysis is a fundamental technique in molecular biology and genomics. It allows
        researchers to predict the function of an uncharacterised protein based on its similarity
        to a well-studied one, to trace the evolutionary history of a gene family, and to identify
        conserved regions that are likely to be functionally important. JAHbio automates this
        process by fetching sequences from a public database, aligning them, and running a selection
        of complementary analyses - all from a single search.
    </p>

    <hr>

    <h3>Sequence Retrieval from NCBI</h3>
    <p>
        JAHbio searches the <a href="https://www.ncbi.nlm.nih.gov/protein/" target="_blank">NCBI
        Protein Database</a>, one of the largest publicly available repositories of protein
        sequences in the world. When you enter a protein family name and a taxonomic group,
        JAHbio queries this database and retrieves matching sequences in FASTA format (a standard
        plain-text format used to represent biological sequences).
    </p>
    <p>
        The taxonomic group you enter can be as broad or as specific as you like. You can search
        within an entire class of organisms (e.g. <em>Aves</em> for all birds, or
        <em>Rodentia</em> for all rodents), a single genus, or a single species such as
        <em>Homo sapiens</em>. Broader searches will typically return more sequences and greater
        evolutionary diversity, while narrower searches are useful for comparing closely related
        species.
    </p>
    <p>
        The number of sequences you request affects both the speed of the analysis and the
        quality of the results. More sequences provide a richer picture of evolutionary diversity,
        but very large datasets can slow down alignment and other downstream steps.
    </p>

    <hr>

    <h3>Sequence Length Distribution</h3>
    <p>
        The first output JAHbio produces is a histogram of sequence lengths. This gives an
        immediate overview of the dataset. Most sequences in a protein family will cluster
        around a characteristic length - the full-length canonical form of the protein. Sequences
        that are substantially shorter may represent partial sequences, alternative splice
        isoforms, or pseudogenes, while longer sequences may carry lineage-specific insertions
        or additional functional domains.
    </p>
    <p>
        A narrow, well-defined length distribution suggests that the sequences in your dataset
        are all likely to be true orthologues of the same protein. A broad or multimodal
        distribution may indicate that the search has retrieved a mixture of related proteins
        from the same family, or that significant structural variation exists across the taxa
        examined.
    </p>

    <hr>

    <h3>Multiple Sequence Alignment</h3>
    <p>
        Before comparing sequences, JAHbio aligns them using
        <a href="http://www.clustal.org/omega/" target="_blank">ClustalOmega</a>, a widely
        used multiple sequence alignment tool. Alignment arranges the sequences so that
        equivalent positions, amino acids that occupy the same position in the protein
        structure, are lined up in the same column. Gaps are introduced where one sequence
        has an insertion or deletion relative to the others.
    </p>
    <p>
        The resulting alignment is the foundation for the conservation analysis. It is also
        available to download as a FASTA file, which can be loaded into other tools such as
        <a href="https://www.jalview.org" target="_blank">Jalview</a> or
        <a href="https://msa.biojs.net" target="_blank">BioJS MSA viewer</a> for more detailed
        visual exploration.
    </p>

    <hr>

    <h3>Conservation Analysis</h3>
    <p>
        Once the sequences are aligned, JAHbio calculates a conservation score at each position
        using Plotcon. A high conservation score at a given position means that the same or
        chemically similar amino acid is present across most or all of the species in your
        dataset. Positions that are highly conserved over long evolutionary timescales are
        strong candidates for functional or structural importance - mutations at these positions
        are likely to disrupt protein function and are therefore selected against.
    </p>
    <p>
        Conversely, positions with low conservation scores are more variable and may correspond
        to surface-exposed loops, linker regions, or sites under positive selection that have
        diverged between lineages. Comparing the conservation profile to known structural or
        functional features of the protein can reveal which parts of the sequence drive its
        core activity.
    </p>

    <hr>

    <h3>PROSITE Motif Scanning</h3>
    <p>
        JAHbio scans all retrieved sequences against the
        <a href="https://prosite.expasy.org" target="_blank">PROSITE database</a> of protein
        families, domains, and functional sites. PROSITE contains curated patterns and profiles
        representing known sequence signatures associated with specific biological functions,
        for example, the ATP-binding P-loop motif, glycosylation sites, or active site residues
        of specific enzyme families.
    </p>
    <p>
        When a motif is detected, the results table shows the name of the motif, the sequence
        it was found in, and the exact start and end positions within that sequence. Finding the
        same motif in sequences from multiple species is strong evidence that the corresponding
        functional feature is conserved and therefore biologically important. The absence of a
        known motif does not mean the protein lacks that function. The PROSITE database, while
        comprehensive, does not yet catalogue all known functional signatures.
    </p>

    <hr>

    <h3>BLAST Similarity Search</h3>
    <p>
        JAHbio performs an all-versus-all
        <a href="https://blast.ncbi.nlm.nih.gov" target="_blank">BLASTp</a> search across all
        retrieved sequences. BLAST (Basic Local Alignment Search Tool) identifies regions of
        local similarity between sequences and calculates a statistical score indicating how
        likely the similarity is to have arisen by chance.
    </p>
    <p>
        The key values to focus on are the percentage identity and the E-value. Percentage
        identity tells you what fraction of aligned residues are identical between two sequences.
        The E-value represents the expected number of alignments of equal or better quality that
        would occur by chance in a database of the same size - a lower E-value means a more
        statistically significant match. E-values at or near 0.0 indicate extremely high
        similarity. High percentage identity between sequences from different species confirms
        that they are orthologues (evolutionarily equivalent genes performing the same function
        in different organisms).
    </p>

    <hr>

    <h3>3D Structure Prediction</h3>
    <p>
        JAHbio predicts the three-dimensional structure of the first retrieved sequence using
        <a href="https://esmatlas.com" target="_blank">ESMFold</a>, a deep learning model
        developed by Meta AI that predicts protein structure directly from amino acid sequence.
        The predicted structure is displayed interactively in the browser - you can rotate,
        zoom, and pan the model to examine it from any angle.
    </p>
    <p>
        Protein structure is more conserved than sequence over evolutionary time; two proteins
        can have very different sequences but fold into nearly identical three-dimensional shapes
        if they perform the same function. Viewing the predicted structure alongside the
        conservation and motif data allows you to assess whether conserved sequence positions
        correspond to structurally or functionally important regions such as active sites,
        binding pockets, or buried hydrophobic cores.
    </p>
    <p>
        It is important to note that ESMFold produces a <em>predicted</em> structure, not an
        experimentally determined one. For experimentally solved structures, consult the
        <a href="https://www.rcsb.org" target="_blank">RCSB Protein Data Bank</a>. The
        confidence of the prediction varies across the protein, flexible loop regions are
        typically predicted with lower confidence than well-structured helices and sheets.
        JAHbio uses a set colour-scheme which is not intended to convey any sort of confidence score.
    </p>

    <hr>

    <h3>Tips for Getting Good Results</h3>
    <div class="infobox">
        Use the correct taxonomic name (e.g. <em>Rodentia</em> rather than "rodents", or
        <em>Homo sapiens</em> rather than "human"). NCBI uses formal taxonomy.<br>
        Use the singular form of the protein name (e.g. "kinase" rather than "kinases").<br>
        Start with 20-50 sequences for a manageable, informative dataset. Very large
        datasets (100+) will take longer to process.<br>
        If no sequences are found, try broadening the taxon (e.g. from a species to a family
        or order) or simplifying the protein name.<br>
        The example dataset (glucose-6-phosphatase in Aves) is a good reference
        for what a complete, well-populated analysis looks like.
    </div>

    <hr>

    <p>
        Not sure where to start? Try the <a href="example.php">pre-loaded example dataset</a>
        to see all analyses in action before running your own search.
    </p>

</div>
</div>

<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a> &mdash; <a href="https://github.com/B292813-2025/IWD2_ICA" target="_blank">GitHub</a></p>
    </div>
</div>

</body>
</html>
