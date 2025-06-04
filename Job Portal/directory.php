<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    // A more user-friendly error page or message would be better in a production app
    die("Database connection failed: " . $conn->connect_error);
}

$current_username = $_SESSION["username"];
$page_message = null;
$page_message_type = 'info'; // 'info', 'success', 'error'

// Handle connection request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["connect_to_user"])) {
    $user_to_connect = $_POST["connect_to_user"];

    if ($user_to_connect === $current_username) {
        $page_message = "You cannot connect with yourself.";
        $page_message_type = 'error';
    } else {
        // Check if a pending or accepted connection already exists
        $check_stmt = $conn->prepare("SELECT status FROM connections WHERE (user1=? AND user2=?) OR (user1=? AND user2=?)");
        $check_stmt->bind_param("ssss", $current_username, $user_to_connect, $user_to_connect, $current_username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($existing_conn = $check_result->fetch_assoc()) {
            if ($existing_conn['status'] === 'pending') {
                $page_message = "A connection request is already pending with " . htmlspecialchars($user_to_connect) . ".";
            } elseif ($existing_conn['status'] === 'accepted'){
                $page_message = "You are already connected with " . htmlspecialchars($user_to_connect) . ".";
            }
            $page_message_type = 'info';
        } else {
            // No existing connection, proceed to insert
            $stmt = $conn->prepare("INSERT INTO connections (user1, user2, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ss", $current_username, $user_to_connect);
            if ($stmt->execute()) {
                // Send notification
                $message_text = htmlspecialchars($current_username) . " sent you a connection request.";
                $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, is_read) VALUES (?, ?, ?, 0)");
                $notifStmt->bind_param("sss", $user_to_connect, $current_username, $message_text);
                $notifStmt->execute();
                $notifStmt->close();
                $page_message = "Connection request sent to " . htmlspecialchars($user_to_connect) . ".";
                $page_message_type = 'success';
            } else {
                $page_message = "Failed to send connection request. Please try again.";
                $page_message_type = 'error';
                error_log("Connect request DB error: " . $stmt->error);
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    // Set message in session to persist across redirect if desired, or display directly
    // For simplicity here, using $page_message directly. For PRG pattern, use session.
    // header("Location: directory.php"); // Avoid redirect if displaying message on same page
    // exit();
}

// Fetch users for the directory: users who are not the current user
// and not already 'accepted' connections.
$users_to_display = [];
$users_sql = "
    SELECT u.id, u.username, u.full_name,
           c.status AS connection_status,
           CASE
               WHEN c.status = 'pending' AND c.user1 = ? THEN 'sent_by_me'
               WHEN c.status = 'pending' AND c.user2 = ? THEN 'received_by_me'
               ELSE c.status
           END AS detailed_connection_status
    FROM users u
    LEFT JOIN connections c ON (c.user1 = u.username AND c.user2 = ?) OR (c.user1 = ? AND c.user2 = u.username)
    WHERE u.username != ?
    AND u.role != 'admin'  -- Exclude admins
    ORDER BY u.username ASC";

$users_stmt = $conn->prepare($users_sql);
if ($users_stmt) {
    $users_stmt->bind_param("sssss", $current_username, $current_username, $current_username, $current_username, $current_username);
    $users_stmt->execute();
    $result = $users_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users_to_display[] = $row;
    }
    $users_stmt->close();
} else {
    $page_message = "Error fetching user directory.";
    $page_message_type = 'error';
    error_log("Directory user fetch DB error: " . $conn->error);
}

// Handle message from session (e.g., after redirect from another action)
if (isset($_SESSION['message']) && !isset($page_message)) { // Prioritize direct page_message
    $page_message = $_SESSION['message'];
    $page_message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Directory - CareerLynk</title>
    <?php // CSS links are in header.php ?>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">User Directory</h1>

    <?php if (isset($page_message)): ?>
        <div class="p-4 mb-6 rounded-md 
            <?php echo ($page_message_type === 'success' ? 'alert-success' : ($page_message_type === 'error' ? 'alert-danger' : 'alert-info')); ?>"
             role="alert">
            <strong><?php echo htmlspecialchars($page_message); ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!empty($users_to_display)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($users_to_display as $user): ?>
            <div class="bg-white p-5 rounded-lg shadow-md text-center">
                <a href="profile.php?username=<?php echo urlencode(htmlspecialchars($user['username'])); ?>" class="block mb-2">
                    <?php 
                    // Basic avatar placeholder using initials
                    $initials = '';
                    if (!empty($user['full_name'])) {
                        $parts = explode(" ", $user['full_name']);
                        $initials = $parts[0][0] ?? '';
                        if (count($parts) > 1) {
                            $initials .= $parts[count($parts)-1][0] ?? '';
                        }
                    } elseif (!empty($user['username'])) {
                        $initials = strtoupper($user['username'][0]);
                    }
                    ?>
                    <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-3">
                        <?php echo htmlspecialchars(strtoupper($initials)); ?>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 hover:text-blue-600 transition duration-150">
                        <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                    </h3>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                </a>
                
                <?php 
                // Use the detailed_connection_status fetched from the modified query
                $connection_status_with_this_user = $user['detailed_connection_status'] ?? null;

                if ($connection_status_with_this_user === 'accepted'): ?>
                    <p class="text-sm text-green-500 font-semibold italic mt-3">Connected</p>
                <?php elseif ($connection_status_with_this_user === 'sent_by_me'): ?>
                    <p class="text-sm text-gray-500 italic mt-3">Connection request sent.</p>
                <?php elseif ($connection_status_with_this_user === 'received_by_me'): ?>
                     <p class="text-sm text-orange-500 italic mt-3">Pending request from them.</p>
                     <a href="connections.php" class="mt-2 inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-1 px-3 rounded-md text-xs">View Requests</a>
                <?php else: // No connection or other status (e.g. declined, though not handled explicitly here) - show connect button ?>
                    <form method='POST' action="directory.php" class="mt-3">
                        <input type="hidden" name="connect_to_user" value="<?php echo htmlspecialchars($user['username']); ?>">
                        <button type='submit' 
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
                            Connect
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600 text-center py-8">No users found in the directory that you can connect with at this time.</p>
    <?php endif; ?>
</main>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
</body>
</html>
