<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if security is verified for this action
$action_key = 'reset_system';
$verified = isset($_SESSION["security_verified_$action_key"]) && 
           (time() - $_SESSION["security_verified_time"] < 300); // 5 minutes timeout

if (!$verified) {
    header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF']));
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_reset'])) {
        try {
            // Close existing database connection
            if (isset($db)) {
                $db->close();
            }
            
            // Delete the entire database file
            if (file_exists('election.db')) {
                unlink('election.db');
            }
            
            // Recreate the database with fresh schema
            $db = new SQLite3('election.db');
            
            // Create students table
            $db->exec("CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lrn TEXT UNIQUE NOT NULL,
                last_name TEXT NOT NULL,
                given_name TEXT NOT NULL,
                middle_name TEXT,
                sex TEXT,
                grade_level INTEGER,
                section TEXT,
                login_token TEXT UNIQUE NOT NULL,
                has_voted INTEGER DEFAULT 0
            )");
            
            // Create candidates table
            $db->exec("CREATE TABLE candidates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                position TEXT NOT NULL,
                name TEXT NOT NULL,
                party TEXT,
                photo_path TEXT,
                vote_count INTEGER DEFAULT 0
            )");
            
            // Create votes table
            $db->exec("CREATE TABLE votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_lrn TEXT NOT NULL,
                candidate_id INTEGER NOT NULL,
                position TEXT NOT NULL
            )");
            
            // Create/Reset tokens file
            $tokens_file = 'election_tokens.json';
            $default_tokens = [
                'enabled' => false,
                'chief_commissioner' => bin2hex(random_bytes(8)),
                'screening_validation' => bin2hex(random_bytes(8)),
                'electoral_board' => bin2hex(random_bytes(8))
            ];
            file_put_contents($tokens_file, json_encode($default_tokens, JSON_PRETTY_PRINT));
            
            $message = "System has been completely reset! All data has been deleted and the system is ready for a new election.";
            
            // Clear verification after successful operation
            unset($_SESSION["security_verified_$action_key"]);
            unset($_SESSION['security_verified_time']);
            
        } catch (Exception $e) {
            $message = "Error resetting system: " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel_reset'])) {
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
    <title>School Learner Government Election System - Reset System</title>
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
            max-width: 600px; 
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
        .danger { 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            color: #721c24; 
        }
        .info-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #0c5460;
        }
        .action-buttons { 
            text-align: center; 
            margin-top: 20px; 
        }
        .btn { 
            padding: 12px 24px; 
            margin: 5px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            font-weight: bold;
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
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 4px; 
        }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>⚠️ RESET ENTIRE SYSTEM ⚠️</h2>
        
        <?php if ($message): ?>
            <div class="message success">
                <?= htmlspecialchars($message) ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_panel.php" class="btn btn-secondary">Back to Admin Panel</a>
            </div>
        <?php else: ?>
            <div class="danger">
                <strong>EXTREME WARNING:</strong> This action will permanently delete ALL data from the entire election system!
            </div>
            
            <div class="warning">
                <strong>CONFIRMATION REQUIRED:</strong> Are you absolutely sure you want to reset the entire system?
            </div>
            
            <div class="info-box">
                <strong>This will delete:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>All student records</li>
                    <li>All candidate information</li>
                    <li>All votes cast</li>
                    <li>Reset voting tokens</li>
                    <li>Disable voting status</li>
                    <li><strong>Complete database recreation (including LRN uniqueness)</strong></li>
                </ul>
                <p><strong>Note:</strong> This action cannot be undone. The system will be completely fresh for a new election.</p>
            </div>
            
            <form method="POST" style="text-align: center; margin-top: 20px;">
                <input type="submit" name="confirm_reset" value="YES, RESET ENTIRE SYSTEM" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!')">
                <input type="submit" name="cancel_reset" value="Cancel" class="btn btn-secondary">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>