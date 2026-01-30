<?php
$password = $_POST['password'] ?? '';

if ($password !== 'admin123') {
    header("Location: admin_login.php");
    exit;
}

$db = new SQLite3('election.db');

$sql = "SELECT position, name, party, vote_count FROM candidates ORDER BY 
    CASE position
        WHEN 'President' THEN 1
        WHEN 'Vice President' THEN 2
        WHEN 'Secretary' THEN 3
        WHEN 'Treasurer' THEN 4
        WHEN 'Auditor' THEN 5
        WHEN 'Public Information Officer' THEN 6
        WHEN 'Protocol Officer' THEN 7
        WHEN 'Grade 10 Representative' THEN 8
        WHEN 'Grade 9 Representative' THEN 9
        WHEN 'Grade 8 Representative' THEN 10
        ELSE 99
    END ASC, vote_count DESC";

$result = $db->query($sql);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="election_results_' . date('Y-m-d_H-i-s') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Position', 'Name', 'Party', 'Votes']);

while ($row = $result->fetchArray()) {
    fputcsv($output, [$row['position'], $row['name'], $row['party'] ?? 'N/A', $row['vote_count']]);
}

fclose($output);
?>