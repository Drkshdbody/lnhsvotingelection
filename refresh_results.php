<?php
header('Content-Type: application/json');

$db = new SQLite3('election.db');

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_classification' => 'Small',
    'school_level' => 'Junior High School'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
    $school_class = $settings['school_classification'];
    $school_level = $settings['school_level'];
} else {
    $school_class = 'Small';
    $school_level = 'Junior High School';
}

// Build position list dynamically based on school level and classification
$filtered_positions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Protocol Officer'
];

// Add grade-specific representatives based on school level
switch ($school_level) {
    case 'Elementary':
        if (in_array($school_class, ['Small', 'Medium'])) {
            $filtered_positions[] = 'Grade 6 Representative';
            $filtered_positions[] = 'Grade 5 Representative';
            $filtered_positions[] = 'Grade 4 Representative';
            $filtered_positions[] = 'Grade 3 Representative';
        } else {
            $filtered_positions[] = 'Grade 6 Representative 1';
            $filtered_positions[] = 'Grade 6 Representative 2';
            $filtered_positions[] = 'Grade 5 Representative 1';
            $filtered_positions[] = 'Grade 5 Representative 2';
            $filtered_positions[] = 'Grade 4 Representative 1';
            $filtered_positions[] = 'Grade 4 Representative 2';
            $filtered_positions[] = 'Grade 3 Representative 1';
            $filtered_positions[] = 'Grade 3 Representative 2';
        }
        break;
        
    case 'Integrated School':
        if (in_array($school_class, ['Small', 'Medium'])) {
            $filtered_positions[] = 'Grade 12 Representative';
            $filtered_positions[] = 'Grade 11 Representative';
            $filtered_positions[] = 'Grade 10 Representative';
            $filtered_positions[] = 'Grade 9 Representative';
            $filtered_positions[] = 'Grade 8 Representative';
            $filtered_positions[] = 'Grade 7 Representative';
        } else {
            $filtered_positions[] = 'Grade 12 Representative 1';
            $filtered_positions[] = 'Grade 12 Representative 2';
            $filtered_positions[] = 'Grade 11 Representative 1';
            $filtered_positions[] = 'Grade 11 Representative 2';
            $filtered_positions[] = 'Grade 10 Representative 1';
            $filtered_positions[] = 'Grade 10 Representative 2';
            $filtered_positions[] = 'Grade 9 Representative 1';
            $filtered_positions[] = 'Grade 9 Representative 2';
            $filtered_positions[] = 'Grade 8 Representative 1';
            $filtered_positions[] = 'Grade 8 Representative 2';
            $filtered_positions[] = 'Grade 7 Representative 1';
            $filtered_positions[] = 'Grade 7 Representative 2';
        }
        break;
        
    case 'Senior High School':
        if (in_array($school_class, ['Small', 'Medium'])) {
            $filtered_positions[] = 'Grade 12 Representative';
        } else {
            $filtered_positions[] = 'Grade 12 Representative 1';
            $filtered_positions[] = 'Grade 12 Representative 2';
        }
        break;
        
    case 'Junior High School':
    default:
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
        break;
}

// Build the query using string concatenation (safe since we control the values)
$position_placeholders = [];
foreach ($filtered_positions as $pos) {
    $position_placeholders[] = "'" . $db->escapeString($pos) . "'";
}
$position_list = implode(',', $position_placeholders);

// Build CASE statement for ordering
$case_statements = [
    "WHEN 'President' THEN 1",
    "WHEN 'Vice President' THEN 2", 
    "WHEN 'Secretary' THEN 3",
    "WHEN 'Treasurer' THEN 4",
    "WHEN 'Auditor' THEN 5",
    "WHEN 'Public Information Officer' THEN 6",
    "WHEN 'Protocol Officer' THEN 7"
];

// Add grade representative ordering
$grade_order = 8;
if ($school_level === 'Elementary') {
    $grades = ['Grade 6', 'Grade 5', 'Grade 4', 'Grade 3'];
} elseif ($school_level === 'Integrated School') {
    $grades = ['Grade 12', 'Grade 11', 'Grade 10', 'Grade 9', 'Grade 8', 'Grade 7'];
} elseif ($school_level === 'Senior High School') {
    $grades = ['Grade 12'];
} else { // Junior High School
    $grades = ['Grade 10', 'Grade 9', 'Grade 8'];
}

foreach ($grades as $grade) {
    if (in_array($school_class, ['Small', 'Medium'])) {
        $case_statements[] = "WHEN '{$grade} Representative' THEN {$grade_order}";
        $grade_order++;
    } else {
        $case_statements[] = "WHEN '{$grade} Representative 1' THEN {$grade_order}";
        $case_statements[] = "WHEN '{$grade} Representative 2' THEN " . ($grade_order + 1);
        $grade_order += 2;
    }
}

$case_order = implode("\n        ", $case_statements);

$sql = "SELECT position, name, party, vote_count, photo_path FROM candidates WHERE position IN ($position_list) ORDER BY 
    CASE position
        $case_order
        ELSE 99
    END ASC, vote_count DESC";

$result = $db->query($sql);

$data = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $data[] = $row;
}

echo json_encode($data);
?>