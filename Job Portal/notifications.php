<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$current_user_id = $_SESSION["user_id"];
$current_user_username = ''; // Initialize

// Fetch username for the current user_id
$user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
if ($user_stmt) {
    $user_stmt->bind_param("i", $current_user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_row = $user_result->fetch_assoc()) {
        $current_user_username = $user_row['username'];
    }
    $user_stmt->close();
}

if (empty($current_user_username)) {
    // Handle error: user not found, though this is unlikely if session is set
    error_log("Could not find username for user_id: " . $current_user_id);
    // You might want to redirect or show an error message
    die("Error: Could not identify user.");
}

// Handle marking all as read
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_all_as_read'])) {
    $updateQuery = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_username = ? AND is_read = 0");
    if ($updateQuery) {
        $updateQuery->bind_param("s", $current_user_username);
        $updateQuery->execute();
        $updateQuery->close();
    }
    header("Location: notifications.php"); // Refresh
    exit();
}

// Handle marking individual notification as read and redirecting (if applicable)
if (isset($_GET['notif_id'])) {
    $notif_id = intval($_GET['notif_id']);
    $redirect_url = "notifications.php"; // Default redirect back to notifications page

    // Fetch the notification to check its type for potential specific redirection
    $notif_check_stmt = $conn->prepare("SELECT message, type, job_id FROM notifications WHERE id = ? AND recipient_username = ?");
    if ($notif_check_stmt) {
        $notif_check_stmt->bind_param("is", $notif_id, $current_user_username);
        $notif_check_stmt->execute();
        $notif_data_result = $notif_check_stmt->get_result();
        if ($notif_data = $notif_data_result->fetch_assoc()) {
            // Mark the notification as read
            $update_ind_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_username = ?");
            if ($update_ind_stmt) {
                $update_ind_stmt->bind_param("is", $notif_id, $current_user_username);
                $update_ind_stmt->execute();
                $update_ind_stmt->close();
            }

            // Contextual redirect logic
            if (($notif_data['type'] === 'job_approved' || $notif_data['type'] === 'job_rejected') && !empty($notif_data['job_id'])) {
                $redirect_url = "job_details.php?job_id=".$notif_data['job_id'];
            } elseif (strpos($notif_data['message'], 'connection request') !== false) {
                $redirect_url = "connections.php";
            } elseif (strpos($notif_data['message'], 'New message from') !== false || strpos($notif_data['message'], 'sent you a message') !== false) {
                preg_match("/New message from (\\w+)/", $notif_data['message'], $matches);
                $sender_username_from_notif = $matches[1] ?? null;
                if ($sender_username_from_notif) {
                    $redirect_url = "message.php?receiver=" . urlencode($sender_username_from_notif);
                }
            }
            $notif_check_stmt->close();
        }
    }
    header("Location: " . $redirect_url); // Redirect after processing
    exit();
}

// Fetch all notifications for the current user
$all_notifications = [];
$notifications_stmt = $conn->prepare("SELECT id, message, type, job_id, created_at, is_read FROM notifications WHERE recipient_username = ? ORDER BY created_at DESC");
if ($notifications_stmt) {
    $notifications_stmt->bind_param("s", $current_user_username);
    $notifications_stmt->execute();
    $notifications_result = $notifications_stmt->get_result();
    while ($row = $notifications_result->fetch_assoc()) {
        $all_notifications[] = $row;
    }
    $notifications_stmt->close();
} else {
    error_log("Failed to prepare statement for fetching notifications: " . $conn->error);
    // Optionally set a page error message
}

// Pre-fetch job details for notifications that have a related_id and relevant type
$job_ids_to_fetch = [];
foreach ($all_notifications as $notification) {
    if (!empty($notification['job_id']) && ($notification['type'] === 'job_approved' || $notification['type'] === 'job_rejected' || $notification['type'] === 'job_application')) {
        $job_ids_to_fetch[$notification['job_id']] = true; // Use keys for uniqueness
    }
}
$job_ids_to_fetch = array_keys($job_ids_to_fetch);

// $job_details_map = []; // This map is useful if you need job titles directly in the notification list before clicking
// However, the current display logic only shows the message string which should already be self-contained.
// The pre-fetching was mainly for the old redirection logic. Let's simplify: the link will contain the job_id.

// The message in our new notifications table (job_approved, job_rejected) already contains the job title.
// So, we might not need the $job_details_map for displaying the list itself, but it was used for direct linking.
// The new linking logic passes through notifications.php which then redirects.
// Ensure PHP block is properly closed before HTML starts
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - CareerLynk</title>
    <?php // Styles are handled by header.php and style.css ?>
</head>
<body class="bg-gray-100 min-h-screen">

<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-200">
        <h1 class="text-3xl font-bold text-gray-800">Your Notifications</h1>
        <?php if (!empty($all_notifications)): ?>
        <form method="post" action="notifications.php">
            <button type="submit" name="mark_all_as_read"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
                Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($all_notifications)): ?>
        <ul class="space-y-4">
            <?php foreach ($all_notifications as $notification): ?>
                <?php
                $message_text = htmlspecialchars($notification['message']);
                $notif_id_for_link = $notification['id'];
                // All notification links now go through notifications.php?notif_id=... 
                // The script will mark it as read and then redirect to the correct final page based on the notification type.
                $final_link_url = "notifications.php?notif_id=" . $notif_id_for_link;
                ?>
                <li class="p-4 rounded-lg shadow-sm transition-all duration-150 ease-in-out
                           <?php echo ($notification['is_read'] == 0) ? 'bg-blue-50 hover:bg-blue-100 border-l-4 border-blue-500 font-medium' : 'bg-white hover:bg-gray-50 border-l-4 border-transparent'; ?>">
                    <a href="<?php echo htmlspecialchars($final_link_url); ?>" class="block group">
                        <div class="flex items-center justify-between">
                            <p class="text-sm <?php echo ($notification['is_read'] == 0) ? 'text-blue-700' : 'text-gray-700'; ?> group-hover:text-blue-600">
                                <?php echo $message_text; ?>
                            </p>
                            <?php if ($notification['is_read'] == 0): ?>
                                <span class="w-3 h-3 bg-blue-500 rounded-full flex-shrink-0" title="Unread"></span>
                            <?php endif; ?>
                        </div>
                        <small class="text-xs <?php echo ($notification['is_read'] == 0) ? 'text-blue-500' : 'text-gray-500'; ?> mt-1 block">
                            <?php echo date("M d, Y \\a\\t g:i A", strtotime($notification['created_at'])); ?>
                        </small>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-center py-12">
             <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No notifications yet</h3>
            <p class="mt-1 text-sm text-gray-500">We'll let you know when something new happens.</p>
        </div>
    <?php endif; ?>
</main>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
</body>
</html>
