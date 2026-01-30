<?php
header('Content-Type: text/html');

$db = new SQLite3('election.db');

echo "<h2>Debug Info</h2>";

// Check if election.db exists
if (!file_exists('election.db')) {
    echo "<p>❌ ERROR: election.db file not found!</p>";
    exit;
} else {
    echo "<p>✅ election.db file exists</p>";
}

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_classification' => 'Small'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
    $school_class = $settings['school_classification'];
    echo "<p>✅ School Classification: $school_class</p>";
} else {
    $school_class = 'Small';
    echo "<p>⚠️ Settings file not found, using default: Small</p>";
}

// Get all positions from database
echo "<h3>All Positions in Database:</h3>";
$result = $db->query("SELECT DISTINCT position FROM candidates ORDER BY position");
$all_positions = [];
while ($row = $result->fetchArray()) {
    $all_positions[] = $row['position'];
    echo "- {$row['position']}<br>";
}

// Filter positions based on school classification
$filtered_positions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Protocol Officer'
];

if (in_array($school_class, ['Small', 'Medium'])) {
    $filtered_positions[] = 'Grade 10 Representative';
    $filtered_positions[] = 'Grade 9 Representative';
    $filtered_positions[] = 'Grade 8 Representative';
} else {
    $filtered_positions[] = 'Grade 10 Representative 1';
    $filtered_positions[] = 'Grade 10 Representative 2';
    $filtered_positions[] = 'Grade 9 Representative 1';
    $filtered_positions[] = 'Grade 9 Representative 2';
    $filtered_positions[] = 'Grade 8 Representative 1';
    $filtered_positions[] = 'Grade 8 Representative 2';
}

echo "<h3>Filtered Positions (based on classification):</h3>";
foreach ($filtered_positions as $pos) {
    echo "- $pos<br>";
}

// Try to execute the query
$placeholders = str_repeat('?,', count($filtered_positions) - 1) . '?';
$sql = "SELECT position, name, party, vote_count FROM candidates WHERE position IN ($placeholders)";

echo "<h3>SQL Query:</h3>";
echo "<pre>$sql</pre>";

$stmt = $db->prepare($sql);
$result = $stmt->execute($filtered_positions);

echo "<h3>Query Results:</h3>";
$data = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $data[] = $row;
    echo "Position: {$row['position']}, Name: {$row['name']}, Votes: {$row['vote_count']}<br>";
}

echo "<h3>Final JSON Output:</h3>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
?>