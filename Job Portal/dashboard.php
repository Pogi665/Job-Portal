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

// Get the search term if available
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

// Modify the query to filter by the search term
$jobsQuery = "
    SELECT * 
    FROM jobs 
    WHERE id NOT IN (SELECT job_id FROM job_applications WHERE applicant = ?) 
    AND (title LIKE ? OR employer LIKE ?) 
    ORDER BY timestamp DESC
";

$stmt = $conn->prepare($jobsQuery);
$searchTermLike = '%' . $searchTerm . '%';
$stmt->bind_param("sss", $username, $searchTermLike, $searchTermLike);
$stmt->execute();
$jobsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
        }

        .dashboard h1 {
            text-align: center;
            font-size: 2.5em;
            color: #0056b3;
            margin-bottom: 30px;
        }

        .dashboard form {
            text-align: center;
            margin-bottom: 30px;
        }

        .dashboard input[type="text"] {
            width: 60%;
            padding: 10px 15px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .dashboard button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 10px;
        }

        .dashboard button:hover {
            background-color: #0056b3;
        }

        .dashboard ul {
            list-style: none;
            padding: 0;
        }

        .dashboard li {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .dashboard strong {
            font-size: 1.4em;
            color: #333;
        }

        .dashboard em {
            display: block;
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }

        .dashboard small {
            display: block;
            margin-top: 5px;
            color: #888;
        }

        .dashboard form button {
            margin-top: 10px;
        }

        .dashboard p {
            text-align: center;
            font-size: 1.2em;
            color: #666;
        }
    </style>
    <script>
        function handleApply(event, jobId) {
            event.preventDefault();
            window.location.href = "apply_for_job.php?job_id=" + jobId;
        }
    </script>
</head>
<body>
<?php include 'header.php'; ?>

<main class="dashboard">
    <h1>Available Job Listings</h1>

    <!-- Search Bar -->
    <form method="POST">
        <input type="text" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Search</button>
    </form>

    <?php if ($jobsResult->num_rows > 0): ?>
        <ul>
        <?php while ($job = $jobsResult->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                <span> at <?php echo htmlspecialchars($job['employer']); ?></span><br>
                <em><?php echo nl2br(htmlspecialchars($job['description'])); ?></em>
                <small>Posted on: <?php echo date("F j, Y, g:i a", strtotime($job['timestamp'])); ?></small>

                <?php if ($role === 'job_seeker'): ?>
                    <form method="POST" onsubmit="handleApply(event, <?php echo $job['id']; ?>);">
                        <input type="hidden" name="apply_job_id" value="<?php echo $job['id']; ?>">
                        <button type="submit">Apply Now</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>🎯 No job listings available based on your search.</p>
    <?php endif; ?>
</main>

</body>
</html>
