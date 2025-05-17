<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

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

// Get users + last messages and unread count
$usersWithMessages = [];
foreach ($connections as $user) {
    $previewStmt = $conn->prepare("SELECT message, sender, timestamp, is_read 
        FROM messages 
        WHERE (sender=? AND receiver=?) OR (sender=? AND receiver=?) 
        ORDER BY timestamp DESC LIMIT 1");
    $previewStmt->bind_param("ssss", $username, $user, $user, $username);
    $previewStmt->execute();
    $previewResult = $previewStmt->get_result();
    $lastMsg = $previewResult->fetch_assoc();

    // Get unread message count for the current user
    $unreadStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE receiver=? AND sender=? AND is_read=0");
    $unreadStmt->bind_param("ss", $username, $user);
    $unreadStmt->execute();
    $unreadResult = $unreadStmt->get_result();
    $unreadCount = $unreadResult->fetch_assoc()['unread_count'];

    if ($lastMsg) {
        $usersWithMessages[] = [
            'user' => $user,
            'message' => $lastMsg['message'],
            'sender' => $lastMsg['sender'],
            'timestamp' => $lastMsg['timestamp'],
            'is_read' => $lastMsg['is_read'],
            'unread_count' => $unreadCount
        ];
    }
}

usort($usersWithMessages, function ($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* General Styles */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .message {
            background-color: #f0f8ff;
            padding: 10px;
            border: 1px solid #007BFF;
            border-radius: 5px;
        }
        .message.unread {
            font-weight: bold;
            background-color: #d1f1ff;
            border-left: 4px solid #007BFF;
        }
        .message a {
            text-decoration: none;
            color: #007BFF;
        }
        .message small {
            font-size: 0.8em;
            color: #888;
        }
        .message em {
            display: block;
            margin-top: 5px;
        }
        .message button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .message button:hover {
            background-color: #0056b3;
        }
        .unread-count {
            background-color: #ff5733;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Your Conversations</h1>
    <div class="message-list">
        <?php foreach ($usersWithMessages as $entry): ?>
            <div class="message <?php echo ($entry['is_read'] == 0 && $entry['sender'] != $username) ? 'unread' : ''; ?>">
                <a href="message.php?receiver=<?php echo urlencode($entry['user']); ?>">
                    <strong><?php echo htmlspecialchars($entry['user']); ?></strong>
                    <?php if ($entry['unread_count'] > 0): ?>
                        <span class="unread-count"><?php echo $entry['unread_count']; ?> new</span>
                    <?php endif; ?>
                </a><br>
                <em>
                    <?php echo ($entry['sender'] == $username ? 'You: ' : htmlspecialchars($entry['sender']) . ': '); ?>
                    <?php echo htmlspecialchars($entry['message']); ?>
                </em><br>
                <small><?php echo date("g:i A", strtotime($entry['timestamp'])); ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
