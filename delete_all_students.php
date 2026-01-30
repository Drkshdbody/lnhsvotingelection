<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if security is verified for this action
$action_key = 'delete_all_students';
$verified = isset($_SESSION["security_verified_$action_key"]) && 
           (time() - $_SESSION["security_verified_time"] < 300); // 5 minutes timeout

if (!$verified) {
    header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF']));
    exit;
}

$db = new SQLite3('election.db');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Delete all students regardless of voting status
        $stmt = $db->prepare("DELETE FROM students");
        if ($stmt->execute()) {
            // Also delete related votes and reset candidate votes
            $stmt = $db->prepare("DELETE FROM votes");
            $stmt->execute();
            
            $stmt = $db->prepare("UPDATE candidates SET vote_count = 0");
            $stmt->execute();
            
            $message = "All students and their votes have been deleted successfully!";
            
            // Clear verification after successful operation
            unset($_SESSION["security_verified_$action_key"]);
            unset($_SESSION['security_verified_time']);
        } else {
            $message = "Error deleting students.";
        }
    } elseif (isset($_POST['cancel_delete'])) {
        // Clear verification on cancel
        unset($_SESSION["security_verified_$action_key"]);
        unset($_SESSION['security_verified_time']);
        
        header("Location: admin_panel.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Delete All Students</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            background-color: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 500px; 
            margin: 50px auto; 
        }
        .warning { 
            background-color: #fff3cd; 
            border: 1px solid #ffeaa7; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            color: #856404; 
        }
        .confirm { 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            color: #721c24; 
        }
        .action-buttons { 
            text-align: center; 
            margin-top: 20px; 
        }
        .btn { 
            padding: 10px 20px; 
            margin: 5px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-danger { 
            background-color: #dc3545; 
            color: white; 
        }
        .btn-secondary { 
            background-color: #6c757d; 
            color: white; 
        }
        .btn:hover { 
            opacity: 0.8; 
        }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px; 
        }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Delete All Students</h2>
        
        <?php if ($message): ?>
            <div class="message success">
                <?= htmlspecialchars($message) ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_panel.php" class="btn btn-secondary">Back to Admin Panel</a>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>WARNING:</strong> This action will permanently delete ALL students and their associated data.
            </div>
            
            <div class="confirm">
                <strong>CONFIRMATION:</strong> Are you absolutely sure you want to delete all students?
            </div>
            
            <p>This action cannot be undone. All student information, votes, and related data will be permanently removed.</p>
            
            <form method="POST" style="text-align: center; margin-top: 20px;">
                <input type="submit" name="confirm_delete" value="Yes, Delete All Students" class="btn btn-danger">
                <input type="submit" name="cancel_delete" value="Cancel" class="btn btn-secondary">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>