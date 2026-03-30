<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | JAHbio</title>
    <link rel="stylesheet" href="/~s2837201/ICA/style2.css">
    <style>
        body {
            margin: 0;
            background: transparent;
        }

        #dna-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            background: var(--navy);
            display: flex;
            justify-content: space-between;
        }

        .dna-col {
            flex: 1;              /* Claude helped fix this section - looked a bit off initially */
            max-width: 72px;      /* keeps the intended density */
            align-items: center;  /* center the text */ 
            position: relative;
            top: 0;
            display: flex;
            flex-direction: column;
            gap: clamp(18px, 4vw, 30px);
            animation: dna-fall linear infinite;
            font-family: 'Source Code Pro', monospace;
            font-size: 18px;
            font-weight: 500;
            user-select: none;
            transform: translateY(-50%);
        }

        .dna-col span {
            display: block;
            text-align: center;
            letter-spacing: 0.05em;
        }

        @keyframes dna-fall {
            0%   { transform: translateY(-50%); }
            100% { transform: translateY(0); }
        }

        .page-wrap {
            position: relative;
            z-index: 1;
        }

        .header, .menu, .footer {
            position: relative;
            z-index: 2;
        }

        .menu {
            background-color: var(--navy) !important;
        }

        .content {
            background-color: rgba(4, 30, 66, 0.78) !important;
            border-left: none !important;
            box-shadow: 0 8px 40px rgba(0,0,0,0.4) !important;
        }

        .content h2, .content p {
            color: #fff !important;
        }

        .error-box {
            text-align: center;
            padding: 60px 40px;
        }

        .error-number {
            font-size: 8em;
            font-weight: 700;
            color: var(--red);
            line-height: 1;
            text-shadow: 0 0 60px rgba(200,16,46,0.5);
            animation: pulse 2.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.65; }
        }

        .back-btn {
            display: inline-block;
            background: var(--red);
            color: #fff;
            padding: 12px 32px;
            border-radius: 3px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            font-size: 0.9em;
            margin-top: 32px;
            transition: background 0.18s, box-shadow 0.18s;
            border-bottom: none;
        }

        .back-btn:hover {
            background: #a00d25;
            box-shadow: 0 4px 12px rgba(200,16,46,0.4);
            color: #fff;
            border-bottom: none;
        }
       @media (max-width: 600px) {
            .dna-col {
            font-size: 10px;
        }
    }
    </style>
</head>
<body>

<div id="dna-bg"></div>

<div class="page-wrap">

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
    <div class="error-box">
        <div class="error-number">404</div>
        <h2 style="border:none; margin-top:16px;">Page Not Found</h2>
        <p style="max-width:480px; margin:16px auto;">
            The page you were looking for does not exist or has been moved.
            Perhaps you followed a broken link, or the URL was mistyped.
            No need to worry - click the button below to find your way back&nbsp;:)
        </p>
        <a href="/~s2837201/ICA/index.php" class="back-btn">&larr; Back to JAHbio</a>
    </div>
</div>
</div>

<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="/~s2837201/ICA/credits.php">Statement of Credits</a></p>
    </div>
</div>

</div>

<script>
(function() {
// my code which I passed to claude to fix/make compatible with this page (and also add nicer colours)
    var aas = [
        { code: 'Ala', colour: '#7a9ab8' },
        { code: 'Val', colour: '#7a9ab8' },
        { code: 'Ile', colour: '#7a9ab8' },
        { code: 'Leu', colour: '#7a9ab8' },
        { code: 'Met', colour: '#7a9ab8' },
        { code: 'Phe', colour: '#7a9ab8' },
        { code: 'Trp', colour: '#7a9ab8' },
        { code: 'Pro', colour: '#7a9ab8' },
        { code: 'Gly', colour: '#7a9ab8' },
        { code: 'Ser', colour: '#a8d8a8' },
        { code: 'Thr', colour: '#a8d8a8' },
        { code: 'Cys', colour: '#f0d080' },
        { code: 'Tyr', colour: '#a8d8a8' },
        { code: 'Asn', colour: '#a8d8a8' },
        { code: 'Gln', colour: '#a8d8a8' },
        { code: 'Asp', colour: '#e07070' },
        { code: 'Glu', colour: '#e07070' },
        { code: 'Lys', colour: '#88aaee' },
        { code: 'Arg', colour: '#88aaee' },
        { code: 'His', colour: '#88aaee' },
    ];

    var bg      = document.getElementById('dna-bg');
    var spacing = 72;
    var cols = Math.ceil(window.innerWidth / spacing) + 4;

    for (var i = -1; i < cols; i++) {
        var col = document.createElement('div');
        col.className = 'dna-col';
        col.style.opacity = (0.25 + Math.random() * 0.25).toFixed(2);

        var duration = (Math.random(1) * 16).toFixed(1);
        var delay    = (-Math.random() * parseFloat(duration)).toFixed(2);
        col.style.animationDuration = duration + 's';
        col.style.animationDelay    = delay + 's';

        // create one sequence
        var seq = [];
        for (var j = 0; j < 150; j++) {
            seq.push(aas[Math.floor(Math.random() * aas.length)]);
        }

        // duplicate it (loop effect so it never ends)
        for (var k = 0; k < 2; k++) {
            for (var j = 0; j < seq.length; j++) {
                var span = document.createElement('span');
                span.textContent = seq[j].code;
                span.style.color = seq[j].colour;
                col.appendChild(span);
            }
        }

        bg.appendChild(col);
    }

})();
</script>

</body>
</html>
