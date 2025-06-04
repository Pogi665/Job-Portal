<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php'; // Contains $conn

$page_title = "View User Profile";
$user_id_to_view = $_GET['id'] ?? null;
$user_data = null;
$error_message = '';

if (!$user_id_to_view || !filter_var($user_id_to_view, FILTER_VALIDATE_INT)) {
    $_SESSION['user_action_message'] = 'Invalid user ID provided.';
    $_SESSION['user_action_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
} else {
    $stmt = $conn->prepare("SELECT id, username, full_name, email, role, created_at, updated_at FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id_to_view);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
        } else {
            $error_message = "User not found.";
        }
        $stmt->close();
    } else {
        $error_message = "Error preparing statement: " . $conn->error;
    }
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="manage_users.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
            <i class="fas fa-arrow-left mr-2"></i>Back to Manage Users
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php elseif ($user_data): ?>
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="px-6 py-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-2">User Details</h2>
                    <p class="text-sm text-gray-500">Viewing profile for: <?php echo htmlspecialchars($user_data['username']); ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 text-sm">
                    <div>
                        <p class="text-gray-500 font-medium">User ID:</p>
                        <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user_data['id']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Username:</p>
                        <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Full Name:</p>
                        <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Email:</p>
                        <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Role:</p>
                        <p class="text-gray-800 text-lg uppercase font-semibold <?php 
                            switch ($user_data['role']) {
                                case 'admin': echo 'text-red-600'; break;
                                case 'job_employer': echo 'text-blue-600'; break;
                                case 'job_seeker': echo 'text-green-600'; break;
                                default: echo 'text-gray-600';
                            }
                        ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $user_data['role'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Registered On:</p>
                        <p class="text-gray-800 text-lg"><?php echo date("M d, Y, h:i A", strtotime($user_data['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Last Updated:</p>
                        <p class="text-gray-800 text-lg"><?php echo date("M d, Y, h:i A", strtotime($user_data['updated_at'])); ?></p>
                    </div>
                    
                    <?php // Placeholder for potential future fields
                    // if (isset($user_data['profile_picture_url'])) : ?>
                    <!-- <div>
                        <p class="text-gray-500 font-medium">Profile Picture:</p>
                        <img src="<?php echo htmlspecialchars($user_data['profile_picture_url']); ?>" alt="Profile Picture" class="mt-1 h-24 w-24 rounded-full object-cover">
                    </div> -->
                    <?php // endif; ?>
                    <?php // if (isset($user_data['bio'])) : ?>
                    <!-- <div class="md:col-span-2">
                        <p class="text-gray-500 font-medium">Bio:</p>
                        <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($user_data['bio']); ?></p>
                    </div> -->
                    <?php // endif; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200 text-right">
                     <a href="edit_user.php?id=<?php echo htmlspecialchars($user_data['id']); ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
                        <i class="fas fa-edit mr-2"></i>Edit Role
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if(empty($error_message)) : // Only show this if no specific error message was set by logic, but user_data is null (e.g. ID valid but not found after check) ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">User data could not be loaded. The user may not exist or an error occurred.</span>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
include_once 'admin_footer.php';
?> 