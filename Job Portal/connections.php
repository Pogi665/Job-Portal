<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

// Handle Accept
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept"])) {
    $user_to_accept = $_POST["accept"];
    $stmt = $conn->prepare("UPDATE connections SET status='accepted' WHERE user1=? AND user2=? AND status='pending'");
    $stmt->bind_param("ss", $user_to_accept, $username);
    $stmt->execute(); $stmt->close();

    $message = "$username accepted your connection request";
    $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, is_read) VALUES (?, ?, ?, 0)");
    $notifStmt->bind_param("sss", $user_to_accept, $username, $message);
    $notifStmt->execute(); $notifStmt->close();

    $_SESSION['message'] = "Connection request from $user_to_accept accepted.";
    header("Location: connections.php"); exit();
}

// Handle Reject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reject"])) {
    $user_to_reject = $_POST["reject"];
    $stmt = $conn->prepare("DELETE FROM connections WHERE user1=? AND user2=? AND status='pending'");
    $stmt->bind_param("ss", $user_to_reject, $username);
    $stmt->execute(); $stmt->close();

    $_SESSION['message'] = "Connection request from $user_to_reject rejected.";
    header("Location: connections.php"); exit();
}

// Fetch accepted connections
$connStmt = $conn->prepare("SELECT user1, user2 FROM connections WHERE (user1=? OR user2=?) AND status='accepted'");
$connStmt->bind_param("ss", $username, $username);
$connStmt->execute();
$connResult = $connStmt->get_result();

$connections = [];
while ($row = $connResult->fetch_assoc()) {
    $otherUser = ($row['user1'] == $username) ? $row['user2'] : $row['user1'];
    $connections[] = $otherUser;
}

// Fetch pending requests
$pending_requests = $conn->prepare("SELECT user1 FROM connections WHERE user2=? AND status='pending'");
$pending_requests->bind_param("s", $username);
$pending_requests->execute();
$pending_result = $pending_requests->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connections</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
        }
        .message {
            background-color: #dff0d8;
            border: 1px solid #3c763d;
            padding: 10px;
            border-radius: 5px;
            color: #3c763d;
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            text-align: center;
        }
        .card h3 {
            margin: 10px 0;
        }
        .card form {
            display: inline-block;
            margin: 5px;
        }
        .card a {
            display: inline-block;
            margin: 5px;
            color: #007bff;
            text-decoration: none;
        }
        .card a:hover {
            text-decoration: underline;
        }
        .card button {
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
        }
        .card button.reject {
            background-color: #dc3545;
        }
        .card button:hover {
            opacity: 0.9;
        }
        h1, h2 {
            margin-top: 40px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Your Connections</h1>

    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='message'><strong>{$_SESSION['message']}</strong></div>";
        unset($_SESSION['message']);
    }
    ?>

    <?php if (count($connections) > 0): ?>
        <div class="grid">
        <?php foreach ($connections as $user): ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($user); ?></h3>
                <form method="GET" action="message.php">
                    <input type="hidden" name="receiver" value="<?php echo htmlspecialchars($user); ?>">
                    <button type="submit">Send Message</button>
                </form>
                <a href="profile.php?username=<?php echo urlencode($user); ?>">View Profile</a>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have no connections yet.</p>
    <?php endif; ?>

    <h2>Pending Connection Requests</h2>
    <?php if ($pending_result->num_rows > 0): ?>
        <div class="grid">
        <?php while ($row = $pending_result->fetch_assoc()): 
            $pending_user = $row['user1']; ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($pending_user); ?></h3>
                <form method="POST">
                    <button type="submit" name="accept" value="<?php echo htmlspecialchars($pending_user); ?>">Accept</button>
                </form>
                <form method="POST">
                    <button class="reject" type="submit" name="reject" value="<?php echo htmlspecialchars($pending_user); ?>">Reject</button>
                </form>
            </div>
        <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No pending connection requests.</p>
    <?php endif; ?>
</div>
</body>
</html>
