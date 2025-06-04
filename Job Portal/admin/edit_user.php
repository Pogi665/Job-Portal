<?php
require_once 'admin_header.php';
require_once '../database_connection.php';

$user_id_to_edit = $_GET['id'] ?? null;
$user = null;
$message = '';
$message_type = ''; // 'success' or 'error'

// Available roles - ensure this matches your ENUM definition in the database + the new 'admin' role
$available_roles = ['job_seeker', 'job_employer', 'admin'];

// Check if the user being edited is the current admin
$is_editing_self = false;
if (isset($_SESSION['user_id']) && $user_id_to_edit == $_SESSION['user_id']) {
    $is_editing_self = true;
}

if (!$user_id_to_edit || !filter_var($user_id_to_edit, FILTER_VALIDATE_INT)) {
    $_SESSION['user_action_message'] = "Invalid user ID provided."; // Use session for redirect messages
    $_SESSION['user_action_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

// Check database connection
if ($conn->connect_error) {
    // This is a critical error, might be better to show a generic error page
    // For now, let's show it on the page and stop further execution within this block.
    $message = "Database connection failed: " . $conn->connect_error;
    $message_type = 'error';
} else {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_user'])) {
            $new_role = $_POST['role'] ?? '';

            if ($is_editing_self && isset($user) && $new_role !== $user['role']) { // Check if user is set before accessing its properties
                $message = "You cannot change your own role.";
                $message_type = 'error';
            } elseif (in_array($new_role, $available_roles)) {
                $stmt = $conn->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $new_role, $user_id_to_edit);
                    if ($stmt->execute()) {
                        $message = "User role updated successfully!";
                        $message_type = 'success';
                        // If an admin edits another user's role to admin, or their own (if allowed, though currently blocked),
                        // we might want to update session role if it was self, but it's blocked.
                        // If they successfully change *another* user to admin, no current session change needed.
                    } else {
                        $message = "Error updating user role: " . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                } else {
                    $message = "Error preparing statement: " . $conn->error;
                    $message_type = 'error';
                }
            } else {
                $message = "Invalid role selected.";
                $message_type = 'error';
            }
        }
    }

    // Fetch user details for the form
    $stmt_fetch = $conn->prepare("SELECT id, username, email, role, full_name FROM users WHERE id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $user_id_to_edit);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // After fetching user, re-check if editing self with the fetched role for form display
            if ($is_editing_self && $user['role'] === 'admin') {
                // Message for page load, not just for POST
                if (empty($message)) { // Show only if no other message (like update success/failure) is present
                    $message = "As an administrator, you cannot change your own role from this interface.";
                    $message_type = 'info'; // Use info for a non-error, non-success message
                }
            }
        } else {
            // If user not found, redirect back with a message
            $_SESSION['user_action_message'] = "User not found.";
            $_SESSION['user_action_message_type'] = 'error';
            header("Location: manage_users.php");
            exit;
        }
        $stmt_fetch->close();
    } else {
        $message = "Error fetching user details: " . $conn->error; // This is for error in preparing statement
        $message_type = 'error';
    }
}

// Fetch messages from session if redirected here
if (isset($_SESSION['user_action_message'])) {
    $message = $_SESSION['user_action_message'];
    $message_type = $_SESSION['user_action_message_type'] ?? 'info';
    unset($_SESSION['user_action_message'], $_SESSION['user_action_message_type']);
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800">Edit User Role</h1>
        <a href="manage_users.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
            <i class="fas fa-arrow-left mr-2"></i>Back to Manage Users
        </a>
    </div>

    <?php if ($message): ?>
    <div class="mb-4 px-4 py-3 rounded relative <?php 
        switch ($message_type) {
            case 'success': echo 'bg-green-100 border border-green-400 text-green-700'; break;
            case 'error':   echo 'bg-red-100 border border-red-400 text-red-700'; break;
            case 'info':    echo 'bg-blue-100 border border-blue-400 text-blue-700'; break;
            default:        echo 'bg-gray-100 border border-gray-400 text-gray-700'; break;
        }
    ?> role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($user): ?>
    <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id_to_edit); ?>" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-700">User: <?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo htmlspecialchars($user['id']); ?>)</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-200" readonly>
            </div>
            <div>
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-200" readonly>
            </div>
        </div>

        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
            <select name="role" id="role" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                <?php if ($is_editing_self && $user['role'] === 'admin') echo 'disabled'; ?>>
                <?php foreach ($available_roles as $role_value): ?>
                    <option value="<?php echo htmlspecialchars($role_value); ?>" <?php echo ($user['role'] === $role_value) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role_value))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($is_editing_self && $user['role'] === 'admin'): ?>
                <p class="text-xs text-red-500 mt-1">Administrators cannot change their own role.</p>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between border-t pt-6 mt-6">
            <a href="manage_users.php" class="text-sm text-blue-500 hover:text-blue-700">Cancel</a>
            <button type="submit" name="update_user" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                <?php if ($is_editing_self && $user['role'] === 'admin') echo 'disabled'; ?>>
                Update User Role
            </button>
        </div>
    </form>
    <?php elseif (!$message && !$user): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">User data could not be loaded. The user may not exist or was not found.</span>
    </div>
    <?php endif; ?>

</div>

<?php
require_once 'admin_footer.php';
?> 