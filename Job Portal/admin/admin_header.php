<?php
if (session_status() == PHP_SESSION_NONE) { // Ensure session is started
    session_start();
}

// Include your database connection file
// require_once '../database_connection.php'; // Adjust path as needed

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If you have a specific login page for admins, you might redirect there.
    // Otherwise, redirect to the general login page.
    header("Location: ../login.php"); 
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'Admin'; // Fallback for username
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CareerLynk</title>
    <link rel="stylesheet" href="../style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100"> <!-- Match main site background: Tailwind bg-gray-100 is #f3f4f6, style.css uses #f5f7fa -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="dashboard.php" class="text-2xl font-bold text-blue-600 flex items-center">
                <i class="fas fa-handshake mr-2"></i>CareerLynk <span class="text-lg text-gray-600 font-normal ml-2">- Admin</span>
            </a>
            <nav class="space-x-4 md:space-x-6 flex items-center">
                <a href="dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Dashboard</a>
                <a href="manage_users.php" class="<?= ($currentPage == 'manage_users.php' || $currentPage == 'edit_user.php' || $currentPage == 'view_user_profile.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Manage Users</a>
                <a href="manage_jobs.php" class="<?= ($currentPage == 'manage_jobs.php' || $currentPage == 'edit_job_admin.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Manage Jobs</a>
                <a href="manage_content.php" class="<?= ($currentPage == 'manage_content.php' || $currentPage == 'add_content.php' || $currentPage == 'edit_content.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Manage Content</a>
                <a href="../logout.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow hover:shadow-lg">
                    Log Out
                </a>
            </nav>
        </div>
    </header>
    
    <!-- The main content of each admin page will start after this header -->
    <!-- The old .admin-container div is removed from here -->
    <!-- Individual admin pages should wrap their content in a <main class="container mx-auto p-4 md:p-6 lg:p-8"> or similar --> 