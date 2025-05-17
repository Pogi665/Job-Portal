<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// Fetch current user info
$userQuery = $conn->prepare("SELECT fullname, phone, email FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();
$userQuery->close();

if (!$user) {
    echo "User information not found.";
    exit();
}

// Handle the application form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $resume_url = filter_var($_POST['resume_url'], FILTER_SANITIZE_URL);
    $cover_letter = filter_var($_POST['cover_letter'], FILTER_SANITIZE_STRING);

    $full_name = $user['fullname'];
    $contact_number = $user['phone'];
    $email = $user['email'];

    if (filter_var($resume_url, FILTER_VALIDATE_URL) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("INSERT INTO job_applications (job_id, applicant, resume_url, full_name, contact_number, email, cover_letter) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $job_id, $username, $resume_url, $full_name, $contact_number, $email, $cover_letter);
        
        if ($stmt->execute()) {
            // ✅ Fetch employer and job title
            $jobQuery = $conn->prepare("SELECT employer, title FROM jobs WHERE id = ?");
            $jobQuery->bind_param("i", $job_id);
            $jobQuery->execute();
            $jobResult = $jobQuery->get_result();

            if ($jobRow = $jobResult->fetch_assoc()) {
                $employer = $jobRow['employer'];
                $job_title = $jobRow['title'];

                // ✅ Send notification to employer
                $notifMsg = "$full_name applied for your job post: $job_title";
                $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, message, job_id, timestamp) VALUES (?, ?, ?, NOW())");
                $notifStmt->bind_param("ssi", $employer, $notifMsg, $job_id);
                $notifStmt->execute();
                $notifStmt->close();

                // ✅ Check if the user and employer are already connected
                $checkConn = $conn->prepare("SELECT * FROM connections WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?)");
                $checkConn->bind_param("ssss", $username, $employer, $employer, $username);
                $checkConn->execute();
                $connResult = $checkConn->get_result();

                if ($connResult->num_rows > 0) {
                    $row = $connResult->fetch_assoc();
                    if ($row['status'] !== 'accepted') {
                        $updateConn = $conn->prepare("UPDATE connections SET status = 'accepted' WHERE id = ?");
                        $updateConn->bind_param("i", $row['id']);
                        $updateConn->execute();
                        $updateConn->close();
                    }
                } else {
                    $insertConn = $conn->prepare("INSERT INTO connections (user1, user2, status) VALUES (?, ?, 'accepted')");
                    $insertConn->bind_param("ss", $username, $employer);
                    $insertConn->execute();
                    $insertConn->close();

                    // ✅ Send notification to job seeker about new connection
                    $connectionNotifMsg = "You are now connected to $employer.";
                    $connectionNotifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, message, job_id, timestamp) VALUES (?, ?, NULL, NOW())");
                    $connectionNotifStmt->bind_param("ss", $username, $connectionNotifMsg);
                    $connectionNotifStmt->execute();
                    $connectionNotifStmt->close();
                }
            }

            header("Location: dashboard.php");
            exit();
        } else {
            echo "Error submitting application.";
        }
        $stmt->close();
    } else {
        echo "Please provide a valid resume URL.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Job</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-size: 1.1em;
            color: #555;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .form-group textarea {
            resize: vertical;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container">
    <h1>Apply for Job</h1>

    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>

    <form action="apply_for_job.php?job_id=<?php echo $job_id; ?>" method="POST">
        <div class="form-group">
            <label for="resume_url">Resume URL:</label>
            <input type="url" name="resume_url" required>
        </div>

        <div class="form-group">
            <label for="cover_letter">Cover Letter:</label>
            <textarea name="cover_letter" rows="4" placeholder="Write a brief cover letter..."></textarea>
        </div>

        <div class="form-group">
            <button type="submit" class="button">Submit Application</button>
        </div>
    </form>
</main>

</body>
</html>
