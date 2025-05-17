<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$username = $_SESSION["username"];

// Fetch current user info
$sql = "SELECT fullname, role, bio, location, company, email, phone FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($fullname, $role, $bio, $location, $company, $email, $phone);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_fullname = $_POST["fullname"];
    $new_bio = $_POST["bio"];
    $new_location = $_POST["location"];
    $new_email = $_POST["email"];
    $new_phone = $_POST["phone"];
    $new_company = ($role === "job_employer") ? $_POST["company"] : null;

    $updateSql = "UPDATE users SET fullname=?, bio=?, location=?, email=?, phone=?" . ($role === "job_employer" ? ", company=?" : "") . " WHERE username=?";
    if ($role === "job_employer") {
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssssss", $new_fullname, $new_bio, $new_location, $new_email, $new_phone, $new_company, $username);
    } else {
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssssss", $new_fullname, $new_bio, $new_location, $new_email, $new_phone, $username);
    }

    $updateStmt->execute();
    $updateStmt->close();

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
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
            max-width: 800px;
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

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 1.1em;
            color: #555;
        }

        input[type="text"], input[type="email"], textarea {
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .role-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 6px;
            font-size: 1.1em;
            color: #555;
        }

        .role-section strong {
            color: #333;
        }

        .section-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <h1>Edit Profile</h1>
        <form method="post">
            <div>
                <label for="fullname">Full Name:</label>
                <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
            </div>

            <?php if ($role === "job_employer"): ?>
            <div>
                <label for="company">Company:</label>
                <input type="text" name="company" id="company" value="<?php echo htmlspecialchars($company); ?>">
            </div>
            <?php endif; ?>

            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div>
                <label for="phone">Phone:</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>">
            </div>

            <div>
                <label for="bio">Bio:</label>
                <textarea name="bio" id="bio" rows="5"><?php echo htmlspecialchars($bio); ?></textarea>
            </div>

            <div>
                <label for="location">Location:</label>
                <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location); ?>">
            </div>

            <input type="submit" value="Save Changes">
        </form>
    </main>
</body>
</html>
