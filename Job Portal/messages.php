<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$current_username = $_SESSION["username"];
$usersWithMessages = [];

// 1. Get distinct users the current user has had conversations with
$partners_sql = "
    SELECT DISTINCT
        IF(sender_username = ?, recipient_username, sender_username) AS conversation_partner
    FROM messages
    WHERE sender_username = ? OR recipient_username = ?
";
$partners_stmt = $conn->prepare($partners_sql);
if ($partners_stmt) {
    $partners_stmt->bind_param("sss", $current_username, $current_username, $current_username);
    $partners_stmt->execute();
    $partners_result = $partners_stmt->get_result();
    $conversation_partners = [];
    while ($row = $partners_result->fetch_assoc()) {
        $conversation_partners[] = $row['conversation_partner'];
    }
    $partners_stmt->close();

    if (!empty($conversation_partners)) {
        $last_msg_sql = "
            SELECT message, sender_username, timestamp, is_read, recipient_username
            FROM messages
            WHERE (sender_username = ? AND recipient_username = ?) OR (sender_username = ? AND recipient_username = ?)
            ORDER BY timestamp DESC LIMIT 1
        ";
        $last_msg_stmt = $conn->prepare($last_msg_sql);

        $unread_sql = "
            SELECT COUNT(*) as unread_count
            FROM messages
            WHERE recipient_username = ? AND sender_username = ? AND is_read = 0
        ";
        $unread_stmt = $conn->prepare($unread_sql);

        if ($last_msg_stmt && $unread_stmt) {
            foreach ($conversation_partners as $partner_username) {
                // Get last message details
                $last_msg_stmt->bind_param("ssss", $current_username, $partner_username, $partner_username, $current_username);
                $last_msg_stmt->execute();
                $last_msg_result = $last_msg_stmt->get_result();
                $lastMsgDetails = $last_msg_result->fetch_assoc();

                // Get unread message count (messages from partner to current user that are unread)
                $unread_stmt->bind_param("ss", $current_username, $partner_username);
                $unread_stmt->execute();
                $unread_result = $unread_stmt->get_result();
                $unreadData = $unread_result->fetch_assoc();
                $unread_count_from_partner = $unreadData ? $unreadData['unread_count'] : 0;

                if ($lastMsgDetails) {
                    $usersWithMessages[] = [
                        'partner_username' => $partner_username,
                        'last_message_text' => $lastMsgDetails['message'],
                        'last_message_sender' => $lastMsgDetails['sender_username'],
                        'last_message_timestamp' => $lastMsgDetails['timestamp'],
                        'unread_count_for_current_user' => $unread_count_from_partner
                        // 'is_last_message_read_by_receiver' => $lastMsgDetails['is_read'] // if needed for other logic
                    ];
                }
            }
            $last_msg_stmt->close();
            $unread_stmt->close();
        } else {
            error_log("Failed to prepare statements for last message or unread count.");
        }
        
        // Sort by the timestamp of the last message, descending
        usort($usersWithMessages, function ($a, $b) {
            return strtotime($b['last_message_timestamp']) - strtotime($a['last_message_timestamp']);
        });
    }
} else {
    error_log("Failed to prepare statement for fetching conversation partners.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - CareerLynk</title>
    <?php // Styles are handled by header.php and style.css ?>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Your Conversations</h1>
        <!-- TODO: Add a "New Message" button here if desired -->
        <!-- <a href="new_message.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md">New Message</a> -->
    </div>

    <?php if (!empty($usersWithMessages)): ?>
        <div class="bg-white rounded-lg shadow">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($usersWithMessages as $convo): ?>
                    <?php 
                        $is_unread_for_current = $convo['unread_count_for_current_user'] > 0;
                        $avatar_initials = strtoupper(substr($convo['partner_username'], 0, 1)); // Simple initial
                    ?>
                    <li class="hover:bg-gray-50 transition duration-150 ease-in-out <?php echo $is_unread_for_current ? 'font-semibold' : ''; ?>">
                        <a href="message.php?receiver=<?php echo urlencode(htmlspecialchars($convo['partner_username'])); ?>" class="block p-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-lg font-bold">
                                        <?php echo htmlspecialchars($avatar_initials); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <p class="text-md <?php echo $is_unread_for_current ? 'text-blue-600' : 'text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($convo['partner_username']); ?>
                                        </p>
                                        <div class="text-xs <?php echo $is_unread_for_current ? 'text-blue-500' : 'text-gray-500'; ?>">
                                            <?php echo date("M d, g:i A", strtotime($convo['last_message_timestamp'])); ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-start mt-1">
                                        <p class="text-sm <?php echo $is_unread_for_current ? 'text-gray-700' : 'text-gray-500'; ?> truncate">
                                            <?php 
                                                if ($convo['last_message_sender'] == $current_username) {
                                                    echo "<span class='text-gray-500'>You: </span>";
                                                }
                                                echo htmlspecialchars($convo['last_message_text']); 
                                            ?>
                                        </p>
                                        <?php if ($convo['unread_count_for_current_user'] > 0): ?>
                                            <span class="ml-2 flex-shrink-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                                                <?php echo $convo['unread_count_for_current_user']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No conversations yet</h3>
            <p class="mt-1 text-sm text-gray-500">
                Start a conversation from a user's profile or the directory.
            </p>
            <div class="mt-6">
                <a href="directory.php"
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    Find Users
                </a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
</body>
</html>
