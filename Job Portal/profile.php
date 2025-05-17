<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];
$profile_username = isset($_GET['username']) ? $_GET['username'] : $username;

// Fetch user info including email and phone
$sql = "SELECT fullname, role, bio, location, company, email, phone FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $profile_username);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($fullname, $role, $bio, $location, $company, $email, $phone);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
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
        .profile-info {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .profile-info p {
            font-size: 1.1em;
            line-height: 1.6;
            margin: 10px 0;
        }
        .profile-info strong {
            color: #007BFF;
        }
        .edit-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
        }
        .edit-button:hover {
            background-color: #0056b3;
        }
        .company-info {
            background-color: #eef5ff;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container">
    <h1>Profile of <?php echo htmlspecialchars($profile_username); ?></h1>

    <div class="profile-info">
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>

        <?php if ($role === 'job_employer' && !empty($company)): ?>
            <div class="company-info">
                <p><strong>Company:</strong> <?php echo htmlspecialchars($company); ?></p>
            </div>
        <?php endif; ?>

        <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($bio)); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($location); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
    </div>

    <?php if ($profile_username === $username): ?>
        <a href="edit_profile.php" class="edit-button">Edit Profile</a>
    <?php endif; ?>
</main>

</body>
</html>
