<?php 
session_start();
require_once 'config.php';

if (!isset($_SESSION['voter_id'])) {
    header("Location: index.php");
    exit();
}

$voter_id = $_SESSION['voter_id'];

// Prepare and execute with PDO
$stmt = $pdo->prepare("
    SELECT c.name, c.party 
    FROM votes v 
    JOIN candidates c ON v.candidate_id = c.id 
    WHERE v.voter_id = ?
");
$stmt->execute([$voter_id]);
$vote_details = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Online Voting System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Thank You for Voting!</h2>
            <div class="thank-you-message">
                <p>Dear <?php echo htmlspecialchars($_SESSION['voter_name']); ?>,</p>
                <p>Your vote has been successfully recorded.</p>
                <?php if ($vote_details): ?>
                <div class="vote-details">
                    <p>You voted for:</p>
                    <h3><?php echo htmlspecialchars($vote_details['name']); ?></h3>
                    <p><?php echo htmlspecialchars($vote_details['party']); ?></p>
                </div>
                <?php endif; ?>
                <p class="timestamp">Vote cast on: <?php echo date('F j, Y, g:i a'); ?></p>
            </div>
            <div class="actions">
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>