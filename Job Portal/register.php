<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$name = $email = $role = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = cleanInput($_POST['role']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists. Please use a different email or login.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate avatar URL
            $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff';
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, avatar_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $avatar_url);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Clear form values on success
                $name = $email = $role = '';
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md my-12">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Create Your Account</h2>
    
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
            <?php echo $success; ?>
            <p class="mt-2">
                <a href="login.php" class="font-medium text-green-700 underline">Click here to login</a>
            </p>
        </div>
    <?php else: ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long</p>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">I am a:</label>
                <select id="role" name="role" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="job_seeker" <?php echo ($role == 'job_seeker') ? 'selected' : ''; ?>>Job Seeker</option>
                    <option value="employer" <?php echo ($role == 'employer') ? 'selected' : ''; ?>>Employer</option>
                </select>
            </div>
            <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Sign Up
            </button>
            <p class="text-sm text-center pt-2 text-gray-600">
                Already have an account? <a href="login.php" class="text-indigo-600 hover:underline font-medium">Login</a>
            </p>
        </form>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
