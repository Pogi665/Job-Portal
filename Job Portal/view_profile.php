<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

// Ensure the applicant's username is passed in the URL
if (isset($_GET['applicant'])) {
    $applicant_username = $_GET['applicant']; // Get the applicant's username from the URL

    // Fetch applicant's profile information
    $profileQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($profileQuery);
    $stmt->bind_param("s", $applicant_username);
    $stmt->execute();
    $profileResult = $stmt->get_result();

    if ($profileResult->num_rows > 0) {
        $profile = $profileResult->fetch_assoc();
    } else {
        echo "Profile not found.";
        exit();
    }
} else {
    echo "No applicant specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile: <?php echo htmlspecialchars($profile['username']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>


<main>
    <h1>Profile of <?php echo htmlspecialchars($profile['username']); ?></h1>

    <strong>Full Name:</strong> <?php echo htmlspecialchars($profile['fullname']); ?><br>
    <strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?><br>
    <strong>Bio:</strong> <em><?php echo htmlspecialchars($profile['bio']); ?></em><br>
    <!-- Add other profile information as needed -->

</main>
</body>
</html>
