<?php
// Require authentication
require_once 'includes/auth_guard.php';
require_once 'includes/header.php';

$userId = $_SESSION['user_id'];

// Mark notifications as read if requested
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    $markReadQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($markReadQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    setMessage('success', 'All notifications marked as read');
    header('Location: notifications.php');
    exit;
}

// Mark a single notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    $markReadQuery = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($markReadQuery);
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    
    // Check if there's a redirect URL
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
        exit;
    }
    
    header('Location: notifications.php');
    exit;
}

// Get user notifications
$notificationsQuery = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($notificationsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Count unread notifications
$unreadCountQuery = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unreadCountQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$unreadResult = $stmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
                
                <?php if ($unreadCount > 0): ?>
                <a href="?mark_all_read=1" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    Mark all as read
                </a>
                <?php endif; ?>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <?php while($notification = $result->fetch_assoc()): ?>
                        <?php 
                            // Determine notification type classes
                            $isRead = $notification['is_read'] == 1;
                            $notificationBg = $isRead ? 'bg-white' : 'bg-indigo-50';
                            
                            // Determine icon based on type
                            $iconClass = 'fa-bell';
                            $iconColor = 'text-indigo-500';
                            
                            switch($notification['type']) {
                                case 'application_update':
                                    $iconClass = 'fa-file-alt';
                                    $iconColor = 'text-blue-500';
                                    break;
                                case 'job_alert':
                                    $iconClass = 'fa-search';
                                    $iconColor = 'text-green-500';
                                    break;
                                case 'message':
                                    $iconClass = 'fa-envelope';
                                    $iconColor = 'text-purple-500';
                                    break;
                                case 'system':
                                    $iconClass = 'fa-cog';
                                    $iconColor = 'text-gray-500';
                                    break;
                            }
                        ?>
                        <div class="border-b border-gray-100 p-6 <?php echo $notificationBg; ?>">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <i class="fas <?php echo $iconClass; ?> <?php echo $iconColor; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-md font-medium text-gray-900">
                                            <?php echo h($notification['title']); ?>
                                            <?php if (!$isRead): ?>
                                                <span class="inline-block ml-2 w-2 h-2 rounded-full bg-indigo-600"></span>
                                            <?php endif; ?>
                                        </h3>
                                        <span class="text-sm text-gray-500">
                                            <?php echo timeElapsed($notification['created_at']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700 mt-1">
                                        <?php echo h($notification['message']); ?>
                                    </p>
                                    
                                    <div class="mt-3 flex items-center">
                                        <?php if (!empty($notification['action_url'])): ?>
                                        <a href="?mark_read=<?php echo $notification['id']; ?>&amp;redirect=<?php echo urlencode($notification['action_url']); ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 mr-4">
                                            <?php echo !empty($notification['action_text']) ? h($notification['action_text']) : 'View Details'; ?>
                                            <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$isRead): ?>
                                        <a href="?mark_read=<?php echo $notification['id']; ?>" class="text-sm text-gray-500 hover:text-gray-700">
                                            Mark as read
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white shadow-md rounded-lg p-8 text-center">
                    <div class="mb-4">
                        <i class="fas fa-bell text-gray-300 text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No notifications</h3>
                    <p class="text-gray-500">You don't have any notifications at the moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 