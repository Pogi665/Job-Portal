<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="style.css"> <!-- Corrected path for CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>

<header class="main-header bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <a href="../dashboard.php" class="text-2xl font-bold text-blue-600 flex items-center">
            <i class="fas fa-handshake mr-2"></i>CareerLynk
        </a>
        <!-- No navigation links -->
    </div>
</header> 