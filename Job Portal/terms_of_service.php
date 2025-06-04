<?php
session_start();
$is_public_page = true; // Flag for header.php to show public view
include_once 'database_connection.php'; // Ensure this path is correct

$page_key = 'terms-of-service';
$page_content = '';
$page_title = 'Terms of Service'; // Default title
$last_updated = '';

// Fetch content from database
$stmt = $conn->prepare("SELECT page_title, content, updated_at FROM site_content WHERE page_key = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $page_key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $page_title = htmlspecialchars($row['page_title']);
        $page_content = $row['content']; // HTML content, displayed as is
        $last_updated = !empty($row['updated_at']) ? date("F j, Y", strtotime($row['updated_at'])) : date("F j, Y");
    } else {
        // Fallback content if page_key not found, though it should be inserted
        $page_content = "<p>The terms of service content is not available at the moment. Please check back later.</p>";
        $last_updated = date("F j, Y");
    }
    $stmt->close();
} else {
    // Database error
    $page_content = "<p>Could not retrieve terms of service due to a database error. Please try again later.</p>";
    $last_updated = date("F j, Y");
    // Log error: error_log("Failed to prepare statement for terms_of_service: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CareerLynk</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .content-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .content-container h1 {
            font-size: 2em;
            margin-bottom: 0.5em;
            color: #333;
        }
        .content-container h2 {
            font-size: 1.5em;
            margin-top: 1em;
            margin-bottom: 0.5em;
            color: #555;
        }
        .content-container p, .content-container ul {
            margin-bottom: 1em;
            line-height: 1.6;
            color: #666;
        }
        .content-container ul {
            list-style-position: inside;
            padding-left: 20px;
        }
        .content-container li {
            margin-bottom: 0.5em;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #3b82f6; /* Tailwind blue-500 */
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .back-link:hover {
            background-color: #2563eb; /* Tailwind blue-600 */
        }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<div class="content-container mt-10 mb-10">
    <h1><?php echo $page_title; ?></h1>
    <p>Last Updated: <?php echo $last_updated; ?></p>

    <?php echo $page_content; // Display the fetched HTML content ?>

    <a href="signup_page.php" class="back-link"><i class="fas fa-arrow-left mr-2"></i> Back to Sign Up</a>
    <a href="index.php" class="back-link" style="margin-left: 10px;"><i class="fas fa-home mr-2"></i> Back to Home</a>

</div>

<?php include 'footer.php'; ?>

</body>
</html> 