<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$current_username = $_SESSION["username"];
$receiver_username = isset($_GET['receiver']) ? trim($_GET['receiver']) : '';

$page_error = null;

if (empty($receiver_username)) {
    $page_error = "No recipient specified for the message.";
    // Optional: redirect to messages.php or show error and halt
    // For now, will allow page to render with this error
} elseif ($receiver_username === $current_username) {
    $page_error = "You cannot send messages to yourself.";
    // Optional: redirect or halt
}

// Mark messages from $receiver_username to $current_username as read when this page loads
if (!$page_error && !empty($receiver_username)) {
    $update_read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_username = ? AND recipient_username = ? AND is_read = 0");
    if ($update_read_stmt) {
        $update_read_stmt->bind_param("ss", $receiver_username, $current_username);
        $update_read_stmt->execute();
        $update_read_stmt->close();
    } else {
        error_log("Failed to prepare statement for marking messages as read: " . $conn->error);
    }
}

// Handle sending a new message
if (!$page_error && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"]) && !empty($receiver_username)) {
    $message_text = trim($_POST["message"]);
    if (!empty($message_text)) {
        $insert_stmt = $conn->prepare("INSERT INTO messages (sender_username, recipient_username, message, is_read) VALUES (?, ?, ?, 0)"); // New messages are initially unread
        if ($insert_stmt) {
            $insert_stmt->bind_param("sss", $current_username, $receiver_username, $message_text);
            if (!$insert_stmt->execute()) {
                error_log("Failed to send message: " . $insert_stmt->error);
                // Set a session flash message for error if desired
            }
            $insert_stmt->close();
        } else {
            error_log("Failed to prepare statement for sending message: " . $conn->error);
        }
        // Redirect to clear POST data and show the new message
        header("Location: message.php?receiver=" . urlencode($receiver_username));
        exit();
    }
}

// Fetch conversation messages
$conversation_messages = [];
if (!$page_error && !empty($receiver_username)) {
    $fetch_stmt = $conn->prepare("SELECT id, sender_username, recipient_username, message, timestamp FROM messages 
        WHERE (sender_username=? AND recipient_username=?) OR (sender_username=? AND recipient_username=?) 
        ORDER BY timestamp ASC");
    if ($fetch_stmt) {
        $fetch_stmt->bind_param("ssss", $current_username, $receiver_username, $receiver_username, $current_username);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $conversation_messages[] = $row;
        }
        $fetch_stmt->close();
    } else {
        $page_error = "Error fetching messages.";
        error_log("Failed to prepare statement for fetching messages: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($receiver_username); ?> - CareerLynk</title>
    <?php // Styles are handled by header.php and style.css ?>
    <style>
        /* Basic height for chat area to prevent footer overlap if content is short */
        /* Adjust 5rem based on actual header height + some margin */
        .chat-container-height {
            height: calc(100vh - 10rem); /* Example: 5rem header + 5rem form/padding */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php include 'header.php'; ?>

<main class="flex-grow container mx-auto py-6 px-4 flex flex-col chat-container-height">
    
    <div class="mb-4">
        <a href="messages.php" class="text-blue-600 hover:text-blue-800">
            &larr; Back to Conversations
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Chat with <?php echo htmlspecialchars($receiver_username); ?></h1>
    </div>

    <?php if ($page_error): ?>
        <div class="alert alert-danger p-4 mb-4 rounded-md" role="alert">
            <?php echo htmlspecialchars($page_error); ?>
        </div>
    <?php endif; ?>

    <div id="chatBox" class="flex-grow overflow-y-auto p-4 space-y-4 bg-white rounded-t-lg shadow-inner border border-gray-200">
        <?php if (!empty($conversation_messages)): ?>
            <?php foreach ($conversation_messages as $msg): ?>
                <?php
                // Determine if the current logged-in user is the sender of this specific message
                $is_current_user_sender = ($msg['sender_username'] === $current_username);
                ?>
                <div class="flex <?php echo $is_current_user_sender ? 'justify-end' : 'justify-start'; ?>" id="message-<?php echo $msg['id']; ?>">
                    <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-xl shadow 
                                <?php echo $is_current_user_sender ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800'; ?> relative group">
                        <p class="text-sm break-words"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <p class="text-xs mt-1 <?php echo $is_current_user_sender ? 'text-blue-100' : 'text-gray-500'; ?> text-right">
                            <?php echo date("M d, g:i A", strtotime($msg['timestamp'])); ?>
                        </p>
                        <?php if ($is_current_user_sender): ?>
                            <button onclick="confirmDelete(<?php echo $msg['id']; ?>)" 
                                    class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                    title="Delete message">
                                &#x1F5D1; <!-- Unicode trash can icon -->
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (!$page_error): ?>
            <p class="text-center text-gray-500 py-10">No messages yet. Start the conversation!</p>
        <?php endif; ?>
    </div>

    <?php if (!$page_error && !empty($receiver_username)): ?>
    <form method="POST" action="message.php?receiver=<?php echo urlencode(htmlspecialchars($receiver_username)); ?>" class="bg-gray-100 p-4 rounded-b-lg shadow sticky bottom-0 border-t border-gray-200">
        <div class="flex items-center">
            <textarea name="message" required placeholder="Type your message..." rows="1" 
                      class="flex-grow p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 resize-none mr-2"></textarea>
            <button type="submit" name="send_message" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-5 rounded-lg transition duration-150 ease-in-out">
                Send
            </button>
        </div>
    </form>
    <?php endif; ?>

</main>

<script>
function scrollToBottom() {
    var chatBox = document.getElementById('chatBox');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

function autoResizeTextarea() {
    const textarea = document.querySelector('textarea[name="message"]');
    if (textarea) {
        textarea.style.height = 'auto'; // Reset height to shrink if text is deleted
        textarea.style.height = textarea.scrollHeight + 'px';
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }, false);
    }
}

window.addEventListener('load', () => {
    scrollToBottom();
    autoResizeTextarea();
});

function confirmDelete(messageId) {
    if (confirm("Are you sure you want to delete this message? This action cannot be undone.")) {
        deleteMessage(messageId);
    }
}

function deleteMessage(messageId) {
    fetch('delete_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message_id=' + messageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const messageElement = document.getElementById('message-' + messageId);
            if (messageElement) {
                messageElement.remove();
            }
            // Optionally, display a success notification
        } else {
            alert('Error deleting message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while trying to delete the message.');
    });
}

// If form was submitted (page reloaded after sending message), scroll again.
// This might be redundant if window.onload covers it, but can be a fallback.
<?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"])): ?>
    scrollToBottom(); 
<?php endif; ?>
</script>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
</body>
</html>
