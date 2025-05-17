<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];
$receiver = isset($_GET['receiver']) ? $_GET['receiver'] : '';

// Send message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"]) && !empty($receiver)) {
    $message = trim($_POST["message"]);
    if (!empty($message)) {
        // Insert the new message and mark it as unread (is_read = 0)
        $stmt = $conn->prepare("INSERT INTO messages (sender, receiver, message, is_read) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $username, $receiver, $message);
        $stmt->execute();
        $stmt->close();
        
        // Mark the messages as read after sending the message
        $updateStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver=? AND sender=? AND is_read = 0");
        $updateStmt->bind_param("ss", $username, $receiver);
        $updateStmt->execute();
        $updateStmt->close();
    }
    header("Location: message.php?receiver=" . urlencode($receiver));
    exit();
}

// Fetch conversation
$messages = [];
if (!empty($receiver)) {
    $stmt = $conn->prepare("SELECT * FROM messages 
        WHERE (sender=? AND receiver=?) OR (sender=? AND receiver=?) 
        ORDER BY timestamp ASC");
    $stmt->bind_param("ssss", $username, $receiver, $receiver, $username);
    $stmt->execute();
    $messages = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($receiver); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        .chat-box {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            background: #f9f9f9;
            max-height: 500px;
            overflow-y: auto;
            height: 250px;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            max-width: 70%;
            position: relative;
        }
        .sent {
            background-color: #007bff;
            color: white;
            margin-left: auto;
            text-align: right;
        }
        .received {
            background-color: #eaeaea;
            color: #333;
            margin-right: auto;
            text-align: left;
        }
        .new-message {
            position: absolute;
            top: -5px;
            right: 10px;
            background-color: #ffeb3b;
            color: black;
            padding: 2px 5px;
            font-size: 0.8em;
            border-radius: 5px;
        }
        .timestamp {
            font-size: 0.8em;
            color: black;
            margin-top: 5px;
        }
        .chat-form {
            margin-top: 20px;
        }
        textarea {
            width: 100%;
            height: 80px;
            resize: vertical;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 10px;
            background-color: #007bff;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Chat with <?php echo htmlspecialchars($receiver); ?></h1>

    <div class="chat-box" id="chatBox">
        <?php if ($messages && $messages->num_rows > 0): ?>
            <?php while ($row = $messages->fetch_assoc()): ?>
                <div class="message <?php echo $row['sender'] == $username ? 'sent' : 'received'; ?>">
                    <?php 
                    echo htmlspecialchars($row['message']); 
                    if ($row['is_read'] == 0 && $row['receiver'] == $username) { 
                        // Display "New Message" text for unread messages
                        echo '<span class="new-message">New Message</span>'; 
                    }
                    ?>
                    <div class="timestamp">
                        <?php echo date("M j, g:i A", strtotime($row['timestamp'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages yet. Start the conversation!</p>
        <?php endif; ?>
    </div>

    <form method="POST" class="chat-form">
        <textarea name="message" required placeholder="Type your message..."></textarea>
        <button type="submit" name="send_message">Send</button>
    </form>
</div>

<script>
// Function to scroll to the bottom of the chat box
function scrollToBottom() {
    var chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Call the function when the page loads
window.onload = scrollToBottom;

// Call the function after a message is sent and the page reloads
<?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"])): ?>
    window.onload = function() {
        scrollToBottom();
    };
<?php endif; ?>
</script>

</body>
</html>
