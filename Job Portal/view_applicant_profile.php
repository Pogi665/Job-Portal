<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0; // Get the job_id from URL
$applicant = isset($_GET['applicant']) ? $_GET['applicant'] : '';

// Fetch the applicant's data
$query = "SELECT * FROM job_applications WHERE job_id = ? AND applicant = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $job_id, $applicant);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Applicant Profile</title>
        <link rel="stylesheet" href="style.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }

            header {
                background-color: #333;
                color: white;
                padding: 15px;
                text-align: center;
                font-size: 1.5em;
            }

            main {
                max-width: 900px;
                margin: 50px auto;
                padding: 20px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            h1 {
                text-align: center;
                color: #333;
            }

            .profile-details {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .profile-details p {
                font-size: 1.1em;
                color: #555;
                line-height: 1.5;
            }

            .profile-details a {
                color: #black;
                text-decoration: none;
            }

            .profile-details a:hover {
                text-decoration: underline;
            }

            .back-button {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #007bff;
                color: black; /* Button text color set to black */
                text-align: center;
                border-radius: 6px;
                font-size: 1.1em;
                text-decoration: none;
            }

            .back-button:hover {
                background-color: #0056b3;
                color: white; /* Change text color to white on hover */
            }

            .section-title {
                font-size: 1.3em;
                font-weight: bold;
                color: #333;
                margin-bottom: 10px;
            }

        </style>
    </head>
    <body>
    <?php include 'header.php'; ?>

    <main>
        <h1>Applicant Profile</h1>
        <div class="profile-details">
            <div>
                <span class="section-title">Full Name:</span>
                <p><?php echo htmlspecialchars($application['full_name']); ?></p>
            </div>

            <div>
                <span class="section-title">Contact Number:</span>
                <p><?php echo htmlspecialchars($application['contact_number']); ?></p>
            </div>

            <div>
                <span class="section-title">Email:</span>
                <p><?php echo htmlspecialchars($application['email']); ?></p>
            </div>

            <div>
                <span class="section-title">Resume URL:</span>
                <p><a href="<?php echo htmlspecialchars($application['resume_url']); ?>" target="_blank">View Resume</a></p>
            </div>

            <div>
                <span class="section-title">Cover Letter:</span>
                <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
            </div>

            <a href="jobs.php" class="back-button">Back to Job Listings</a>
        </div>
    </main>

    </body>
    </html>
    <?php
}
$stmt->close();
?>
