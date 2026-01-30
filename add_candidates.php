<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $password = $_POST['password'] ?? '';
    if ($password !== 'admin123') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION['admin_logged_in'] = true;
}

// Load school settings
$settings_file = 'school_settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $school_level = $settings['school_level'];
    $school_class = $settings['school_classification'];
    
    // Determine system title based on school level
    if ($school_level === 'Elementary') {
        $system_title = "Supreme Elementary Learner Government Election System";
    } else {
        $system_title = "Supreme Secondary Learner Government Election System";
    }
} else {
    $school_level = 'Junior High School';
    $school_class = 'Small';
    $system_title = "Supreme Secondary Learner Government Election System";
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = $_POST['position'];
    $student_id = $_POST['student_id'];
    $party = trim($_POST['party'] ?? '');
    
    // If new party was entered, use that instead
    $new_party = trim($_POST['new_party'] ?? '');
    if (!empty($new_party)) {
        $party = $new_party;
    }

    $db = new SQLite3('election.db');
    
    // Get student info
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bindValue(1, $student_id);
    $result = $stmt->execute();
    $student = $result->fetchArray();
    
    if ($student) {
        // Check if candidate already exists for ANY position (not just this position)
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM candidates WHERE name = ?");
        $check_stmt->bindValue(1, $student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']);
        $exists = $check_stmt->execute()->fetchArray()[0];
        
        if ($exists > 0) {
            $message = "Candidate already exists in the election and cannot be added again.";
        } else {
            // Check if student meets grade requirements for position
            $eligible = false;
            
            switch ($school_level) {
                case 'Elementary':
                    switch ($position) {
                        case 'President':
                            $eligible = in_array($student['grade_level'], [4, 5]);
                            break;
                        case 'Vice President':
                            $eligible = in_array($student['grade_level'], [4, 5]);
                            break;
                        case 'Secretary':
                            $eligible = in_array($student['grade_level'], [2, 3, 4, 5]);
                            break;
                        case 'Treasurer':
                            $eligible = in_array($student['grade_level'], [2, 3, 4, 5]);
                            break;
                        case 'Auditor':
                            $eligible = in_array($student['grade_level'], [2, 3, 4, 5]);
                            break;
                        case 'Public Information Officer':
                            $eligible = in_array($student['grade_level'], [2, 3, 4, 5]);
                            break;
                        case 'Protocol Officer':
                            $eligible = in_array($student['grade_level'], [2, 3, 4, 5]);
                            break;
                        case 'Grade 6 Representative':
                        case 'Grade 6 Representative 1':
                        case 'Grade 6 Representative 2':
                            $eligible = $student['grade_level'] == 5;
                            break;
                        case 'Grade 5 Representative':
                        case 'Grade 5 Representative 1':
                        case 'Grade 5 Representative 2':
                            $eligible = $student['grade_level'] == 4;
                            break;
                        case 'Grade 4 Representative':
                        case 'Grade 4 Representative 1':
                        case 'Grade 4 Representative 2':
                            $eligible = $student['grade_level'] == 3;
                            break;
                        case 'Grade 3 Representative':
                        case 'Grade 3 Representative 1':
                        case 'Grade 3 Representative 2':
                            $eligible = $student['grade_level'] == 2;
                            break;
                        default:
                            $eligible = false;
                    }
                    break;
                    
                case 'Integrated School':
                    switch ($position) {
                        case 'President':
                            // Current Grade 10 (Incoming 11) or Grade 11 (Incoming 12)
                            $eligible = in_array($student['grade_level'], [10, 11]);
                            break;
                        case 'Vice President':
                            // Current Grade 10 (Incoming 11) or Grade 11 (Incoming 12)
                            $eligible = in_array($student['grade_level'], [10, 11]);
                            break;
                        case 'Secretary':
                            // Current Grade 7-11 (Incoming 8-12)
                            $eligible = in_array($student['grade_level'], [7, 8, 9, 10, 11]);
                            break;
                        case 'Treasurer':
                            // Current Grade 7-11 (Incoming 8-12)
                            $eligible = in_array($student['grade_level'], [7, 8, 9, 10, 11]);
                            break;
                        case 'Auditor':
                            // Current Grade 7-11 (Incoming 8-12)
                            $eligible = in_array($student['grade_level'], [7, 8, 9, 10, 11]);
                            break;
                        case 'Public Information Officer':
                            // Current Grade 7-11 (Incoming 8-12)
                            $eligible = in_array($student['grade_level'], [7, 8, 9, 10, 11]);
                            break;
                        case 'Protocol Officer':
                            // Current Grade 7-11 (Incoming 8-12)
                            $eligible = in_array($student['grade_level'], [7, 8, 9, 10, 11]);
                            break;
                        case 'Grade 12 Representative':
                        case 'Grade 12 Representative 1':
                        case 'Grade 12 Representative 2':
                            // Current Grade 11 (Incoming 12)
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Grade 11 Representative':
                        case 'Grade 11 Representative 1':
                        case 'Grade 11 Representative 2':
                            // Current Grade 10 (Incoming 11)
                            $eligible = $student['grade_level'] == 10;
                            break;
                        case 'Grade 10 Representative':
                        case 'Grade 10 Representative 1':
                        case 'Grade 10 Representative 2':
                            // Current Grade 9 (Incoming 10)
                            $eligible = $student['grade_level'] == 9;
                            break;
                        case 'Grade 9 Representative':
                        case 'Grade 9 Representative 1':
                        case 'Grade 9 Representative 2':
                            // Current Grade 8 (Incoming 9)
                            $eligible = $student['grade_level'] == 8;
                            break;
                        case 'Grade 8 Representative':
                        case 'Grade 8 Representative 1':
                        case 'Grade 8 Representative 2':
                            // Current Grade 7 (Incoming 8)
                            $eligible = $student['grade_level'] == 7;
                            break;
                        default:
                            $eligible = false;
                    }
                    break;
                    
                case 'Senior High School':
                    switch ($position) {
                        case 'President':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Vice President':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Secretary':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Treasurer':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Auditor':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Public Information Officer':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Protocol Officer':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        case 'Grade 12 Representative':
                        case 'Grade 12 Representative 1':
                        case 'Grade 12 Representative 2':
                            $eligible = $student['grade_level'] == 11;
                            break;
                        default:
                            $eligible = false;
                    }
                    break;
                    
                case 'Junior High School':
                default:
                    switch ($position) {
                        case 'President':
                            $eligible = in_array($student['grade_level'], [8, 9]);
                            break;
                        case 'Vice President':
                            $eligible = in_array($student['grade_level'], [8, 9]);
                            break;
                        case 'Secretary':
                            $eligible = in_array($student['grade_level'], [7, 8, 9]);
                            break;
                        case 'Treasurer':
                            $eligible = in_array($student['grade_level'], [7, 8, 9]);
                            break;
                        case 'Auditor':
                            $eligible = in_array($student['grade_level'], [7, 8, 9]);
                            break;
                        case 'Public Information Officer':
                            $eligible = in_array($student['grade_level'], [7, 8, 9]);
                            break;
                        case 'Protocol Officer':
                            $eligible = in_array($student['grade_level'], [7, 8, 9]);
                            break;
                        case 'Grade 10 Representative':
                        case 'Grade 10 Representative 1':
                        case 'Grade 10 Representative 2':
                            $eligible = $student['grade_level'] == 9;
                            break;
                        case 'Grade 9 Representative':
                        case 'Grade 9 Representative 1':
                        case 'Grade 9 Representative 2':
                            $eligible = $student['grade_level'] == 8;
                            break;
                        case 'Grade 8 Representative':
                        case 'Grade 8 Representative 1':
                        case 'Grade 8 Representative 2':
                            $eligible = $student['grade_level'] == 7;
                            break;
                        default:
                            $eligible = false;
                    }
            }
            
            if ($eligible) {
                $photo_path = null;
                
                // Handle photo upload
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "candidates_photos/";
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $imageFileType = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $target_file = $target_dir . "candidate_" . $student_id . "_" . time() . "." . $imageFileType;

                    // Check if image file is actual image
                    $check = getimagesize($_FILES['photo']['tmp_name']);
                    if ($check !== false) {
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                            $photo_path = $target_file;
                        } else {
                            $message = "Error uploading photo.";
                        }
                    } else {
                        $message = "File is not an image.";
                    }
                }

                // Insert candidate using student info
                if (empty($message)) {
                    $stmt = $db->prepare("INSERT INTO candidates (position, name, party, photo_path) VALUES (?, ?, ?, ?)");
                    $stmt->bindValue(1, $position);
                    $stmt->bindValue(2, $student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']);
                    $stmt->bindValue(3, $party);
                    $stmt->bindValue(4, $photo_path);

                    if ($stmt->execute()) {
                        $message = "Candidate added successfully!";
                    } else {
                        $message = "Error adding candidate.";
                    }
                }
            } else {
                $message = "Student does not meet grade requirements for this position.";
            }
        }
    } else {
        $message = "Student not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($system_title) ?> - Add Candidate</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 120px; }
        input, select { padding: 5px; width: 250px; }
        button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .info-box {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .back-link {
            margin-top: 20px;
        }
        .qualification-info {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .party-section {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        .photo-section {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($system_title) ?> - Add Candidate from Registered Students</h2>
    
    <div class="info-box">
        <strong>School Level:</strong> <?= htmlspecialchars($school_level) ?><br>
        <strong>Classification:</strong> <?= htmlspecialchars($school_class) ?>
    </div>
    
    <div class="qualification-info">
        <strong>Grade Requirements:</strong>
        <?php
        switch ($school_level) {
            case 'Elementary':
                echo "<ul>";
                echo "<li><strong>President:</strong> Incoming Grade 5, 6</li>";
                echo "<li><strong>Vice President:</strong> Incoming Grade 5, 6</li>";
                echo "<li><strong>Secretary:</strong> Incoming Grade 3, 4, 5, 6</li>";
                echo "<li><strong>Treasurer:</strong> Incoming Grade 3, 4, 5, 6</li>";
                echo "<li><strong>Auditor:</strong> Incoming Grade 3, 4, 5, 6</li>";
                echo "<li><strong>Public Information Officer:</strong> Incoming Grade 3, 4, 5, 6</li>";
                echo "<li><strong>Protocol Officer:</strong> Incoming Grade 3, 4, 5, 6</li>";
                echo "<li><strong>Grade 6 Representative:</strong> Incoming Grade 6</li>";
                echo "<li><strong>Grade 5 Representative:</strong> Incoming Grade 5</li>";
                echo "<li><strong>Grade 4 Representative:</strong> Incoming Grade 4</li>";
                echo "<li><strong>Grade 3 Representative:</strong> Incoming Grade 3</li>";
                echo "</ul>";
                break;
            case 'Integrated School':
                echo "<ul>";
                echo "<li><strong>President:</strong> Incoming Grade 11 or 12</li>";
                echo "<li><strong>Vice President:</strong> Incoming Grade 11 or 12</li>";
                echo "<li><strong>Secretary:</strong> Incoming Grade 8-12</li>";
                echo "<li><strong>Treasurer:</strong> Incoming Grade 8-12</li>";
                echo "<li><strong>Auditor:</strong> Incoming Grade 8-12</li>";
                echo "<li><strong>Public Information Officer:</strong> Incoming Grade 8-12</li>";
                echo "<li><strong>Protocol Officer:</strong> Incoming Grade 8-12</li>";
                echo "<li><strong>Grade 12 Representative:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Grade 11 Representative:</strong> Incoming Grade 11</li>";
                echo "<li><strong>Grade 10 Representative:</strong> Incoming Grade 10</li>";
                echo "<li><strong>Grade 9 Representative:</strong> Incoming Grade 9</li>";
                echo "<li><strong>Grade 8 Representative:</strong> Incoming Grade 8</li>";
                echo "</ul>";
                break;
            case 'Senior High School':
                echo "<ul>";
                echo "<li><strong>President:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Vice President:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Secretary:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Treasurer:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Auditor:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Public Information Officer:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Protocol Officer:</strong> Incoming Grade 12</li>";
                echo "<li><strong>Grade 12 Representative:</strong> Incoming Grade 12</li>";
                echo "</ul>";
                break;
            case 'Junior High School':
                echo "<ul>";
                echo "<li><strong>President:</strong> Incoming Grade 9, 10</li>";
                echo "<li><strong>Vice President:</strong> Incoming Grade 9, 10</li>";
                echo "<li><strong>Secretary:</strong> Incoming Grade 8, 9, 10</li>";
                echo "<li><strong>Treasurer:</strong> Incoming Grade 8, 9, 10</li>";
                echo "<li><strong>Auditor:</strong> Incoming Grade 8, 9, 10</li>";
                echo "<li><strong>Public Information Officer:</strong> Incoming Grade 8, 9, 10</li>";
                echo "<li><strong>Protocol Officer:</strong> Incoming Grade 8, 9, 10</li>";
                echo "<li><strong>Grade 10 Representative:</strong> Incoming Grade 10</li>";
                echo "<li><strong>Grade 9 Representative:</strong> Incoming Grade 9</li>";
                echo "<li><strong>Grade 8 Representative:</strong> Incoming Grade 8</li>";
                echo "</ul>";
                break;
        }
        ?>
    </div>
    
    <?php if ($message) echo "<p style='color: green;'>$message</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Position:</label>
            <select name="position" required onchange="filterStudents()">
                <option value="">Select Position</option>
                <?php
                // Show positions based on school level
                switch ($school_level) {
                    case 'Elementary':
                        echo '<option value="President">President</option>';
                        echo '<option value="Vice President">Vice President</option>';
                        echo '<option value="Secretary">Secretary</option>';
                        echo '<option value="Treasurer">Treasurer</option>';
                        echo '<option value="Auditor">Auditor</option>';
                        echo '<option value="Public Information Officer">Public Information Officer</option>';
                        echo '<option value="Protocol Officer">Protocol Officer</option>';
                        
                        if (in_array($school_class, ['Small', 'Medium'])) {
                            echo '<option value="Grade 6 Representative">Grade 6 Representative</option>';
                            echo '<option value="Grade 5 Representative">Grade 5 Representative</option>';
                            echo '<option value="Grade 4 Representative">Grade 4 Representative</option>';
                            echo '<option value="Grade 3 Representative">Grade 3 Representative</option>';
                        } else {
                            echo '<option value="Grade 6 Representative 1">Grade 6 Representative 1</option>';
                            echo '<option value="Grade 6 Representative 2">Grade 6 Representative 2</option>';
                            echo '<option value="Grade 5 Representative 1">Grade 5 Representative 1</option>';
                            echo '<option value="Grade 5 Representative 2">Grade 5 Representative 2</option>';
                            echo '<option value="Grade 4 Representative 1">Grade 4 Representative 1</option>';
                            echo '<option value="Grade 4 Representative 2">Grade 4 Representative 2</option>';
                            echo '<option value="Grade 3 Representative 1">Grade 3 Representative 1</option>';
                            echo '<option value="Grade 3 Representative 2">Grade 3 Representative 2</option>';
                        }
                        break;
                    case 'Junior High School':
                        echo '<option value="President">President</option>';
                        echo '<option value="Vice President">Vice President</option>';
                        echo '<option value="Secretary">Secretary</option>';
                        echo '<option value="Treasurer">Treasurer</option>';
                        echo '<option value="Auditor">Auditor</option>';
                        echo '<option value="Public Information Officer">Public Information Officer</option>';
                        echo '<option value="Protocol Officer">Protocol Officer</option>';
                        
                        if (in_array($school_class, ['Small', 'Medium'])) {
                            echo '<option value="Grade 10 Representative">Grade 10 Representative</option>';
                            echo '<option value="Grade 9 Representative">Grade 9 Representative</option>';
                            echo '<option value="Grade 8 Representative">Grade 8 Representative</option>';
                        } else {
                            echo '<option value="Grade 10 Representative 1">Grade 10 Representative 1</option>';
                            echo '<option value="Grade 10 Representative 2">Grade 10 Representative 2</option>';
                            echo '<option value="Grade 9 Representative 1">Grade 9 Representative 1</option>';
                            echo '<option value="Grade 9 Representative 2">Grade 9 Representative 2</option>';
                            echo '<option value="Grade 8 Representative 1">Grade 8 Representative 1</option>';
                            echo '<option value="Grade 8 Representative 2">Grade 8 Representative 2</option>';
                        }
                        break;
                    case 'Integrated School':
                        echo '<option value="President">President</option>';
                        echo '<option value="Vice President">Vice President</option>';
                        echo '<option value="Secretary">Secretary</option>';
                        echo '<option value="Treasurer">Treasurer</option>';
                        echo '<option value="Auditor">Auditor</option>';
                        echo '<option value="Public Information Officer">Public Information Officer</option>';
                        echo '<option value="Protocol Officer">Protocol Officer</option>';
                        
                        if (in_array($school_class, ['Small', 'Medium'])) {
                            echo '<option value="Grade 12 Representative">Grade 12 Representative</option>';
                            echo '<option value="Grade 11 Representative">Grade 11 Representative</option>';
                            echo '<option value="Grade 10 Representative">Grade 10 Representative</option>';
                            echo '<option value="Grade 9 Representative">Grade 9 Representative</option>';
                            echo '<option value="Grade 8 Representative">Grade 8 Representative</option>';
                        } else {
                            echo '<option value="Grade 12 Representative 1">Grade 12 Representative 1</option>';
                            echo '<option value="Grade 12 Representative 2">Grade 12 Representative 2</option>';
                            echo '<option value="Grade 11 Representative 1">Grade 11 Representative 1</option>';
                            echo '<option value="Grade 11 Representative 2">Grade 11 Representative 2</option>';
                            echo '<option value="Grade 10 Representative 1">Grade 10 Representative 1</option>';
                            echo '<option value="Grade 10 Representative 2">Grade 10 Representative 2</option>';
                            echo '<option value="Grade 9 Representative 1">Grade 9 Representative 1</option>';
                            echo '<option value="Grade 9 Representative 2">Grade 9 Representative 2</option>';
                            echo '<option value="Grade 8 Representative 1">Grade 8 Representative 1</option>';
                            echo '<option value="Grade 8 Representative 2">Grade 8 Representative 2</option>';
                        }
                        break;
                    case 'Senior High School':
                        echo '<option value="President">President</option>';
                        echo '<option value="Vice President">Vice President</option>';
                        echo '<option value="Secretary">Secretary</option>';
                        echo '<option value="Treasurer">Treasurer</option>';
                        echo '<option value="Auditor">Auditor</option>';
                        echo '<option value="Public Information Officer">Public Information Officer</option>';
                        echo '<option value="Protocol Officer">Protocol Officer</option>';
                        
                        if (in_array($school_class, ['Small', 'Medium'])) {
                            echo '<option value="Grade 12 Representative">Grade 12 Representative</option>';
                        } else {
                            echo '<option value="Grade 12 Representative 1">Grade 12 Representative 1</option>';
                            echo '<option value="Grade 12 Representative 2">Grade 12 Representative 2</option>';
                        }
                        break;
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Student:</label>
            <select name="student_id" id="studentSelect" required>
                <option value="">Select Student</option>
                <?php
                $db = new SQLite3('election.db');
                $result = $db->query("SELECT id, last_name, given_name, middle_name, grade_level, section FROM students ORDER BY grade_level, section, last_name");
                while ($row = $result->fetchArray()) {
                    // Check if student is already a candidate in the whole election
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM candidates WHERE name = ?");
                    $check_stmt->bindValue(1, $row['last_name'] . ', ' . $row['given_name'] . ' ' . $row['middle_name']);
                    $candidate_exists = $check_stmt->execute()->fetchArray()[0];
                    
                    if ($candidate_exists == 0) {
                        echo "<option value='".$row['id']."' data-grade='".$row['grade_level']."'>";
                        echo htmlspecialchars($row['last_name'] . ", " . $row['given_name'] . " " . $row['middle_name']) . " (Grade " . $row['grade_level'] . "-" . $row['section'] . ")";
                        echo "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="party-section">
            <h3>Party Selection</h3>
            <div class="form-group">
                <label>Existing Party:</label>
                <select name="party">
                    <option value="">Select Existing Party</option>
                    <?php
                    // Get all existing parties
                    $parties_result = $db->query("SELECT DISTINCT party FROM candidates WHERE party IS NOT NULL AND party != '' ORDER BY party");
                    while ($party_row = $parties_result->fetchArray()) {
                        if (!empty($party_row['party'])) {
                            echo "<option value='".htmlspecialchars($party_row['party'])."'>".htmlspecialchars($party_row['party'])."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Or Create New Party:</label>
                <input type="text" name="new_party" placeholder="Enter new party name">
            </div>
        </div>

        <div class="photo-section">
            <h3>Photo Upload</h3>
            <div class="form-group">
                <label>Select Photo:</label>
                <input type="file" name="photo" accept="image/*">
            </div>
            <p style="font-size: 0.9em; color: #666;">Optional: Upload candidate photo</p>
        </div>

        <button type="submit">Add Candidate</button>
    </form>

    <div class="back-link">
        <a href="admin_panel.php">Back to Admin Panel</a>
    </div>

    <script>
        function filterStudents() {
            const positionSelect = document.querySelector('select[name="position"]');
            const studentSelect = document.getElementById('studentSelect');
            const selectedPosition = positionSelect.value;
            
            // Reset all options to visible
            const options = studentSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value !== '') {
                    option.style.display = '';
                }
            });
            
            if (!selectedPosition) return;
            
            // Define grade requirements based on school level
            const schoolLevel = "<?= $school_level ?>";
            let requiredGrades = [];
            
            switch (schoolLevel) {
                case 'Elementary':
                    switch (selectedPosition) {
                        case 'President':
                        case 'Vice President':
                            requiredGrades = [4, 5];
                            break;
                        case 'Secretary':
                        case 'Treasurer':
                        case 'Auditor':
                        case 'Public Information Officer':
                        case 'Protocol Officer':
                            requiredGrades = [2, 3, 4, 5];
                            break;
                        case 'Grade 6 Representative':
                        case 'Grade 6 Representative 1':
                        case 'Grade 6 Representative 2':
                            requiredGrades = [5];
                            break;
                        case 'Grade 5 Representative':
                        case 'Grade 5 Representative 1':
                        case 'Grade 5 Representative 2':
                            requiredGrades = [4];
                            break;
                        case 'Grade 4 Representative':
                        case 'Grade 4 Representative 1':
                        case 'Grade 4 Representative 2':
                            requiredGrades = [3];
                            break;
                        case 'Grade 3 Representative':
                        case 'Grade 3 Representative 1':
                        case 'Grade 3 Representative 2':
                            requiredGrades = [2];
                            break;
                    }
                    break;
                case 'Integrated School':
                    switch (selectedPosition) {
                        case 'President':
                        case 'Vice President':
                            requiredGrades = [10, 11]; // ✅ FIXED: Grade 10 and 11 now included
                            break;
                        case 'Secretary':
                        case 'Treasurer':
                        case 'Auditor':
                        case 'Public Information Officer':
                        case 'Protocol Officer':
                            requiredGrades = [7, 8, 9, 10, 11];
                            break;
                        case 'Grade 12 Representative':
                        case 'Grade 12 Representative 1':
                        case 'Grade 12 Representative 2':
                            requiredGrades = [11];
                            break;
                        case 'Grade 11 Representative':
                        case 'Grade 11 Representative 1':
                        case 'Grade 11 Representative 2':
                            requiredGrades = [10];
                            break;
                        case 'Grade 10 Representative':
                        case 'Grade 10 Representative 1':
                        case 'Grade 10 Representative 2':
                            requiredGrades = [9];
                            break;
                        case 'Grade 9 Representative':
                        case 'Grade 9 Representative 1':
                        case 'Grade 9 Representative 2':
                            requiredGrades = [8];
                            break;
                        case 'Grade 8 Representative':
                        case 'Grade 8 Representative 1':
                        case 'Grade 8 Representative 2':
                            requiredGrades = [7];
                            break;
                    }
                    break;
                case 'Senior High School':
                    switch (selectedPosition) {
                        case 'President':
                        case 'Vice President':
                        case 'Secretary':
                        case 'Treasurer':
                        case 'Auditor':
                        case 'Public Information Officer':
                        case 'Protocol Officer':
                        case 'Grade 12 Representative':
                        case 'Grade 12 Representative 1':
                        case 'Grade 12 Representative 2':
                            requiredGrades = [11];
                            break;
                    }
                    break;
                case 'Junior High School':
                default:
                    switch (selectedPosition) {
                        case 'President':
                        case 'Vice President':
                            requiredGrades = [8, 9];
                            break;
                        case 'Secretary':
                        case 'Treasurer':
                        case 'Auditor':
                        case 'Public Information Officer':
                        case 'Protocol Officer':
                            requiredGrades = [7, 8, 9];
                            break;
                        case 'Grade 10 Representative':
                        case 'Grade 10 Representative 1':
                        case 'Grade 10 Representative 2':
                            requiredGrades = [9];
                            break;
                        case 'Grade 9 Representative':
                        case 'Grade 9 Representative 1':
                        case 'Grade 9 Representative 2':
                            requiredGrades = [8];
                            break;
                        case 'Grade 8 Representative':
                        case 'Grade 8 Representative 1':
                        case 'Grade 8 Representative 2':
                            requiredGrades = [7];
                            break;
                    }
            }
            
            // Hide students who don't meet grade requirements
            options.forEach(option => {
                if (option.value !== '') {
                    const studentGrade = parseInt(option.getAttribute('data-grade'));
                    if (!requiredGrades.includes(studentGrade)) {
                        option.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>