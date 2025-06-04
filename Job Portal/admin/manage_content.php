<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include header
include_once 'admin_header.php';
include_once '../database_connection.php'; // Contains $conn

// Check if admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an admin to access this page.";
    header("Location: ../login.php");
    exit;
}

$page_title = "Manage Site Content";

// Fetch content pages from the database
$stmt = $conn->prepare("SELECT id, page_key, page_title, last_updated_by, updated_at FROM site_content ORDER BY page_title ASC");
$stmt->execute();
$result = $stmt->get_result();
$content_pages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="add_content.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out flex items-center">
            <i class="fas fa-plus mr-2"></i>Add New Page
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="px-4 sm:px-6 lg:px-8 py-5">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                        <th class="py-3 px-5 text-left">Page Title</th>
                        <th class="py-3 px-5 text-left hidden md:table-cell">Page Key</th>
                        <th class="py-3 px-5 text-left hidden lg:table-cell">Last Updated By</th>
                        <th class="py-3 px-5 text-center">Last Updated</th>
                        <th class="py-3 px-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php if (count($content_pages) > 0): ?>
                        <?php foreach ($content_pages as $page): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition duration-150 ease-in-out">
                                <td class="py-4 px-5 text-left">
                                    <span class="font-medium"><?php echo htmlspecialchars($page['page_title']); ?></span>
                                </td>
                                <td class="py-4 px-5 text-left hidden md:table-cell">
                                    <code class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded"><?php echo htmlspecialchars($page['page_key']); ?></code>
                                </td>
                                <td class="py-4 px-5 text-left hidden lg:table-cell">
                                    <?php echo htmlspecialchars($page['last_updated_by'] ?? 'N/A'); ?>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($page['updated_at']))); ?><br>
                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars(date('g:i A', strtotime($page['updated_at']))); ?></span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <a href="edit_content.php?key=<?php echo htmlspecialchars($page['page_key']); ?>" class="text-blue-600 hover:text-blue-800 py-1 px-3 rounded-md text-sm transition duration-150 ease-in-out inline-flex items-center mr-2" title="Edit Content">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <?php if ($page['page_key'] !== 'terms_of_service' && $page['page_key'] !== 'privacy_policy'): ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-6 px-5 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-file-alt fa-3x text-gray-400 mb-3"></i>
                                    <p class="font-semibold">No content pages found.</p>
                                    <p class="text-sm">Click "Add New Page" to create your first content page.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- "Add New Content Page" button moved to the header -->
    <!-- <div class="mt-8 text-right">
        <a href="add_content.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
            <i class="fas fa-plus mr-2"></i>Add New Content Page
        </a>
    </div> -->

</div>

<?php
// Include footer
include_once 'admin_footer.php';
?> 