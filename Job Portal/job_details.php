<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];
$role = $_SESSION["role"];

// ✅ Mark notification as read if notif_id is passed
if (isset($_GET['notif_id'])) {
    $notif_id = intval($_GET['notif_id']);
    $updateQuery = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $updateQuery->bind_param("i", $notif_id);
    $updateQuery->execute();
    $updateQuery->close();
}

// ✅ Handle POST from accept form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_acceptance'])) {
    $job_id = intval($_POST['job_id']);
    $applicant_id = $_POST['applicant_id'];
    $custom_message = trim($_POST['message']);

    // Update application status
    $updateStatus = $conn->prepare("UPDATE job_applications SET status = 'accepted' WHERE job_id = ? AND applicant = ?");
    $updateStatus->bind_param("is", $job_id, $applicant_id);
    $updateStatus->execute();
    $updateStatus->close();

    // Insert message to messages table
    $insertMessage = $conn->prepare("INSERT INTO messages (sender, receiver, message, timestamp, is_read)
                                     VALUES (?, ?, ?, NOW(), 0)");
    $insertMessage->bind_param("sss", $username, $applicant_id, $custom_message);
    $insertMessage->execute();
    $insertMessage->close();

    // Get job title
    $jobTitleQuery = $conn->prepare("SELECT title FROM jobs WHERE id = ?");
    $jobTitleQuery->bind_param("i", $job_id);
    $jobTitleQuery->execute();
    $jobTitleResult = $jobTitleQuery->get_result();
    $jobTitleRow = $jobTitleResult->fetch_assoc();
    $job_title = $jobTitleRow['title'] ?? 'a job';
    $jobTitleQuery->close();

    // Insert notification to notifications table
    $notif_msg = "You have been accepted for the job: " . $job_title;
    $notif_type = "job_acceptance";
    $created_at = date("Y-m-d H:i:s");

    $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, message, type, timestamp, is_read, job_id)
                                 VALUES (?, ?, ?, ?, 0, ?)");
    $notifStmt->bind_param("ssssi", $applicant_id, $notif_msg, $notif_type, $created_at, $job_id);
    $notifStmt->execute();
    $notifStmt->close();

    header("Location: job_details.php?job_id=$job_id");
    exit();
}

// ✅ Main job and applicant fetch logic
if (isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);

    $jobQuery = "SELECT * FROM jobs WHERE id = ? AND employer = ?";
    $stmt = $conn->prepare($jobQuery);
    $stmt->bind_param("is", $job_id, $username);
    $stmt->execute();
    $jobResult = $stmt->get_result();

    if ($jobResult->num_rows > 0) {
        $job = $jobResult->fetch_assoc();
    } else {
        echo "Job not found or you do not have permission to view it.";
        exit();
    }

    // Fetch applicants by status
    $applicantsQuery = "SELECT applicant, status FROM job_applications WHERE job_id = ?";
    $applicantsStmt = $conn->prepare($applicantsQuery);
    $applicantsStmt->bind_param("i", $job_id);
    $applicantsStmt->execute();
    $applicantsResult = $applicantsStmt->get_result();
} else {
    echo "No job ID specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #333;
            color: white;
            padding: 10px 0;
            text-align: center;
        }

        main {
            max-width: 900px;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        p {
            color: #555;
            font-size: 16px;
            line-height: 1.5;
        }

        .applicant-list ul {
            list-style-type: none;
            padding: 0;
        }

        .applicant-list li {
            padding: 10px;
            background-color: #f9f9f9;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .applicant-list li a {
            color: #007bff;
            text-decoration: none;
        }

        .applicant-list li a:hover {
            text-decoration: underline;
        }

        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            margin-top: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .notification-message {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>Job Details: <?php echo htmlspecialchars($job['title']); ?></h1>

    <p><strong>Employer:</strong> <?php echo htmlspecialchars($job['employer']); ?></p>
    <p><strong>Description:</strong> <em><?php echo htmlspecialchars($job['description']); ?></em></p>
    <p><strong>Posted on:</strong> <?php echo date("F j, Y, g:i a", strtotime($job['timestamp'])); ?></p>

    <!-- ✅ Congratulatory Message Form -->
    <?php if (isset($_GET['show_form']) && isset($_GET['applicant'])): ?>
        <hr>
        <form method="POST" action="job_details.php">
            <input type="hidden" name="job_id" value="<?= $job_id ?>">
            <input type="hidden" name="applicant_id" value="<?= htmlspecialchars($_GET['applicant']) ?>">
            <label for="message">Write a message to congratulate the applicant:</label><br>
            <textarea name="message" id="message" rows="5" cols="50" required></textarea><br><br>
            <button type="submit" name="submit_acceptance">Send and Accept</button>
        </form>
        <hr>
    <?php endif; ?>

    <div class="applicant-list">
        <!-- Pending Applicants -->
        <div class="pending-applicants">
            <div class="section-title">Pending Applicants:</div>
            <?php
            $applicantsResult->data_seek(0); // Reset the result pointer
            $has_pending = false;
            while ($applicant = $applicantsResult->fetch_assoc()) {
                if ($applicant['status'] === 'pending') {
                    $has_pending = true;
                    echo "<li>
                            " . htmlspecialchars($applicant['applicant']) . " - 
                            <a href='view_applicant_profile.php?job_id=$job_id&applicant=" . urlencode($applicant['applicant']) . "'>View Profile</a> |
                            <a href='job_details.php?job_id=$job_id&show_form=1&applicant=" . urlencode($applicant['applicant']) . "'>Accept</a> |
                            <a href='reject_applicant.php?job_id=$job_id&applicant=" . urlencode($applicant['applicant']) . "'>Reject</a>
                          </li>";
                }
            }
            if (!$has_pending) {
                echo "<p>No pending applicants.</p>";
            }
            ?>
        </div>

        <!-- Accepted Applicants -->
        <div class="accepted-applicants">
            <div class="section-title">Accepted Applicants:</div>
            <?php
            $applicantsResult->data_seek(0); // Reset the result pointer
            $has_accepted = false;
            while ($applicant = $applicantsResult->fetch_assoc()) {
                if ($applicant['status'] === 'accepted') {
                    $has_accepted = true;
                    echo "<li>
                            " . htmlspecialchars($applicant['applicant']) . " - Accepted |
                            <a href='view_applicant_profile.php?job_id=$job_id&applicant=" . urlencode($applicant['applicant']) . "'>View Profile</a>
                          </li>";
                }
            }
            if (!$has_accepted) {
                echo "<p>No accepted applicants.</p>";
            }
            ?>
        </div>

        <!-- Rejected Applicants -->
        <div class="rejected-applicants">
            <div class="section-title">Rejected Applicants:</div>
            <?php
            $applicantsResult->data_seek(0); // Reset the result pointer
            $has_rejected = false;
            while ($applicant = $applicantsResult->fetch_assoc()) {
                if ($applicant['status'] === 'rejected') {
                    $has_rejected = true;
                    echo "<li>
                            " . htmlspecialchars($applicant['applicant']) . " - Rejected |
                            <a href='view_applicant_profile.php?job_id=$job_id&applicant=" . urlencode($applicant['applicant']) . "'>View Profile</a>
                          </li>";
                }
            }
            if (!$has_rejected) {
                echo "<p>No rejected applicants.</p>";
            }
            ?>
        </div>
    </div>
</main>

</body>
</html>
