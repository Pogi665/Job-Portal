<?php
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareerLynk - Log In</title>
    <link rel="stylesheet" href="style.css">
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

        main {
            padding: 60px 20px;
            max-width: 500px;
            margin: auto;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        h2 {
            font-size: 2.5em;
            color: #0056b3;
            margin-bottom: 30px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            text-align: left;
            margin-bottom: 5px;
            font-weight: 600;
        }

        input {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        button {
            padding: 15px;
            background-color: #007bff;
            color: white;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #eaeaea;
            font-size: 0.9em;
            margin-top: 60px;
        }
    </style>
</head>
<body>

<header>
    <nav>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="signup.html">Sign Up</a></li>
            <li><a href="login.php">Log In</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Log In to Your Account</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="username">Username</label>
        <input type="text" name="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" required>

        <button type="submit">Log In</button>
    </form>
</main>

<footer>
    &copy; 2025 CareerLynk. All rights reserved.
</footer>

</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "job_portal");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $user = $_POST["username"];
    $pass = $_POST["password"];
    $sql = "SELECT * FROM users WHERE username='$user'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row["password"])) {
            $_SESSION["username"] = $user;
            $_SESSION["role"] = $row["role"];
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "User not found!";
        header("Location: login.php");
        exit();
    }
}
?>
