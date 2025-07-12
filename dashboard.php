<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get voting statistics using PDO
try {
    // Total registered voters
    $total_voters_stmt = $pdo->query("SELECT COUNT(*) as count FROM voters");
    $total_voters = $total_voters_stmt->fetch()['count'];

    // Total votes cast (count distinct voter IDs from votes table)
    $voted_count_stmt = $pdo->query("SELECT COUNT(DISTINCT voter_id) as count FROM votes");
    $voted_count = $voted_count_stmt->fetch()['count'];

    // Get candidate vote results
    $candidates_stmt = $pdo->query("
        SELECT c.name, c.party, COUNT(v.id) as votes
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        GROUP BY c.id
        ORDER BY votes DESC
    ");
    $candidates = $candidates_stmt->fetchAll();

    // Prepare chart data
    $labels = [];
    $data = [];
    foreach ($candidates as $row) {
        $labels[] = $row['name'];
        $data[] = $row['votes'];
    }

} catch (Exception $e) {
    die("Error fetching dashboard data.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Online Voting System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css"> <!-- Optional external stylesheet -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f4f6f8;
        }
        .admin-container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout-btn {
            padding: 8px 15px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background-color: #e9ecef;
            flex: 1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .results-container {
            margin-top: 40px;
        }
        table.results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.results-table th, table.results-table td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        table.results-table th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>Admin Dashboard</h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <h3>Total Voters</h3>
                <p><?php echo htmlspecialchars($total_voters); ?></p>
            </div>
            <div class="stat-box">
                <h3>Votes Cast</h3>
                <p><?php echo htmlspecialchars($voted_count); ?></p>
            </div>
            <div class="stat-box">
                <h3>Turnout</h3>
                <p><?php echo $total_voters > 0 ? round(($voted_count / $total_voters) * 100, 1) : 0; ?>%</p>
            </div>
        </div>

        <div class="results-container">
            <h3>Election Results</h3>
            <canvas id="resultsChart" height="100"></canvas>

            <table class="results-table">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Party</th>
                        <th>Votes</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $row): 
                        $percentage = $voted_count > 0 ? round(($row['votes'] / $voted_count) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['party']); ?></td>
                        <td><?php echo $row['votes']; ?></td>
                        <td><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('resultsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Votes',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });
    </script>
</body>
</html>
