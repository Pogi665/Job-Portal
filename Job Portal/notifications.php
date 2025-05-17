<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

// Handle marking all as read
if (isset($_POST['mark_all_as_read'])) {
    $updateQuery = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_username = ? AND is_read = 0");
    $updateQuery->bind_param("s", $username);
    $updateQuery->execute();
    $updateQuery->close();

    // Refresh the page after marking all notifications as read
    header("Location: notifications.php");
    exit();
}

// Handle marking individual notification as read and redirecting
if (isset($_GET['notif_id'])) {
    $notif_id = intval($_GET['notif_id']);

    // Fetch the notification
    $notifStmt = $conn->prepare("SELECT message FROM notifications WHERE id = ? AND recipient_username = ?");
    $notifStmt->bind_param("is", $notif_id, $username);
    $notifStmt->execute();
    $notifResult = $notifStmt->get_result();

    if ($notifResult->num_rows > 0) {
        $notif = $notifResult->fetch_assoc();

        // Mark the notification as read
        $updateQuery = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_username = ?");
        $updateQuery->bind_param("is", $notif_id, $username);
        $updateQuery->execute();
        $updateQuery->close();

        $message = $notif['message'];

        if (strpos($message, 'connection request') !== false) {
            header("Location: connections.php");
            exit();
        }
    }

    header("Location: notifications.php");
    exit();
}

// Fetch notifications for current user
$notificationsQuery = "SELECT DISTINCT * FROM notifications WHERE recipient_username = ? ORDER BY timestamp DESC";
$stmt = $conn->prepare($notificationsQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$notificationsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
            background-color: white;
            min-height: 100vh;
        }
        h1 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
        }
        ul.notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        ul.notification-list li {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
            transition: background-color 0.3s;
        }
        ul.notification-list li:hover {
            background-color: #eef5ff;
        }
        .unread {
            font-weight: bold;
            background-color: #e6f0ff;
            border-left: 4px solid #007BFF;
        }
        a {
            color: #007BFF;
            text-decoration: none;
            font-size: 1em;
        }
        a:hover {
            text-decoration: underline;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #777;
            font-size: 0.85em;
        }
        .mark-all-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .mark-all-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container">
    <h1>Your Notifications</h1>

    <!-- Mark all as read button -->
    <form method="post">
        <button type="submit" name="mark_all_as_read" class="mark-all-btn">Mark All as Read</button>
    </form>

    <?php if ($notificationsResult->num_rows > 0): ?>
        <ul class="notification-list">
            <?php while ($notification = $notificationsResult->fetch_assoc()): ?>
                <li class="<?php echo ($notification['is_read'] == 0) ? 'unread' : ''; ?>">
                    <?php
                    $message = htmlspecialchars($notification['message']);
                    $notif_id = $notification['id'];

                    if (!empty($notification['job_id'])) {
                        $job_id = $notification['job_id'];

                        $jobQuery = "SELECT employer FROM jobs WHERE id = ?";
                        $jobStmt = $conn->prepare($jobQuery);
                        $jobStmt->bind_param("i", $job_id);
                        $jobStmt->execute();
                        $jobResult = $jobStmt->get_result();

                        if ($jobResult->num_rows > 0) {
                            $job = $jobResult->fetch_assoc();
                            if ($job['employer'] === $username) {
                                echo "<a href='job_details.php?job_id=$job_id&notif_id=$notif_id'>$message</a>";
                            } else {
                                echo "<a href='notifications.php?notif_id=$notif_id'>$message</a>";
                            }
                        } else {
                            echo "<a href='notifications.php?notif_id=$notif_id'>$message</a>";
                        }

                        $jobStmt->close();
                    } else {
                        echo "<a href='notifications.php?notif_id=$notif_id'>$message</a>";
                    }
                    ?>
                    <small><?php echo date("F j, Y, g:i a", strtotime($notification['timestamp'])); ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>You have no notifications.</p>
    <?php endif; ?>
</main>
</body>
</html>
