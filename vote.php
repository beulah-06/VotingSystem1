<?php
session_start();
require_once 'config.php';

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['voter_id'])) {
    header("Location: index.php");
    exit();
}

$voter_id = $_SESSION['voter_id'];

// Check if already voted
$stmt = $pdo->prepare("SELECT * FROM votes WHERE voter_id = :voter_id");
$stmt->execute(['voter_id' => $voter_id]);
if ($stmt->rowCount() > 0) {
    header("Location: thank_you.php");
    exit();
}

// Fetch candidates
try {
    $stmt = $pdo->query("SELECT * FROM candidates");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching candidates.");
}

// Handle vote submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $candidate_id = trim($_POST['candidate_id']);
    if (!ctype_digit($candidate_id)) {
        die("Invalid candidate ID");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO votes (voter_id, candidate_id) VALUES (:voter_id, :candidate_id)");
        $stmt->execute([
            'voter_id' => $voter_id,
            'candidate_id' => $candidate_id
        ]);
        header("Location: thank_you.php");
        exit();
    } catch (Exception $e) {
        die("Voting failed. Please try again.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="voting-box">
            <h2>Cast Your Vote</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['voter_name']); ?></p>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="candidates-list">
                    <?php foreach($candidates as $candidate): ?>
                        <div class="candidate-card">
                            <img src="<?php echo htmlspecialchars($candidate['image_url']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                            <h3><?php echo htmlspecialchars($candidate['name']); ?></h3>
                            <p><?php echo htmlspecialchars($candidate['party']); ?></p>
                            <input type="radio" name="candidate_id" value="<?php echo $candidate['id']; ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit">Submit Vote</button>
            </form>
            
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</body>
</html>
