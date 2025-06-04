<?php 
require_once 'admin_header.php'; 
require_once '../database_connection.php'; // Ensure this path is correct

// Fetch messages from other actions, using the new session keys if available
$action_message = '';
$action_message_type = '';
if (isset($_SESSION['user_action_message'])) {
    $action_message = $_SESSION['user_action_message'];
    $action_message_type = $_SESSION['user_action_message_type'] ?? 'info';
    unset($_SESSION['user_action_message'], $_SESSION['user_action_message_type']);
} elseif (isset($_SESSION['delete_message'])) { // Fallback for older session key from delete_user
    $action_message = $_SESSION['delete_message'];
    $action_message_type = $_SESSION['delete_message_type'] ?? 'info';
    unset($_SESSION['delete_message'], $_SESSION['delete_message_type']);
}

// Check database connection
if ($conn->connect_error) {
    // Display error within the main layout for consistency
    $error_html = "<div class=\"container mx-auto px-4 py-8\"><div class=\"bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative\" role=\"alert\"><strong class=\"font-bold\">Database Connection Error:</strong> <span class=\"block sm:inline\">" . htmlspecialchars($conn->connect_error) . "</span></div></div>";
    // We need to include admin_footer as well if we die here after outputting HTML
    echo $error_html; // Simplified, ideally this would be part of a layout include
    require_once 'admin_footer.php'; 
    die();
}

// Fetch users from the database
$users = [];
$sql = "SELECT id, username, email, role, full_name, created_at, updated_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

$fetch_error = null;
if (!$result) {
    $fetch_error = "Error fetching users: " . htmlspecialchars($conn->error);
} elseif ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
        <!-- Optional: Add a button like 'Add New User' here if functionality exists/is planned -->
    </div>

    <?php if ($action_message): 
        $alert_class = '';
        switch ($action_message_type) {
            case 'success': $alert_class = 'bg-green-100 border-green-400 text-green-700'; break;
            case 'error':   $alert_class = 'bg-red-100 border-red-400 text-red-700'; break;
            case 'warning': $alert_class = 'bg-yellow-100 border-yellow-400 text-yellow-700'; break;
            default:        $alert_class = 'bg-blue-100 border-blue-400 text-blue-700'; break;
        }
    ?>
        <div class="<?php echo $alert_class; ?> px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($action_message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($fetch_error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $fetch_error; ?></span>
        </div>
    <?php endif; ?>

    <p class="mb-6 text-gray-600 text-sm">This section allows administrators to view, edit roles, and manage user accounts.</p>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Full Name</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Registered</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Updated</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): 
                        $role_class = '';
                        switch ($user['role']) {
                            case 'admin': $role_class = 'text-red-600 font-semibold'; break;
                            case 'job_employer': $role_class = 'text-blue-600 font-semibold'; break;
                            case 'job_seeker': $role_class = 'text-green-600 font-semibold'; break;
                        }
                    ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-5 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td class="px-5 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-5 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                            <td class="px-5 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-5 py-4 whitespace-nowrap <?php echo $role_class; ?>">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars(date("M d, Y", strtotime($user['created_at']))); ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars(date("M d, Y", strtotime($user['updated_at']))); ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <a href="view_user_profile.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3 text-xs" title="View Profile">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3 text-xs" title="Edit Role">
                                    <i class="fas fa-user-shield"></i> Edit Role
                                </a>
                                <?php if ($_SESSION['user_id'] != $user['id']): // Prevent admin from deleting self via this link ?>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900 text-xs" title="Delete User" onclick="return confirm('Are you sure you want to delete this user (<?php echo htmlspecialchars(addslashes($user['username'])); ?>)? \nThis action cannot be undone.');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                                <?php else: ?>
                                <span class="text-gray-400 text-xs">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </span> <!-- Disabled look -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500">
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-lg font-medium text-gray-900">No users found</h3>
                                <p class="mt-1 text-sm text-gray-500">There are currently no users in the system.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
require_once 'admin_footer.php'; 
?> 