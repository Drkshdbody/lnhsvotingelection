<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$candidate_id = $_GET['id'] ?? $_POST['id'] ?? '';

if (empty($candidate_id)) {
    header("Location: view_candidates.php");
    exit;
}

$db = new SQLite3('election.db');

// Get candidate data
$stmt = $db->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->bindValue(1, $candidate_id);
$result = $stmt->execute();
$candidate = $result->fetchArray();

if (!$candidate) {
    header("Location: view_candidates.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete candidate
    $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->bindValue(1, $candidate_id);
    
    if ($stmt->execute()) {
        // Also delete any votes for this candidate
        $stmt = $db->prepare("DELETE FROM votes WHERE candidate_id = ?");
        $stmt->bindValue(1, $candidate_id);
        $stmt->execute();
        
        $message = "Candidate deleted successfully!";
    } else {
        $message = "Error deleting candidate.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Candidate - School Learner Government Election System</title>
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
        .candidate-display {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .candidate-photo-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #ddd;
        }
        .no-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-weight: bold;
            font-size: 1.5em;
            margin-right: 15px;
            border: 2px solid #ccc;
        }
        .candidate-info {
            flex: 1;
        }
        .candidate-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #2c3e50;
        }
        .candidate-position {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .candidate-party {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            display: inline-block;
            margin-top: 5px;
        }
        .form-group { 
            margin: 15px 0; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        input, select, textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        button { 
            background-color: #dc3545; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-right: 10px; 
        }
        button:hover { 
            background-color: #c82333; 
        }
        .cancel-btn {
            background-color: #6c757d;
        }
        .cancel-btn:hover {
            background-color: #5a6268;
        }
        .back-link { 
            margin-top: 20px; 
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
        .error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Delete Candidate</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($message)): ?>
            <div class="warning">
                <strong>Warning:</strong> This action cannot be undone. All votes for this candidate will also be deleted.
            </div>
            
            <!-- Display current candidate info -->
            <div class="candidate-display">
                <?php if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])): ?>
                    <img src="<?= $candidate['photo_path'] ?>" alt="<?= $candidate['name'] ?>" class="candidate-photo-large">
                <?php else: ?>
                    <div class="no-photo"><?= strtoupper(substr($candidate['name'], 0, 1)) ?></div>
                <?php endif; ?>
                
                <div class="candidate-info">
                    <div class="candidate-name"><?= htmlspecialchars($candidate['name']) ?></div>
                    <div class="candidate-position"><?= htmlspecialchars($candidate['position']) ?></div>
                    <?php if (!empty($candidate['party'])): ?>
                        <div class="candidate-party"><?= htmlspecialchars($candidate['party']) ?></div>
                    <?php endif; ?>
                    <div>Votes: <?= $candidate['vote_count'] ?></div>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="id" value="<?= $candidate['id'] ?>">
                <p>Are you sure you want to delete this candidate?</p>
                <button type="submit">Yes, Delete Candidate</button>
                <a href="view_candidates.php">
                    <button type="button" class="cancel-btn">Cancel</button>
                </a>
            </form>
        <?php else: ?>
            <a href="view_candidates.php">
                <button class="cancel-btn">Back to All Candidates</button>
            </a>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>