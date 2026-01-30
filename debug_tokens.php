<?php
// Debug file to check token structure
$db = new SQLite3('election.db');

// Check first few students
$result = $db->query("SELECT lrn, login_token, has_voted FROM students LIMIT 5");
echo "<h2>Sample Student Tokens:</h2>";
echo "<pre>";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    print_r($row);
}
echo "</pre>";

// Check if table exists and structure
$result = $db->query("PRAGMA table_info(students)");
echo "<h2>Students Table Structure:</h2>";
echo "<pre>";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    print_r($row);
}
echo "</pre>";
?>