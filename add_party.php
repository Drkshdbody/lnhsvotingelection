<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$error = '';

if ($_POST) {
    $party_name = trim($_POST['party_name'] ?? '');
    
    if (empty($party_name)) {
        $error = "Party name is required.";
    } else {
        $db = new SQLite3('election.db');
        
        // Check if party already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM candidates WHERE party = ?");
        $stmt->bindValue(1, $party_name);
        $count = $stmt->execute()->fetchArray()[0];
        
        if ($count > 0) {
            $error = "Party already exists.";
        } else {
            // We don't need to store parties separately since they're in candidates table
            // But we can validate that the party name is valid
            $message = "Party '$party_name' added successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Party - School Learner Government Election System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .logo {
            font-size: 4em;
            color: #4285f4;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 2em;
            color: #202124;
            font-weight: 400;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 1.2em;
            color: #5f6368;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-weight: 500;
            text-align: left;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        
        input[type="text"]:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 1px #4285f4;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: #4285f4;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3367d6;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-link a {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .footer {
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            text-align: center;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            font-size: 0.9em;
        }
        
        .instructions h3 {
            color: #202124;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 5px 0;
            color: #5f6368;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .title {
                font-size: 1.8em;
            }
            
            .subtitle {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">SLGES</div>
        <h1 class="title">Add Party</h1>
        <p class="subtitle">School Learner Government Election System</p>
        
        <div class="instructions">
            <h3>Adding Parties:</h3>
            <ul>
                <li>Enter the name of the party you want to add</li>
                <li>Parties will be available for candidate registration</li>
                <li>Party names must be unique</li>
                <li>Parties are created when candidates are added with party names</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="party_name">Party Name</label>
                <input type="text" name="party_name" id="party_name" placeholder="Enter party name" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Party</button>
        </form>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
    
    <div class="footer">
        &copy; 2026 School Learner Government Election System
    </div>
</body>
</html>