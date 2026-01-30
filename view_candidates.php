<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $password = $_POST['password'];
    if (empty($password)) $password = '';
    if ($password !== 'admin123') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION['admin_logged_in'] = true;
}

$db = new SQLite3('election.db');
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - View All Candidates</title>
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
            overflow-x: auto; 
        }
        .back-link { 
            margin-bottom: 15px; 
        }
        .search-box { 
            margin-bottom: 15px; 
        }
        input[type="text"] { 
            padding: 8px; 
            margin: 5px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            width: 300px; 
        }
        .filter-section { 
            margin-bottom: 15px; 
        }
        select { 
            padding: 8px; 
            margin: 5px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .candidate-card {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .candidate-photo-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #ddd;
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
        .candidate-grade-section {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 3px;
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
        .candidate-votes {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            display: inline-block;
            margin-top: 5px;
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
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .page-link {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 2px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #007bff;
        }
        .page-link.active {
            background: #007bff;
            color: white;
        }
        .candidate-actions {
            margin-top: 10px;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.8em;
        }
        .edit-btn {
            background: #ffc107;
            color: #212529;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
        
        <h2>All Candidates</h2>
        
        <div class="search-box">
            <input type="text" id="searchInput" onkeyup="searchCandidates()" placeholder="Search for candidates...">
        </div>
        
        <div class="filter-section">
            <label for="positionFilter">Filter by Position: </label>
            <select id="positionFilter" onchange="filterCandidates()">
                <option value="">All Positions</option>
                <?php
                $positions = $db->query("SELECT DISTINCT position FROM candidates ORDER BY position");
                while ($row = $positions->fetchArray()) {
                    echo "<option value='".htmlspecialchars($row['position'])."'>".htmlspecialchars($row['position'])."</option>";
                }
                ?>
            </select>
            
            <label for="partyFilter">Filter by Party: </label>
            <select id="partyFilter" onchange="filterCandidates()">
                <option value="">All Parties</option>
                <?php
                $parties = $db->query("SELECT DISTINCT party FROM candidates WHERE party IS NOT NULL ORDER BY party");
                while ($row = $parties->fetchArray()) {
                    if (!empty($row['party'])) {
                        echo "<option value='".htmlspecialchars($row['party'])."'>".htmlspecialchars($row['party'])."</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div id="candidatesContainer">
            <?php
            $result = $db->query("SELECT c.*, s.grade_level, s.section FROM candidates c LEFT JOIN students s ON c.name LIKE s.last_name || ', ' || s.given_name || ' ' || s.middle_name ORDER BY c.position, c.name");
            
            while ($row = $result->fetchArray()) {
                echo "<div class='candidate-card' data-position='".htmlspecialchars($row['position'])."' data-party='".htmlspecialchars($row['party'])."' data-name='".htmlspecialchars($row['name'])."'>";
                
                // Show photo if exists
                if (!empty($row['photo_path']) && file_exists($row['photo_path'])) {
                    echo "<img src='".$row['photo_path']."' alt='".$row['name']."' class='candidate-photo-large'>";
                } else {
                    // Default avatar with first letter
                    $first_letter = strtoupper(substr($row['name'], 0, 1));
                    echo "<div class='no-photo'>".$first_letter."</div>";
                }
                
                echo "<div class='candidate-info'>";
                echo "<div class='candidate-name'>".htmlspecialchars($row['name'])."</div>";
                echo "<div class='candidate-position'>".htmlspecialchars($row['position'])."</div>";
                
                // Show grade and section if available
                if (!empty($row['grade_level']) && !empty($row['section'])) {
                    echo "<div class='candidate-grade-section'>Grade ".$row['grade_level']."-".$row['section']."</div>";
                } else {
                    echo "<div class='candidate-grade-section'>Grade Information Not Available</div>";
                }
                
                if (!empty($row['party'])) {
                    echo "<div class='candidate-party'>".htmlspecialchars($row['party'])."</div>";
                }
                
                if ($row['vote_count'] > 0) {
                    echo "<div class='candidate-votes'>".$row['vote_count']." votes</div>";
                } else {
                    echo "<div class='candidate-votes'>0 votes</div>";
                }
                
                echo "<div class='candidate-actions'>";
                echo "<a href='edit_candidate.php?id=".$row['id']."' class='action-btn edit-btn'>Edit</a>";
                echo "<a href='delete_candidate.php?id=".$row['id']."' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this candidate?\")'>Delete</a>";
                echo "</div>";
                
                echo "</div>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>

    <script>
        function searchCandidates() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();
            var cards = document.getElementsByClassName("candidate-card");

            for (var i = 0; i < cards.length; i++) {
                var name = cards[i].getAttribute("data-name");
                var position = cards[i].getAttribute("data-position");
                
                if (name.toUpperCase().indexOf(filter) > -1 || position.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }

        function filterCandidates() {
            var positionFilter = document.getElementById("positionFilter").value;
            var partyFilter = document.getElementById("partyFilter").value;
            var cards = document.getElementsByClassName("candidate-card");

            for (var i = 0; i < cards.length; i++) {
                var cardPosition = cards[i].getAttribute("data-position");
                var cardParty = cards[i].getAttribute("data-party");
                
                var showCard = true;
                
                if (positionFilter != "" && cardPosition != positionFilter) {
                    showCard = false;
                }
                
                if (partyFilter != "" && cardParty != partyFilter) {
                    showCard = false;
                }
                
                cards[i].style.display = showCard ? "" : "none";
            }
        }
    </script>
</body>
</html>