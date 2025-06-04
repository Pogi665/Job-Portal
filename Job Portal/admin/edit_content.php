<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once 'admin_header.php';
include_once '../database_connection.php'; // Contains $conn

// Check if admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an admin to access this page.";
    header("Location: ../login.php");
    exit;
}

$page_key = $_GET['key'] ?? null;
$page_title_form = "Edit Site Content"; // Page title for the form itself
$content_data = null;
$error_message = '';
$success_message = '';

if (!$page_key) {
    $_SESSION['error_message'] = "No page key provided to edit.";
    header("Location: manage_content.php");
    exit;
}

// Fetch the current content
$stmt_fetch = $conn->prepare("SELECT page_title, content FROM site_content WHERE page_key = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("s", $page_key);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows > 0) {
        $content_data = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Content page with key '" . htmlspecialchars($page_key) . "' not found.";
        header("Location: manage_content.php");
        exit;
    }
    $stmt_fetch->close();
} else {
    // Handle DB error (e.g., log it)
    $_SESSION['error_message'] = "Database error fetching content. Please try again.";
    header("Location: manage_content.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_page_title = trim($_POST['page_title']);
    $new_content = trim($_POST['content']); // Consider using a more robust editor/sanitizer for real HTML content
    $admin_username = $_SESSION['username'];

    if (empty($new_page_title) || empty($new_content)) {
        $error_message = "Page Title and Content cannot be empty.";
        // Re-populate form with submitted (but unsaved) data
        $content_data['page_title'] = $new_page_title;
        $content_data['content'] = $new_content;
    } else {
        $stmt_update = $conn->prepare("UPDATE site_content SET page_title = ?, content = ?, last_updated_by = ? WHERE page_key = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("ssss", $new_page_title, $new_content, $admin_username, $page_key);
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Content for '" . htmlspecialchars($new_page_title) . "' updated successfully.";
                header("Location: manage_content.php");
                exit;
            } else {
                $error_message = "Failed to update content. Database error.";
                 // Log detailed error: $stmt_update->error
            }
            $stmt_update->close();
        } else {
            $error_message = "Failed to prepare content update. Database error.";
            // Log detailed error: $conn->error
        }
    }
}

?>
<!-- Remove TinyMCE CDN and initialization script -->
<!-- Quill CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<!-- Quill JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800">
            <?php echo htmlspecialchars($page_title_form); ?>: 
            <span class="text-blue-600"><?php echo htmlspecialchars($content_data['page_title'] ?? 'Unknown Page'); ?></span>
        </h1>
        <a href="manage_content.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Cancel & Back to List
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success_message): // Should not typically show here due to redirect, but good for debugging ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="edit_content.php?key=<?php echo htmlspecialchars($page_key); ?>" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
        <div class="mb-6">
            <label for="page_title" class="block text-gray-700 text-sm font-bold mb-2">Page Title:</label>
            <input type="text" name="page_title" id="page_title" 
                   value="<?php echo htmlspecialchars($content_data['page_title'] ?? ''); ?>" 
                   class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Content:</label>
            <!-- Quill editor container -->
            <div id="quill-editor" style="height: 550px;"></div> <!-- Adjusted height slightly less than original 600px for better layout with toolbar -->
            <!-- Hidden input to store Quill's HTML content -->
            <input type="hidden" name="content" id="content">
            <p class="text-xs text-red-600 mt-2 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i>Security Note: Ensure content is properly sanitized server-side before saving and rendering to prevent XSS.</p>
        </div>

        <div class="flex items-center justify-start pt-4 border-t border-gray-200 mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md focus:outline-none focus:shadow-outline flex items-center transition duration-150 ease-in-out">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
        </div>
    </form>

</div>

<script>
  var quill = new Quill('#quill-editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['clean'],
        ['link', 'image']
      ]
    }
  });

  // When text changes in Quill, update the hidden input
  quill.on('text-change', function(delta, oldDelta, source) {
    document.getElementById('content').value = quill.root.innerHTML;
  });

  // Load existing content into Quill
  <?php if (isset($content_data['content']) && !empty($content_data['content'])): ?>
  quill.root.innerHTML = <?php echo json_encode($content_data['content']); ?>;
  // Ensure hidden input is also updated with this initial content
  document.getElementById('content').value = quill.root.innerHTML;
  <?php endif; ?>
</script>

<?php
// Include footer
include_once 'admin_footer.php';
?> 