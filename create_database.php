<?php
if (file_exists('election.db')) {
    die("Database already exists! Delete election.db first if you want to recreate it.<br><a href='admin_panel.php'>Go to Admin Panel</a>");
}

$db = new SQLite3('election.db');

// Create students table
$db->exec("CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lrn TEXT UNIQUE NOT NULL,
    last_name TEXT NOT NULL,
    middle_name TEXT,
    given_name TEXT NOT NULL,
    sex TEXT CHECK(sex IN ('Male', 'Female')),
    grade_level INTEGER NOT NULL,
    section TEXT NOT NULL,
    login_token TEXT UNIQUE NOT NULL,
    has_voted BOOLEAN DEFAULT 0
);");

// Create candidates table
$db->exec("CREATE TABLE candidates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    position TEXT NOT NULL,
    name TEXT NOT NULL,
    party TEXT,
    vote_count INTEGER DEFAULT 0
);");

// Create votes table
$db->exec("CREATE TABLE votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_lrn TEXT NOT NULL,
    candidate_id INTEGER NOT NULL,
    position TEXT NOT NULL,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (student_lrn) REFERENCES students(lrn)
);");

// Insert sample candidates for ALL positions (including dual representatives for Large/Mega)
$positions = [
    'President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor',
    'Public Information Officer', 'Protocol Officer',
    'Grade 10 Representative', 'Grade 9 Representative', 'Grade 8 Representative',
    'Grade 10 Representative 1', 'Grade 10 Representative 2',
    'Grade 9 Representative 1', 'Grade 9 Representative 2',
    'Grade 8 Representative 1', 'Grade 8 Representative 2'
];

foreach ($positions as $pos) {
    $stmt = $db->prepare("INSERT INTO candidates (position, name, party) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $pos);
    $stmt->bindValue(2, "Candidate for $pos");
    $stmt->bindValue(3, "Party $pos");
    $stmt->execute();
}

echo "Database created successfully with all positions!<br>";
echo "<a href='index.php'>Go to Student Login</a> | ";
echo "<a href='admin_panel.php'>Go to Admin Panel</a>";
?>