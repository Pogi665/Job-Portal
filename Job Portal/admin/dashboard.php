<?php 
require_once 'admin_header.php'; 
require_once '../database_connection.php';

$db_connection_error = null;
if ($conn->connect_error) {
    $db_connection_error = "Database connection failed: " . htmlspecialchars($conn->connect_error);
}

// Initialize counts
$total_users = 0;
$job_seekers = 0;
$job_employers = 0;
$total_admins = 0; 

$total_jobs = 0;
$active_jobs = 0;
$pending_jobs = 0; // Ensure this is fetched if displayed
$rejected_jobs = 0; // Added for completeness

$total_applications = 0;

$stats_fetch_errors = [];

if (!$db_connection_error) {
    // Fetch User Counts
    $user_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $user_result = $conn->query($user_sql);
    if ($user_result) {
        while ($row = $user_result->fetch_assoc()) {
            $total_users += $row['count'];
            if ($row['role'] === 'job_seeker') $job_seekers = $row['count'];
            elseif ($row['role'] === 'job_employer') $job_employers = $row['count'];
            elseif ($row['role'] === 'admin') $total_admins = $row['count'];
        }
    } else {
        $stats_fetch_errors[] = "Error fetching user stats: " . htmlspecialchars($conn->error);
    }

    // Fetch Job Counts
    $job_sql = "SELECT status, COUNT(*) as count FROM jobs GROUP BY status";
    $job_result = $conn->query($job_sql);
    if ($job_result) {
        while ($row = $job_result->fetch_assoc()) {
            $total_jobs += $row['count'];
            if ($row['status'] === 'active') $active_jobs = $row['count'];
            elseif ($row['status'] === 'pending_approval') $pending_jobs = $row['count'];
            elseif ($row['status'] === 'rejected') $rejected_jobs = $row['count'];
        }
    } else {
        $stats_fetch_errors[] = "Error fetching job stats: " . htmlspecialchars($conn->error);
    }

    // Fetch Total Job Applications Count
    $app_sql = "SELECT COUNT(*) as count FROM job_applications";
    $app_result = $conn->query($app_sql);
    if ($app_result) {
        $total_applications = $app_result->fetch_assoc()['count'] ?? 0;
    } else {
        $stats_fetch_errors[] = "Error fetching application stats: " . htmlspecialchars($conn->error);
    }
    $conn->close();
}

$stat_cards = [
    ['title' => 'Total Users', 'count' => $total_users, 'icon' => 'fas fa-users', 'color' => 'blue'],
    ['title' => 'Job Seekers', 'count' => $job_seekers, 'icon' => 'fas fa-user-graduate', 'color' => 'green'],
    ['title' => 'Job Employers', 'count' => $job_employers, 'icon' => 'fas fa-user-tie', 'color' => 'yellow'],
    ['title' => 'Administrators', 'count' => $total_admins, 'icon' => 'fas fa-user-shield', 'color' => 'red', 'condition' => $total_admins > 0],
    ['title' => 'Total Job Postings', 'count' => $total_jobs, 'icon' => 'fas fa-briefcase', 'color' => 'teal'],
    ['title' => 'Active Jobs', 'count' => $active_jobs, 'icon' => 'fas fa-check-circle', 'color' => 'lime'],
    ['title' => 'Pending Approval', 'count' => $pending_jobs, 'icon' => 'fas fa-hourglass-half', 'color' => 'amber', 'condition' => true], // Always show, even if 0
    ['title' => 'Rejected Jobs', 'count' => $rejected_jobs, 'icon' => 'fas fa-times-circle', 'color' => 'pink', 'condition' => true], // Always show, even if 0
    ['title' => 'Total Applications', 'count' => $total_applications, 'icon' => 'fas fa-file-alt', 'color' => 'purple']
];

$color_map = [
    'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-100', 'hover_bg' => 'hover:bg-blue-600'],
    'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-100', 'hover_bg' => 'hover:bg-green-600'],
    'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-100', 'hover_bg' => 'hover:bg-yellow-600'],
    'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-100', 'hover_bg' => 'hover:bg-red-600'],
    'teal' => ['bg' => 'bg-teal-500', 'text' => 'text-teal-100', 'hover_bg' => 'hover:bg-teal-600'],
    'lime' => ['bg' => 'bg-lime-500', 'text' => 'text-lime-100', 'hover_bg' => 'hover:bg-lime-600'],
    'amber' => ['bg' => 'bg-amber-500', 'text' => 'text-amber-100', 'hover_bg' => 'hover:bg-amber-600'],
    'pink' => ['bg' => 'bg-pink-500', 'text' => 'text-pink-100', 'hover_bg' => 'hover:bg-pink-600'],
    'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-100', 'hover_bg' => 'hover:bg-purple-600']
];

?>
<main role="main" class="flex-grow">
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
    </div>
    <p class="mb-8 text-gray-600 text-sm">Welcome to the Job Portal Admin Panel. Here's a quick overview of your site:</p>

    <?php if ($db_connection_error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Database Error:</strong>
            <span class="block sm:inline"><?php echo $db_connection_error; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($stats_fetch_errors)):
        foreach($stats_fetch_errors as $error_msg):
    ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Stats Error:</strong>
            <span class="block sm:inline"><?php echo $error_msg; ?></span>
        </div>
    <?php endforeach; endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <?php foreach($stat_cards as $card): ?>
            <?php if (isset($card['condition']) && !$card['condition']) continue; ?>
            <?php $card_colors = $color_map[$card['color']] ?? $color_map['blue']; ?>
            <div class="<?php echo $card_colors['bg']; ?> <?php echo $card_colors['text']; ?> p-6 rounded-lg shadow-lg transform transition-all duration-300 <?php echo $card_colors['hover_bg']; ?> hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-semibold"><?php echo htmlspecialchars($card['title']); ?></h4>
                        <p class="text-4xl font-bold mt-1"><?php echo htmlspecialchars($card['count']); ?></p>
                    </div>
                    <div class="text-5xl opacity-70">
                        <i class="<?php echo htmlspecialchars($card['icon']); ?>"></i>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-10 pt-6 border-t border-gray-300">
        <h3 class="text-2xl font-semibold text-gray-700 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-4">
            <a href="manage_users.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-md shadow-md transition duration-150 ease-in-out transform hover:scale-105 flex items-center text-sm">
                <i class="fas fa-users-cog mr-2"></i> Manage Users
            </a>
            <a href="manage_jobs.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-md shadow-md transition duration-150 ease-in-out transform hover:scale-105 flex items-center text-sm">
                <i class="fas fa-briefcase mr-2"></i> Manage Job Postings
            </a>
            <a href="manage_content.php" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-6 rounded-md shadow-md transition duration-150 ease-in-out transform hover:scale-105 flex items-center text-sm">
                <i class="fas fa-file-alt mr-2"></i> Manage Site Content
            </a>
            <!-- Add more quick actions as needed -->
        </div>
    </div>
</div>
</main>

<?php 
require_once 'admin_footer.php'; 
?> 