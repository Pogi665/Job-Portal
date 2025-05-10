<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $role = cleanInput($_POST['role']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                
                // Update last login time
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Redirect based on user role
                if ($user['role'] == 'job_seeker') {
                    redirect('user/dashboard.php', 'Login successful!');
                } else if ($user['role'] == 'employer') {
                    redirect('employer/dashboard.php', 'Login successful!');
                } else if ($user['role'] == 'admin') {
                    redirect('admin/dashboard.php', 'Login successful!');
                } else {
                    redirect('index.php', 'Login successful!');
                }
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md my-12">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Login to CareerLynk</h2>
    
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" id="email" name="email" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Login as:</label>
            <select id="role" name="role" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="job_seeker">Job Seeker</option>
                <option value="employer">Employer</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Login
        </button>
        <p class="text-sm text-center pt-2 text-gray-600">
            Don't have an account? <a href="register.php" class="text-indigo-600 hover:underline font-medium">Sign up</a>
        </p>
    </form>
</div>

<?php include_once 'includes/footer.php'; ?>
