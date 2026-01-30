<?php
$db = new SQLite3('election.db');

// Check if photo_path column exists
$columns = $db->query("PRAGMA table_info(candidates)");
$columnExists = false;
while ($row = $columns->fetchArray()) {
    if ($row['name'] === 'photo_path') {
        $columnExists = true;
        break;
    }
}

if (!$columnExists) {
    // Add photo_path column
    $db->exec("ALTER TABLE candidates ADD COLUMN photo_path TEXT");
    echo "Database updated successfully! Added photo_path column to candidates table.";
} else {
    echo "Database is already updated. photo_path column exists.";
}

echo "<br><br><a href='admin_panel.php'>Go to Admin Panel</a>";
?>