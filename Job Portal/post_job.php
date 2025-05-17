<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION["username"];
$role = $_SESSION["role"];

if ($role !== 'job_employer') {
    echo "You are not authorized to post a job.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $description = $_POST['description'];
    $location = $_POST['location'];

    $insertQuery = "INSERT INTO jobs (title, company, description, location, employer, timestamp) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssss", $title, $company, $description, $location, $username);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Notify connected users
        $job_title = htmlspecialchars($title);
        $conn_stmt = $conn->prepare("SELECT user2 FROM connections WHERE user1=? AND status='connected'");
        $conn_stmt->bind_param("s", $username);
        $conn_stmt->execute();
        $connected_users = $conn_stmt->get_result();

        $message = "$username posted a new job: $job_title";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, type, message) VALUES (?, ?, 'job_post', ?)");

        while ($row = $connected_users->fetch_assoc()) {
            $recipient = $row['user2'];
            $notif_stmt->bind_param("sss", $recipient, $username, $message);
            $notif_stmt->execute();
        }

        header("Location: jobs.php?msg=Job posted successfully.");
        exit();
    } else {
        echo "Error posting the job.";
    }
}
?>
