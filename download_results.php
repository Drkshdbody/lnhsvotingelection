<?php
$password = $_POST['password'] ?? '';

if ($password !== 'admin123') {
    header("Location: admin_login.php");
    exit;
}

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_name' => 'Sample High School',
    'school_id' => 'SHS-2026',
    'principal' => 'Dr. Juan Santos',
    'logo_path' => 'logo.png'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
} else {
    $settings = $default_settings;
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

$html = "
<!DOCTYPE html>
<html>
<head>
    <title>Election Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .school-info { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='school-info'>
        <h1>" . htmlspecialchars($settings['school_name']) . "</h1>
        <p>School ID: " . htmlspecialchars($settings['school_id']) . " | Principal: " . htmlspecialchars($settings['principal']) . "</p>
        <h2>Student Election Results</h2>
        <p>Generated on: " . date('Y-m-d H:i:s') . "</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Name</th>
                <th>Party</th>
                <th>Votes</th>
            </tr>
        </thead>
        <tbody>
";

while ($row = $result->fetchArray()) {
    $html .= "<tr>
        <td>" . htmlspecialchars($row['position']) . "</td>
        <td>" . htmlspecialchars($row['name']) . "</td>
        <td>" . htmlspecialchars($row['party'] ?? 'N/A') . "</td>
        <td>" . $row['vote_count'] . "</td>
    </tr>";
}

$html .= "
        </tbody>
    </table>
</body>
</html>
";

header('Content-Type: application/html');
header('Content-Disposition: attachment; filename="election_results_' . date('Y-m-d_H-i-s') . '.html"');
echo $html;
?>