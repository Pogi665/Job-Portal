<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION['username'] ?? null;
$unreadNotifCount = 0;
$unreadMsgCount = 0;

if ($username) {
    // Unread notifications
    $notifQuery = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE recipient_username = ? AND is_read = 0");
    $notifQuery->bind_param("s", $username);
    $notifQuery->execute();
    $notifResult = $notifQuery->get_result();
    $unreadNotifCount = $notifResult->fetch_assoc()['unread_count'];
    $notifQuery->close();

    // Unread messages
    $msgQuery = $conn->prepare("SELECT COUNT(*) as unread_messages FROM messages WHERE receiver = ? AND is_read = 0");
    $msgQuery->bind_param("s", $username);
    $msgQuery->execute();
    $msgResult = $msgQuery->get_result();
    $unreadMsgCount = $msgResult->fetch_assoc()['unread_messages'];
    $msgQuery->close();
}
?>

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
    }

    header {
        background-color: #0056b3;
        color: white;
        padding: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    nav ul {
        list-style: none;
        display: flex;
        justify-content: center;
        gap: 30px;
    }

    nav a {
        color: white;
        text-decoration: none;
        font-size: 18px;
        transition: color 0.3s ease;
    }

    nav a:hover {
        color: #ffd700;
    }

    .badge {
        background: red;
        color: white;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.8em;
        vertical-align: top;
        margin-left: 5px;
    }

    nav .active {
        border-bottom: 2px solid #ffd700;
        padding-bottom: 3px;
    }
</style>

<header>
    <nav>
        <ul>
            <li><a href="dashboard.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="jobs.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'active' : ''; ?>">Jobs</a></li>
            <li><a href="directory.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'directory.php') ? 'active' : ''; ?>">Users</a></li>
            <li><a href="connections.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'connections.php') ? 'active' : ''; ?>">Connections</a></li>
            <li>
                <a href="messages.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'messages.php') ? 'active' : ''; ?>">Messages
                    <?php if ($unreadMsgCount > 0): ?>
                        <span class="badge"><?php echo $unreadMsgCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="notifications.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>">Notifications
                    <?php if ($unreadNotifCount > 0): ?>
                        <span class="badge"><?php echo $unreadNotifCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="profile.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">Profile</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>
