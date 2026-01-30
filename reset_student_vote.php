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

// Check if security is verified for this action
$action_key = 'reset_student_vote';
$verified = isset($_SESSION["security_verified_$action_key"]) && 
           (time() - $_SESSION["security_verified_time"] < 300); // 5 minutes timeout

if (!$verified) {
    header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF']));
    exit;
}

$message = '';
$student_info = null;

if (isset($_POST['reset_token'])) {
    $token = trim($_POST['reset_token']);
    
    $db = new SQLite3('election.db');
    
    // Find student by token
    $stmt = $db->prepare("SELECT * FROM students WHERE login_token = ?");
    $stmt->bindValue(1, $token);
    $result = $stmt->execute();
    $student = $result->fetchArray();
    
    if ($student) {
        if ($student['has_voted'] == 1) {
            // Reset the vote
            $stmt = $db->prepare("UPDATE students SET has_voted = 0 WHERE login_token = ?");
            $stmt->bindValue(1, $token);
            $stmt->execute();
            
            // Also remove votes from votes table for this student
            $stmt = $db->prepare("DELETE FROM votes WHERE student_lrn = ?");
            $stmt->bindValue(1, $student['lrn']);
            $stmt->execute();
            
            // Reset candidate vote counts
            $stmt = $db->prepare("UPDATE candidates SET vote_count = 0 WHERE id IN (SELECT candidate_id FROM votes WHERE student_lrn = ?)");
            $stmt->bindValue(1, $student['lrn']);
            $stmt->execute();
            
            $message = "Student vote reset successfully! Student can vote again.";
            
            // Clear verification after successful operation
            unset($_SESSION["security_verified_$action_key"]);
            unset($_SESSION['security_verified_time']);
        } else {
            $message = "Student has not voted yet. No need to reset.";
        }
    } else {
        $message = "Invalid token. Student not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Reset Student Vote</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, button { padding: 8px; margin: 5px; }
        input { width: 300px; }
        button { background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .back-link { margin-top: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-box {
            background-color: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Student Vote</h2>
        
        <div class="info-box">
            <strong>Warning:</strong> This will reset the student's vote status, allowing them to vote again. Use carefully!
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="reset_token">Enter Student Token to Reset Vote:</label>
                <input type="text" name="reset_token" id="reset_token" placeholder="Enter login token" required>
            </div>
            <button type="submit">Reset Vote Status</button>
        </form>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>