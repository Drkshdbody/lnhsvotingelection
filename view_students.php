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
    <title>School Learner Government Election System - View Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .back-link { margin-top: 10px; }
        .search-box { margin-bottom: 15px; }
        input[type="text"] { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-section { margin-bottom: 15px; }
        select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .btn {
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
        
        <h2>All Students</h2>
        
        <div class="search-box">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for students...">
        </div>
        
        <div class="filter-section">
            <label for="gradeFilter">Filter by Grade: </label>
            <select id="gradeFilter" onchange="filterTable()">
                <option value="">All Grades</option>
                <option value="2">Grade 2</option>
                <option value="3">Grade 3</option>
                <option value="4">Grade 4</option>
                <option value="5">Grade 5</option>
                <option value="6">Grade 6</option>
                <option value="7">Grade 7</option>
                <option value="8">Grade 8</option>
                <option value="9">Grade 9</option>
                <option value="10">Grade 10</option>
                <option value="11">Grade 11</option>
            </select>
            
            <label for="sectionFilter">Filter by Section: </label>
            <select id="sectionFilter" onchange="filterTable()">
                <option value="">All Sections</option>
                <?php
                $db = new SQLite3('election.db');
                $sections = $db->query("SELECT DISTINCT section FROM students ORDER BY section");
                while ($row = $sections->fetchArray()) {
                    if (!empty($row['section'])) {
                        echo "<option value='".htmlspecialchars($row['section'])."'>".htmlspecialchars($row['section'])."</option>";
                    }
                }
                ?>
            </select>
            
            <label for="votedFilter">Voting Status: </label>
            <select id="votedFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="0">Not Voted</option>
                <option value="1">Voted</option>
            </select>
        </div>

        <table id="studentsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Sex</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Has Voted?</th>
                    <th>Login Token</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db = new SQLite3('election.db');
                $result = $db->query("SELECT * FROM students ORDER BY grade_level, section, last_name");
                
                $counter = 1;
                while ($row = $result->fetchArray()) {
                    echo "<tr>";
                    echo "<td>".$counter."</td>"; // Numbering column
                    echo "<td>".htmlspecialchars($row['lrn'])."</td>"; // LRN as the main identifier
                    echo "<td>".htmlspecialchars($row['last_name']).", ".htmlspecialchars($row['given_name'])." ".htmlspecialchars($row['middle_name'])."</td>";
                    echo "<td>".htmlspecialchars($row['sex'])."</td>";
                    echo "<td>".$row['grade_level']."</td>";
                    echo "<td>".htmlspecialchars($row['section'])."</td>";
                    $has_voted_text = ($row['has_voted'] == 1) ? '<span style="color: green; font-weight: bold;">Yes</span>' : '<span style="color: red;">No</span>';
                    echo "<td>".$has_voted_text."</td>";
                    echo "<td>".htmlspecialchars($row['login_token'])."</td>"; // Added token column
                    echo "<td>";
                    echo "<a href='delete_student.php?id=".$row['id']."' class='btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                    $counter++;
                }
                ?>
            </tbody>
        </table>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>

    <script>
        function searchTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("studentsTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tr[i].style.display = "";
                td = tr[i].getElementsByTagName("td");
                var found = false;
                
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (!found) {
                    tr[i].style.display = "none";
                }
            }
        }

        function filterTable() {
            var gradeFilter = document.getElementById("gradeFilter").value;
            var sectionFilter = document.getElementById("sectionFilter").value;
            var votedFilter = document.getElementById("votedFilter").value;
            
            var table = document.getElementById("studentsTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var td_grade = tr[i].getElementsByTagName("td")[4]; // Grade column
                var td_section = tr[i].getElementsByTagName("td")[5]; // Section column
                var td_voted = tr[i].getElementsByTagName("td")[6]; // Voted column

                var showRow = true;

                if (gradeFilter != "" && td_grade) {
                    if (td_grade.textContent != gradeFilter) {
                        showRow = false;
                    }
                }

                if (sectionFilter != "" && td_section) {
                    if (td_section.textContent != sectionFilter) {
                        showRow = false;
                    }
                }

                if (votedFilter != "" && td_voted) {
                    var votedText = td_voted.textContent.toLowerCase();
                    if (votedFilter == "0" && !votedText.includes("no")) {
                        showRow = false;
                    } else if (votedFilter == "1" && !votedText.includes("yes")) {
                        showRow = false;
                    }
                }

                tr[i].style.display = showRow ? "" : "none";
            }
        }
    </script>
</body>
</html>