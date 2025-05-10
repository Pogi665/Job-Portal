<?php
// Require authentication
require_once 'includes/auth_guard.php';
require_once 'includes/header.php';

$userId = $_SESSION['user_id'];
$selectedConversation = null;
$selectedUser = null;

// Handle form submission for sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $receiverId = (int)$_POST['receiver_id'];
    $messageContent = trim($_POST['message']);
    
    if (!empty($messageContent)) {
        $insertQuery = "INSERT INTO messages (sender_id, receiver_id, content, sent_at) 
                       VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iis", $userId, $receiverId, $messageContent);
        $stmt->execute();
        
        // Update the conversation's last_message_at timestamp
        $updateConversationQuery = "INSERT INTO conversations (user1_id, user2_id, last_message_at) 
                                   VALUES (?, ?, NOW())
                                   ON DUPLICATE KEY UPDATE last_message_at = NOW()";
        $stmt = $conn->prepare($updateConversationQuery);
        
        // Ensure the smaller ID is user1_id (for consistency)
        $user1 = min($userId, $receiverId);
        $user2 = max($userId, $receiverId);
        $stmt->bind_param("ii", $user1, $user2);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: messages.php?to={$receiverId}");
        exit;
    }
}

// Get conversation list
$conversationsQuery = "SELECT 
                        c.*, 
                        u.name as other_user_name,
                        u.avatar_url as other_user_avatar,
                        u.role as other_user_role,
                        (SELECT content FROM messages 
                         WHERE (sender_id = c.user1_id AND receiver_id = c.user2_id) 
                            OR (sender_id = c.user2_id AND receiver_id = c.user1_id)
                         ORDER BY sent_at DESC LIMIT 1) as last_message,
                        (SELECT sent_at FROM messages 
                         WHERE (sender_id = c.user1_id AND receiver_id = c.user2_id) 
                            OR (sender_id = c.user2_id AND receiver_id = c.user1_id)
                         ORDER BY sent_at DESC LIMIT 1) as last_message_time,
                        (SELECT COUNT(*) FROM messages 
                         WHERE receiver_id = ? AND sender_id = IF(c.user1_id = ?, c.user2_id, c.user1_id) 
                         AND is_read = 0) as unread_count
                      FROM conversations c
                      LEFT JOIN users u ON 
                        (c.user1_id = ? AND u.id = c.user2_id) OR 
                        (c.user2_id = ? AND u.id = c.user1_id)
                      WHERE c.user1_id = ? OR c.user2_id = ?
                      ORDER BY c.last_message_at DESC";

$stmt = $conn->prepare($conversationsQuery);
$stmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $userId, $userId);
$stmt->execute();
$conversations = $stmt->get_result();

// Check if a specific conversation is requested
$selectedUserId = isset($_GET['to']) ? (int)$_GET['to'] : null;

if ($selectedUserId) {
    // Get user details
    $userQuery = "SELECT id, name, avatar_url, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $selectedUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedUser = $result->fetch_assoc();
        
        // Get messages for this conversation
        $messagesQuery = "SELECT m.*, 
                           u_sender.name as sender_name, 
                           u_sender.avatar_url as sender_avatar 
                         FROM messages m
                         JOIN users u_sender ON m.sender_id = u_sender.id
                         WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                            OR (m.sender_id = ? AND m.receiver_id = ?)
                         ORDER BY m.sent_at ASC";
        $stmt = $conn->prepare($messagesQuery);
        $stmt->bind_param("iiii", $userId, $selectedUserId, $selectedUserId, $userId);
        $stmt->execute();
        $messagesList = $stmt->get_result();
        
        // Mark messages as read
        $updateReadQuery = "UPDATE messages 
                          SET is_read = 1 
                          WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt = $conn->prepare($updateReadQuery);
        $stmt->bind_param("ii", $selectedUserId, $userId);
        $stmt->execute();
    }
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Messages</h1>
            
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="grid grid-cols-12">
                    <!-- Conversations List -->
                    <div class="col-span-12 md:col-span-4 border-r border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <div class="relative rounded-md shadow-sm">
                                <input type="text" id="searchContacts" class="form-input py-3 px-4 block w-full pl-10 text-sm" placeholder="Search conversations...">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="conversations-list h-[600px] overflow-y-auto">
                            <?php if ($conversations->num_rows > 0): ?>
                                <?php while($conversation = $conversations->fetch_assoc()): ?>
                                    <?php
                                    $otherUserId = $conversation['user1_id'] == $userId ? $conversation['user2_id'] : $conversation['user1_id'];
                                    $isActive = $selectedUserId == $otherUserId;
                                    $avatarUrl = !empty($conversation['other_user_avatar']) 
                                                ? $conversation['other_user_avatar'] 
                                                : 'https://ui-avatars.com/api/?name=' . urlencode($conversation['other_user_name']) . '&background=random';
                                    ?>
                                    <a href="?to=<?php echo $otherUserId; ?>" class="conversation-item block p-4 border-b border-gray-100 hover:bg-gray-50 <?php echo $isActive ? 'bg-indigo-50' : ''; ?>">
                                        <div class="flex items-center">
                                            <div class="relative">
                                                <img src="<?php echo h($avatarUrl); ?>" alt="Avatar" class="w-12 h-12 rounded-full object-cover">
                                                <?php if ($conversation['unread_count'] > 0): ?>
                                                    <span class="absolute top-0 right-0 bg-red-500 text-white w-5 h-5 rounded-full flex items-center justify-center text-xs">
                                                        <?php echo $conversation['unread_count']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4 flex-1">
                                                <div class="flex justify-between items-center">
                                                    <h3 class="text-sm font-medium text-gray-900"><?php echo h($conversation['other_user_name']); ?></h3>
                                                    <?php if (!empty($conversation['last_message_time'])): ?>
                                                        <span class="text-xs text-gray-500">
                                                            <?php echo date('H:i', strtotime($conversation['last_message_time'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <p class="text-xs text-gray-500 truncate">
                                                        <?php echo !empty($conversation['last_message']) ? h(substr($conversation['last_message'], 0, 35)) . (strlen($conversation['last_message']) > 35 ? '...' : '') : 'No messages yet'; ?>
                                                    </p>
                                                    <span class="text-xs px-2 py-1 rounded-full <?php echo strtolower($conversation['other_user_role']) === 'employer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                                        <?php echo $conversation['other_user_role'] === 'employer' ? 'Employer' : 'Seeker'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-6 text-center">
                                    <i class="fas fa-comments text-gray-300 text-5xl mb-4"></i>
                                    <p class="text-gray-500">No conversations yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Message Area -->
                    <div class="col-span-12 md:col-span-8">
                        <?php if ($selectedUser): ?>
                            <!-- Selected User Header -->
                            <div class="p-4 border-b border-gray-200 flex items-center">
                                <img src="<?php echo !empty($selectedUser['avatar_url']) ? h($selectedUser['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($selectedUser['name']) . '&background=random'; ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                                <div class="ml-3">
                                    <h3 class="text-md font-medium text-gray-900"><?php echo h($selectedUser['name']); ?></h3>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $selectedUser['role'] === 'employer' ? 'Employer' : 'Job Seeker'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Messages List -->
                            <div class="messages-container h-[480px] overflow-y-auto p-4" id="messagesContainer">
                                <?php if (isset($messagesList) && $messagesList->num_rows > 0): ?>
                                    <?php 
                                    $prevDate = '';
                                    while($message = $messagesList->fetch_assoc()): 
                                        $messageDate = date('Y-m-d', strtotime($message['sent_at']));
                                        $showDateDivider = $prevDate !== $messageDate;
                                        $prevDate = $messageDate;
                                        
                                        $isOwnMessage = $message['sender_id'] == $userId;
                                        $messageClass = $isOwnMessage 
                                            ? 'bg-indigo-100 text-indigo-800 ml-auto' 
                                            : 'bg-gray-100 text-gray-800 mr-auto';
                                    ?>
                                        <?php if ($showDateDivider): ?>
                                            <div class="text-center text-xs text-gray-500 my-4">
                                                <?php 
                                                if ($messageDate === date('Y-m-d')) {
                                                    echo 'Today';
                                                } elseif ($messageDate === date('Y-m-d', strtotime('-1 day'))) {
                                                    echo 'Yesterday';
                                                } else {
                                                    echo date('F j, Y', strtotime($message['sent_at']));
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex w-full mb-4 <?php echo $isOwnMessage ? 'justify-end' : 'justify-start'; ?>">
                                            <?php if (!$isOwnMessage): ?>
                                                <img src="<?php echo !empty($message['sender_avatar']) ? h($message['sender_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($message['sender_name']) . '&background=random'; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover mr-2 mt-1">
                                            <?php endif; ?>
                                            
                                            <div class="max-w-[80%]">
                                                <div class="rounded-lg px-4 py-2 <?php echo $messageClass; ?>">
                                                    <?php echo nl2br(h($message['content'])); ?>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1 <?php echo $isOwnMessage ? 'text-right' : 'text-left'; ?>">
                                                    <?php echo date('h:i A', strtotime($message['sent_at'])); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($isOwnMessage): ?>
                                                <img src="<?php echo isset($_SESSION['avatar_url']) ? h($_SESSION['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=random'; ?>" alt="Your Avatar" class="w-8 h-8 rounded-full object-cover ml-2 mt-1">
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="h-full flex items-center justify-center">
                                        <div class="text-center">
                                            <i class="fas fa-comments text-gray-300 text-4xl mb-2"></i>
                                            <p class="text-gray-500">No messages yet. Start the conversation!</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Message Input -->
                            <div class="p-4 border-t border-gray-200">
                                <form method="POST" action="messages.php" id="messageForm">
                                    <input type="hidden" name="action" value="send_message">
                                    <input type="hidden" name="receiver_id" value="<?php echo $selectedUserId; ?>">
                                    <div class="flex">
                                        <textarea name="message" id="messageInput" rows="2" class="form-textarea flex-grow mr-2 py-2" placeholder="Type a message..." required></textarea>
                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center">
                                            <i class="fas fa-paper-plane mr-2"></i> Send
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                        <?php else: ?>
                            <!-- No conversation selected -->
                            <div class="h-[600px] flex items-center justify-center">
                                <div class="text-center">
                                    <i class="fas fa-comments text-gray-300 text-6xl mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No conversation selected</h3>
                                    <p class="text-gray-500">Select a conversation or start a new one</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom of messages container
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Filter conversations on search
    const searchInput = document.getElementById('searchContacts');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const items = document.querySelectorAll('.conversation-item');
            
            items.forEach(item => {
                const name = item.querySelector('h3').textContent.toLowerCase();
                const lastMessage = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(query) || lastMessage.includes(query)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Add message submission handling
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    
    if (messageForm) {
        messageForm.addEventListener('submit', function() {
            if (messageInput.value.trim() === '') {
                event.preventDefault();
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 