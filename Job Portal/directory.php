<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

// Handle connection request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["connect"])) {
    $user_to_connect = $_POST["connect"];

    $stmt = $conn->prepare("INSERT INTO connections (user1, user2, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ss", $username, $user_to_connect);
    $stmt->execute();
    $stmt->close();

    $message = "$username sent you a connection request";
    $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, is_read) VALUES (?, ?, ?, 0)");
    $notifStmt->bind_param("sss", $user_to_connect, $username, $message);
    $notifStmt->execute();
    $notifStmt->close();

    $_SESSION['message'] = "Connection request sent to $user_to_connect.";
    header("Location: directory.php");
    exit();
}

// Query users not already connected or pending
$sql = "SELECT * FROM users WHERE username != '$username' 
        AND username NOT IN (SELECT user1 FROM connections WHERE user2='$username' AND status='accepted') 
        AND username NOT IN (SELECT user2 FROM connections WHERE user1='$username' AND status='accepted')";
$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Directory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .message {
            background-color: #e0ffe0;
            border: 1px solid #8bc34a;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #33691e;
        }
        .directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .user-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background: #fafafa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        .user-card h3 {
            margin: 10px 0;
        }
        .user-card form {
            margin-top: 10px;
        }
        .user-card button {
            background-color: #007bff;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .user-card button:hover {
            background-color: #0056b3;
        }
        .note {
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1>User Directory</h1>

    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='message'><strong>{$_SESSION['message']}</strong></div>";
        unset($_SESSION['message']);
    }
    ?>

    <div class="directory-grid">
    <?php
    while ($row = $res->fetch_assoc()) {
        echo "<div class='user-card'>";
        echo "<h3>{$row['username']}</h3>";
        $check_conn = $conn->query("SELECT * FROM connections 
                                    WHERE (user1='$username' AND user2='{$row['username']}' AND status='pending') 
                                       OR (user2='$username' AND user1='{$row['username']}' AND status='accepted')");
        if ($check_conn->num_rows > 0) {
            echo "<p class='note'>Connection request sent.</p>";
        } else {
            echo "<form method='POST'><button type='submit' name='connect' value='{$row['username']}'>Connect</button></form>";
        }
        echo "</div>";
    }
    ?>
    </div>
</div>
</body>
</html>
