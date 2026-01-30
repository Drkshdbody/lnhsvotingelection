<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$db = new SQLite3('election.db');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Delete all candidates
        $stmt = $db->prepare("DELETE FROM candidates");
        if ($stmt->execute()) {
            // Also delete related votes
            $stmt = $db->prepare("DELETE FROM votes");
            $stmt->execute();
            
            $message = "All candidates and their votes have been deleted successfully!";
        } else {
            $message = "Error deleting candidates.";
        }
    } elseif (isset($_POST['cancel_delete'])) {
        header("Location: admin_panel.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Delete All Candidates</title>
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
        <h2>Delete All Candidates</h2>
        
        <?php if ($message): ?>
            <div class="message success">
                <?= htmlspecialchars($message) ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_panel.php" class="btn btn-secondary">Back to Admin Panel</a>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>WARNING:</strong> This action will permanently delete ALL candidates and their associated votes.
            </div>
            
            <div class="confirm">
                <strong>CONFIRMATION:</strong> Are you absolutely sure you want to delete all candidates?
            </div>
            
            <p>This action cannot be undone. All candidate information and votes will be permanently removed.</p>
            
            <form method="POST" style="text-align: center; margin-top: 20px;">
                <input type="submit" name="confirm_delete" value="Yes, Delete All Candidates" class="btn btn-danger" style="background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                <input type="submit" name="cancel_delete" value="Cancel" class="btn btn-secondary" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>