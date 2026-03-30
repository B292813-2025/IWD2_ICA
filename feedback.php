<?php
// Displays a feedback form and stores submissions in the DB.
session_start();
require_once 'login.php';
// Requires login (to mySQL) to run first as this is where it is stored
$submitted = false;
$errors    = []; //storing any possible errors - empty array to start

// Checks the user pressed submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    // gets inputs - trim to remove whitespace
  
    if ($message === '') {
        $errors[] = 'Please enter a message.';
    } //if nothing is entered display this message
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address, or leave the field blank.';
    } //stores error if email entered is not a valid one

    // if there are no errors - store in database
    if (empty($errors)) {
        try {
            $dsn  = "mysql:host=127.0.0.1;dbname=$database;charset=utf8mb4";
            $conn = new PDO($dsn, $username, $password); //connecting...
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //debugging - throw upon error
            $stmt = $conn->prepare('
                INSERT INTO feedback (name, email, subject, message)
                VALUES (?, ?, ?, ?)
            '); //placeholders
            $stmt->execute([$name ?: null, $email ?: null, $subject ?: null, $message]); //if empty store NULL
            $submitted = true; //mark as submitted
        } catch (PDOException $e) {
            $errors[] = 'Could not save feedback. Please try again later.';
        } //picks up any DB issues and shows an error message if that is the case
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback — JAHbio</title>
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

<!-- ===== CONTENT ===== -->
<div class="container">
<div class="content">

    <h2>Feedback</h2>

    <?php if ($submitted): ?>
    // displays a thank you message
        <div class="success">
            Thank you for your feedback - it has been submitted successfully and will
            be reviewed as part of JAHbio's ongoing development.
        </div>
        <p><a href="index.php">&larr; Back to home</a></p>

    <?php else: ?>

        <p>
            We welcome feedback on JAHbio - whether it's a bug report, a feature
            request, or a general comment. Your input helps improve the tool for everyone.
        </p>

        <?php foreach ($errors as $error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <form method="post" action="feedback.php">
        <pre>
        Name (optional)  : <input type="text"  name="name"    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="Your name"/>
        Email (optional) : <input type="text"  name="email"   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="your@email.com"/>
        Subject          : <input type="text"  name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="e.g. Bug report"/>
        </pre>
        <div style="padding: 0 0 0 8px;">
            <label style="font-size:0.95em; color:var(--grey-text); display:block; margin-bottom:8px;">Message <span style="color:var(--red);">*</span></label>
            <textarea name="message" rows="7" style="width:100%; max-width:600px; font-family:'Droid Serif',serif; font-size:0.95em; padding:10px 12px; border:1px solid var(--grey-border); border-radius:3px; color:var(--navy); resize:vertical;" placeholder="Tell us what you think..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        </div>
        <pre>
        <input type="submit" value="Submit Feedback"/>
        </pre>
        </form>

    <?php endif; ?>

</div>
</div>

<!-- ===== FOOTER ===== -->
<div class="footer">
    <div class="container">
        <p>JAHbio &mdash; Just Another Homology Tool | The University of Edinburgh</p>
        <p><a href="credits.php">Statement of Credits</a> &mdash; <a href="https://github.com/B292813-2025/IWD2_ICA" target="_blank">GitHub</a></p>
    </div>
</div>

</body>
</html>
