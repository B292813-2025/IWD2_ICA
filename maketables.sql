CREATE database if not exists s2837201_ICA ;
use s2837201_ICA ;

DROP TABLE IF EXISTS motif_results;
DROP TABLE IF EXISTS analysis;
DROP TABLE IF EXISTS sequences;
DROP TABLE IF EXISTS jobs;

-- one row per analysis 

CREATE TABLE jobs (
job_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
session_id VARCHAR(64)  NOT NULL,
protein VARCHAR(255) NOT NULL,
taxon VARCHAR(255) NOT NULL,
max_seqs INT NOT NULL,
n_returned INT NOT NULL DEFAULT 0,
do_align INT NOT NULL DEFAULT 0,
do_motif INT NOT NULL DEFAULT 0,
do_blast INT NOT NULL DEFAULT 0,
do_pymol INT NOT NULL DEFAULT 0,
do_tree INT NOT NULL DEFAULT 0,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
is_example INT NOT NULL DEFAULT 0
);

-- 1 row for each sequence retrieved by NCBI 

CREATE TABLE sequences (
seq_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
job_id INT UNSIGNED NOT NULL,
accession VARCHAR(64)  NOT NULL,
description VARCHAR(255),
species VARCHAR(255),
seq_length INT,
fasta_data MEDIUMTEXT,
FOREIGN KEY (job_id) REFERENCES jobs(job_id)
);

-- analysis 

CREATE TABLE analysis (
analysis_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
job_id INT UNSIGNED NOT NULL,
analysis_type VARCHAR(100) NOT NULL,
output_file VARCHAR(255) NOT NULL,
status VARCHAR(50) NOT NULL DEFAULT "Pending",
run_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (job_id) REFERENCES jobs(job_id)
);

-- motif results 

CREATE TABLE motif_results (
motif_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
job_id INT UNSIGNED NOT NULL,
seq_id INT UNSIGNED NOT NULL,
motif_name VARCHAR(255),
start_pos INT,
end_pos INT,
score FLOAT NOT NULL,
FOREIGN KEY (job_id) REFERENCES jobs(job_id),
FOREIGN KEY (seq_id) REFERENCES sequences(seq_id)
);

-- to speed things up 

-- Find all jobs for a given session (history.php) 

CREATE INDEX idx_jobs_session ON jobs(session_id);

-- Find all sequences for a given job (results.php) 

CREATE INDEX idx_sequences_job ON sequences(job_id);

-- Find all motif hits for a given job (results.php) 

CREATE INDEX idx_motifs_job ON motif_results(job_id);

-- Find the example dataset quickly (example.php) 

CREATE INDEX idx_jobs_example ON jobs(is_example);

SHOW TABLES;
