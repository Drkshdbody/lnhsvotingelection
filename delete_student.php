<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$student_id = $_GET['id'] ?? $_POST['id'] ?? '';

if (empty($student_id)) {
    header("Location: view_students.php");
    exit;
}

// Check if security is verified for this action
$action_key = 'delete_student';
$verified = isset($_SESSION["security_verified_$action_key"]) && 
           (time() - $_SESSION["security_verified_time"] < 300); // 5 minutes timeout

if (!$verified) {
    header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF'] . '?id=' . $student_id));
    exit;
}

$db = new SQLite3('election.db');

// Get student data
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bindValue(1, $student_id);
$result = $stmt->execute();
$student = $result->fetchArray();

if (!$student) {
    header("Location: view_students.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if student has voted
    if ($student['has_voted'] == 1) {
        $message = "Cannot delete student who has already voted.";
    } else {
        // Delete student
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bindValue(1, $student_id);
        
        if ($stmt->execute()) {
            $message = "Student deleted successfully!";
            
            // Clear verification after successful operation
            unset($_SESSION["security_verified_$action_key"]);
            unset($_SESSION['security_verified_time']);
        } else {
            $message = "Error deleting student.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Student - School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 500px; margin: 50px auto; }
        .student-display {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .student-info {
            flex: 1;
        }
        .student-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #2c3e50;
        }
        .student-details {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .student-voted {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            display: inline-block;
            margin-top: 5px;
        }
        .student-not-voted {
            background: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            display: inline-block;
            margin-top: 5px;
        }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        button:hover { background-color: #c82333; }
        .back-link { margin-top: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }
        .cancel-btn {
            background-color: #6c757d;
        }
        .cancel-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Delete Student</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($message)): ?>
            <div class="warning">
                <strong>Warning:</strong> This action cannot be undone. The student will be permanently removed from the system.
            </div>
            
            <!-- Display current student info -->
            <div class="student-display">
                <div class="student-info">
                    <div class="student-name"><?= htmlspecialchars($student['last_name']) . ', ' . htmlspecialchars($student['given_name']) . ' ' . htmlspecialchars($student['middle_name']) ?></div>
                    <div class="student-details">
                        LRN: <?= htmlspecialchars($student['lrn']) ?><br>
                        Grade: <?= $student['grade_level'] ?>-<?= htmlspecialchars($student['section']) ?><br>
                        Sex: <?= htmlspecialchars($student['sex']) ?>
                    </div>
                    <?php if ($student['has_voted'] == 1): ?>
                        <div class="student-voted">HAS VOTED</div>
                    <?php else: ?>
                        <div class="student-not-voted">NOT VOTED</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($student['has_voted'] == 1): ?>
                <div class="error">
                    Cannot delete student who has already voted.
                </div>
                <a href="view_students.php">
                    <button type="button" class="cancel-btn">Back to All Students</button>
                </a>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                    <p>Are you sure you want to delete this student?</p>
                    <button type="submit">Yes, Delete Student</button>
                    <a href="view_students.php">
                        <button type="button" class="cancel-btn">Cancel</button>
                    </a>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <a href="view_students.php">
                <button class="cancel-btn">Back to All Students</button>
            </a>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>