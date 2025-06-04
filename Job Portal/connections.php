<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

require 'database_connection.php';

// Check if the database connection from the include was successful
if ($conn->connect_error) {
    // This is a page for logged-in users. If DB fails, it's a critical error.
    // Log the error and display a simple error message, or redirect to an error page.
    error_log("Database connection failed in connections.php: (" . $conn->connect_errno . ") " . $conn->connect_error);
    // For a user-facing page, it's better to show a friendly error than die().
    // We can set a session message and let the header/footer structure display it, or die with a message.
    // For now, let's die to make the error obvious during development.
    die("A critical database error occurred. Please try again later or contact support."); 
}

$username = $_SESSION["username"];

// Handle Accept
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept"])) {
    $user_to_accept = $_POST["accept"];
    // Fetch full_name of the user who sent the request to make notification more friendly
    $sender_fullname_query = $conn->prepare("SELECT full_name FROM users WHERE username = ?");
    $sender_fullname = $user_to_accept; // Fallback to username if full_name not found
    if($sender_fullname_query){
        $sender_fullname_query->bind_param("s", $user_to_accept);
        $sender_fullname_query->execute();
        $sender_result = $sender_fullname_query->get_result();
        if($sender_row = $sender_result->fetch_assoc()){
            $sender_fullname = $sender_row['full_name'];
        }
        $sender_fullname_query->close();
    }

    $stmt = $conn->prepare("UPDATE connections SET status='accepted', accepted_at=NOW() WHERE user1=? AND user2=? AND status='pending'");
    $stmt->bind_param("ss", $user_to_accept, $username);
    $stmt->execute(); $stmt->close();

    $current_user_fullname_query = $conn->prepare("SELECT full_name FROM users WHERE username = ?");
    $current_user_display_name = $username; // Fallback to username
    if($current_user_fullname_query){
        $current_user_fullname_query->bind_param("s", $username);
        $current_user_fullname_query->execute();
        $current_user_result = $current_user_fullname_query->get_result();
        if($current_user_row = $current_user_result->fetch_assoc()){
            $current_user_display_name = $current_user_row['full_name'];
        }
        $current_user_fullname_query->close();
    }

    $message = htmlspecialchars($current_user_display_name) . " accepted your connection request.";
    $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, is_read) VALUES (?, ?, ?, 'connection_accepted', 0)");
    $notifStmt->bind_param("sss", $user_to_accept, $username, $message);
    $notifStmt->execute(); $notifStmt->close();

    $_SESSION['message'] = "Connection request from " . htmlspecialchars($sender_fullname) . " accepted.";
    header("Location: connections.php"); exit();
}

// Handle Reject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reject"])) {
    $user_to_reject = $_POST["reject"];
    // Fetch full_name of the user whose request is being rejected for the message
    $rejected_fullname_query = $conn->prepare("SELECT full_name FROM users WHERE username = ?");
    $rejected_fullname = $user_to_reject; // Fallback to username
    if($rejected_fullname_query){
        $rejected_fullname_query->bind_param("s", $user_to_reject);
        $rejected_fullname_query->execute();
        $rejected_result = $rejected_fullname_query->get_result();
        if($rejected_row = $rejected_result->fetch_assoc()){
            $rejected_fullname = $rejected_row['full_name'];
        }
        $rejected_fullname_query->close();
    }

    $stmt = $conn->prepare("DELETE FROM connections WHERE user1=? AND user2=? AND status='pending'");
    $stmt->bind_param("ss", $user_to_reject, $username);
    $stmt->execute(); $stmt->close();

    $_SESSION['message'] = "Connection request from " . htmlspecialchars($rejected_fullname) . " rejected.";
    header("Location: connections.php"); exit();
}

// Fetch accepted connections with their full names
$conn_sql = "
    SELECT 
        c.user1, 
        c.user2, 
        u1.full_name as user1_fullname,
        u2.full_name as user2_fullname
    FROM connections c
    JOIN users u1 ON c.user1 = u1.username
    JOIN users u2 ON c.user2 = u2.username
    WHERE (c.user1=? OR c.user2=?) AND c.status='accepted'";
$connStmt = $conn->prepare($conn_sql);
$connStmt->bind_param("ss", $username, $username);
$connStmt->execute();
$connResult = $connStmt->get_result();

$connections_data = [];
while ($row = $connResult->fetch_assoc()) {
    if ($row['user1'] == $username) {
        $connections_data[] = ['username' => $row['user2'], 'full_name' => $row['user2_fullname']];
    } else {
        $connections_data[] = ['username' => $row['user1'], 'full_name' => $row['user1_fullname']];
    }
}

// Fetch pending requests with sender's full name
$pending_sql = "
    SELECT c.user1 as pending_username, u.full_name as pending_fullname 
    FROM connections c
    JOIN users u ON c.user1 = u.username
    WHERE c.user2=? AND c.status='pending'";
$pending_requests_stmt = $conn->prepare($pending_sql);
$pending_requests_stmt->bind_param("s", $username);
$pending_requests_stmt->execute();
$pending_result = $pending_requests_stmt->get_result();

$pending_requests_data = [];
while ($row = $pending_result->fetch_assoc()){
    $pending_requests_data[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connections - CareerLynk</title>
    <?php // style.css is included via header.php. Inline styles removed. ?>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Your Connections</h1>

    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-info p-4 mb-6 rounded-md' role='alert'><strong>" . htmlspecialchars($_SESSION['message']) . "</strong></div>";
        unset($_SESSION['message']);
    }
    ?>

    <?php if (count($connections_data) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        <?php foreach ($connections_data as $conn_user): ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-gray-700 mb-1"><?php echo htmlspecialchars($conn_user['full_name'] ?? $conn_user['username']); ?></h3>
                <p class="text-sm text-gray-500 mb-3">@<?php echo htmlspecialchars($conn_user['username']); ?></p>
                <div class="space-x-2">
                    <form method="GET" action="message.php" class="inline-block">
                        <input type="hidden" name="receiver" value="<?php echo htmlspecialchars($conn_user['username']); ?>">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">Send Message</button>
                    </form>
                    <a href="profile.php?username=<?php echo urlencode($conn_user['username']); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out inline-block">View Profile</a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600 mb-10">You have no connections yet.</p>
    <?php endif; ?>

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Pending Connection Requests</h2>
    <?php if (count($pending_requests_data) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($pending_requests_data as $request): 
            $pending_user_username = $request['pending_username'];
            $pending_user_fullname = $request['pending_fullname'];
        ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-gray-700 mb-1"><?php echo htmlspecialchars($pending_user_fullname ?? $pending_user_username); ?></h3>
                <p class="text-sm text-gray-500 mb-3">@<?php echo htmlspecialchars($pending_user_username); ?></p>
                <div class="space-x-2">
                    <form method="POST" class="inline-block">
                        <button type="submit" name="accept" value="<?php echo htmlspecialchars($pending_user_username); ?>" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">Accept</button>
                    </form>
                    <form method="POST" class="inline-block">
                        <button type="submit" name="reject" value="<?php echo htmlspecialchars($pending_user_username); ?>" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">Reject</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No pending connection requests.</p>
    <?php endif; ?>
</main>

<?php 
if (isset($connStmt) && $connStmt) $connStmt->close(); // Added isset check
if (isset($pending_requests_stmt) && $pending_requests_stmt) $pending_requests_stmt->close(); // Added isset check and renamed variable
if (isset($conn) && $conn) $conn->close(); // Added isset check
?>
</body>
</html>
