<?php
require_once 'includes/header.php';

// Get search parameters
$keywords = isset($_GET['keywords']) ? cleanInput($_GET['keywords']) : '';
$location = isset($_GET['location']) ? cleanInput($_GET['location']) : '';
$type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$experience = isset($_GET['experience']) ? cleanInput($_GET['experience']) : '';
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';

// Build base SQL query
$sql = "SELECT j.*, c.name as company_name, c.logo_url 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        WHERE j.status = 'Active'";

// Prepare parameter array
$params = [];
$types = "";

// Add search filters if provided
if (!empty($keywords)) {
    $keywordsLike = "%{$keywords}%";
    $sql .= " AND (j.title LIKE ? OR c.name LIKE ? OR EXISTS (
                SELECT 1 FROM job_skills js WHERE js.job_id = j.id AND js.skill LIKE ?
              ))";
    $params[] = $keywordsLike;
    $params[] = $keywordsLike;
    $params[] = $keywordsLike;
    $types .= "sss";
}

if (!empty($location)) {
    $locationLike = "%{$location}%";
    $sql .= " AND j.location LIKE ?";
    $params[] = $locationLike;
    $types .= "s";
}

if (!empty($type)) {
    $sql .= " AND j.type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($experience)) {
    $sql .= " AND j.experience_level = ?";
    $params[] = $experience;
    $types .= "s";
}

// Add sorting
if ($sortBy == 'oldest') {
    $sql .= " ORDER BY j.posted_date ASC";
} else {
    // Default to newest
    $sql .= " ORDER BY j.posted_date DESC";
}

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$total_jobs = $result->num_rows;
?>

<div id="jobSearchPage" class="page-content">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Find Your Next Opportunity</h1>
    <div class="card mb-8">
