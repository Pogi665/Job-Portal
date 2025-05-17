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

// Fetch jobs based on role
if ($role === 'job_employer') {
    $jobsQuery = "
        SELECT jobs.*, COUNT(job_applications.id) as num_applicants
        FROM jobs 
        LEFT JOIN job_applications 
        ON jobs.id = job_applications.job_id
        WHERE jobs.employer = ? 
        GROUP BY jobs.id
        ORDER BY jobs.timestamp DESC
    ";
    
    $stmt = $conn->prepare($jobsQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $jobsResult = $stmt->get_result();
} else {
    $query = "
        SELECT DISTINCT jobs.*, job_applications.status as application_status 
        FROM jobs 
        INNER JOIN job_applications 
        ON jobs.id = job_applications.job_id 
        WHERE job_applications.applicant = ? 
        ORDER BY jobs.timestamp DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $jobsResult = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jobs</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .jobs-page {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
        }

        .jobs-page h1 {
            text-align: center;
            font-size: 2.5em;
            color: #333;
            margin-bottom: 30px;
        }

        .jobs-page button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .jobs-page button:hover {
            background-color: #0056b3;
        }

        .jobs-page form {
            margin-bottom: 30px;
        }

        .jobs-page input, .jobs-page textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .jobs-page ul {
            list-style: none;
            padding: 0;
        }

        .jobs-page li {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .jobs-page a.edit-button,
        .jobs-page a.delete-button {
            margin-right: 10px;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .jobs-page a.edit-button {
            background-color: #28a745;
            color: white;
        }

        .jobs-page a.delete-button {
            background-color: #dc3545;
            color: white;
        }

        .jobs-page em {
            display: block;
            margin-top: 10px;
            font-style: italic;
            color: #555;
        }

        .jobs-page small {
            display: block;
            margin-top: 5px;
            color: #888;
        }

        .jobs-page h2, .jobs-page h3 {
            margin-top: 20px;
        }

        .jobs-page p {
            color: #555;
            font-size: 1.1em;
        }

        .jobs-page span {
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<main class="jobs-page">
<?php if ($role === 'job_employer'): ?>
    <h1>Your Job Listings</h1>

    <button onclick="document.getElementById('postJobForm').style.display='block'">Post a Job</button>

    <div id="postJobForm" style="display: none;">
        <h2>Post a New Job</h2>
        <form action="post_job.php" method="POST">
            <label for="title">Job Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="company">Company Name:</label>
            <input type="text" id="company" name="company" required>

            <label for="description">Job Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="location">Job Location:</label>
            <input type="text" id="location" name="location" required>

            <input type="submit" value="Post Job">
        </form>
        <button onclick="document.getElementById('postJobForm').style.display='none'">Cancel</button>
    </div>

    <?php if ($jobsResult->num_rows > 0): ?>
        <ul>
        <?php while ($job = $jobsResult->fetch_assoc()): ?>
            <li>
                <strong><a href="job_details.php?job_id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></strong><br>
                <em><?php echo htmlspecialchars($job['description']); ?></em>
                <small>Company: <?php echo htmlspecialchars($job['company']); ?></small>
                <small>Posted on: <?php echo date("F j, Y, g:i a", strtotime($job['timestamp'])); ?></small>
                <small>Applicants: <?php echo $job['num_applicants']; ?> applicants</small>

                <a href="edit_job.php?job_id=<?php echo $job['id']; ?>" class="edit-button">Edit</a>
                <a href="delete_job.php?job_id=<?php echo $job['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this job?')">Delete</a>
                <hr>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>You have not posted any jobs yet.</p>
    <?php endif; ?>
<?php else: ?>
    <h1>Your Applied Jobs</h1>

    <?php if ($jobsResult->num_rows > 0): ?>
        <ul>
        <?php while ($job = $jobsResult->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($job['title']); ?></strong> at 
                <?php echo htmlspecialchars($job['company']); ?><br>
                <em><?php echo htmlspecialchars($job['description']); ?></em>
                <small>Posted on: <?php echo date("F j, Y, g:i a", strtotime($job['timestamp'])); ?></small>

                <?php
                $application_status = htmlspecialchars($job['application_status']);
                if ($application_status === 'pending') {
                    echo "<span style='color: orange;'>Status: Pending</span>";
                } elseif ($application_status === 'accepted') {
                    echo "<span style='color: green;'>Status: Accepted</span>";
                } elseif ($application_status === 'rejected') {
                    echo "<span style='color: red;'>Status: Rejected</span>";
                }
                ?>
                <hr>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>You have not applied to any jobs yet.</p>
    <?php endif; ?>
<?php endif; ?>
</main>
</body>
</html>
