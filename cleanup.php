<?php
// NOTE: Only run this to clear jobs - do not use as job 13 is what is used for example.php
$data_dir = '/home/s2837201/public_html/ICA/data/';
$dirs = glob($data_dir . 'job_*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    array_map('unlink', glob($dir . '/*'));
    rmdir($dir);
}
echo 'Done. Removed ' . count($dirs) . ' directories.';
