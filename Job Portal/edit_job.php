<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];
$role = $_SESSION["role"]; // Assumes role is stored in session

// Only allow job employers to edit their jobs
if ($role !== 'job_employer') {
    header("Location: jobs.php");
    exit();
}

// Get the job ID from the query string
$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;
if (!$job_id) {
    header("Location: jobs.php");
    exit();
}

// Fetch the job details for the employer
$jobQuery = "SELECT * FROM jobs WHERE id = ? AND employer = ?";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("is", $job_id, $username);
$stmt->execute();
$jobResult = $stmt->get_result();

if ($jobResult->num_rows === 0) {
    echo "Job not found or you do not have permission to edit this job.";
    exit();
}

$job = $jobResult->fetch_assoc();

// Handle form submission to update the job
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];

    // Update the job details
    $updateQuery = "UPDATE jobs SET title = ?, description = ?, location = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssi", $title, $description, $location, $job_id);

    if ($updateStmt->execute()) {
        header("Location: jobs.php");
        exit();
    } else {
        echo "Error updating job: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Job</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>


<main>
    <h1>Edit Job</h1>
    <form action="edit_job.php?job_id=<?php echo $job['id']; ?>" method="POST">
        <label for="title">Job Title:</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required><br><br>
        
        <label for="description">Job Description:</label><br>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea><br><br>
        
        <label for="location">Job Location:</label><br>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required><br><br>
        
        <button type="submit">Update Job</button>
    </form>
</main>
</body>
</html>
