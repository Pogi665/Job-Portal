<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once 'admin_header.php';
include_once '../database_connection.php'; // Contains $conn

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You must be logged in as an admin to access this page.";
    header("Location: ../login.php");
    exit;
}

$page_title_form = "Add New Content Page";
$error_message = '';
$success_message = '';

// Form field values to retain on error
$form_page_key = '';
$form_page_title = '';
$form_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_page_key = trim($_POST['page_key']);
    $form_page_title = trim($_POST['page_title']);
    $form_content = trim($_POST['content']); // Content from TinyMCE
    $admin_username = $_SESSION['username'];

    // Validate page_key format (simple example: lowercase, hyphens, no spaces)
    if (!preg_match('/^[a-z0-9-]+$/', $form_page_key)) {
        $error_message = "Page Key must be lowercase alphanumeric with hyphens only (e.g., 'my-new-page').";
    } elseif (empty($form_page_key) || empty($form_page_title) || empty($form_content)) {
        $error_message = "Page Key, Page Title, and Content cannot be empty.";
    } else {
        // Check if page_key already exists
        $stmt_check = $conn->prepare("SELECT id FROM site_content WHERE page_key = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $form_page_key);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $error_message = "Page Key '" . htmlspecialchars($form_page_key) . "' already exists. Choose a unique key.";
            }
            $stmt_check->close();
        } else {
            $error_message = "Database error checking page key: " . $conn->error;
        }

        if (empty($error_message)) { // Proceed if no validation errors so far
            $stmt_insert = $conn->prepare("INSERT INTO site_content (page_key, page_title, content, created_by, last_updated_by) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssss", $form_page_key, $form_page_title, $form_content, $admin_username, $admin_username);
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Content page '" . htmlspecialchars($form_page_title) . "' created successfully.";
                    header("Location: manage_content.php");
                    exit;
                } else {
                    $error_message = "Failed to create content page. Database error: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $error_message = "Failed to prepare content creation. Database error: " . $conn->error;
            }
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
    <h1 class="text-3xl font-bold mb-6 text-gray-800"><?php echo htmlspecialchars($page_title_form); ?></h1>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="add_content.php" method="POST" class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
        <div class="mb-4">
            <label for="page_key" class="block text-gray-700 text-sm font-bold mb-2">Page Key:</label>
            <input type="text" name="page_key" id="page_key" 
                   value="<?php echo htmlspecialchars($form_page_key); ?>" 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            <p class="text-xs text-gray-600 mt-1">Unique identifier for the page (e.g., 'about-us', 'contact-info'). Lowercase alphanumeric and hyphens only.</p>
        </div>

        <div class="mb-4">
            <label for="page_title" class="block text-gray-700 text-sm font-bold mb-2">Page Title:</label>
            <input type="text" name="page_title" id="page_title" 
                   value="<?php echo htmlspecialchars($form_page_title); ?>" 
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-6">
            <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Content:</label>
            <!-- Quill editor container -->
            <div id="quill-editor" style="height: 400px;"></div>
            <!-- Hidden input to store Quill's HTML content -->
            <input type="hidden" name="content" id="content">
             <p class="text-xs text-red-600 mt-1 font-semibold">Security Note: Ensure content is properly sanitized server-side before saving and rendering to prevent XSS.</p>
       </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <i class="fas fa-plus mr-2"></i>Create Page
            </button>
            <a href="manage_content.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</div>

<script>
  var quill = new Quill('#quill-editor', {
    theme: 'snow', // 'snow' is a common theme with a toolbar
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        ['blockquote', 'code-block'],

        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
        [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
        [{ 'direction': 'rtl' }],                         // text direction

        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'font': [] }],
        [{ 'align': [] }],

        ['clean'],                                         // remove formatting button
        ['link', 'image']                                  // link and image
      ]
    }
  });

  // When text changes in Quill, update the hidden input
  quill.on('text-change', function(delta, oldDelta, source) {
    document.getElementById('content').value = quill.root.innerHTML;
  });

  // If there's existing form content (e.g., validation error occurred), load it into Quill
  <?php if (!empty($form_content)): ?>
  quill.root.innerHTML = <?php echo json_encode($form_content); ?>;
  // Ensure hidden input is also updated with this initial content
  document.getElementById('content').value = quill.root.innerHTML;
  <?php endif; ?>
</script>

<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
include_once 'admin_footer.php';
?> 