</main>

<footer class="bg-gray-900 text-gray-300 py-12 mt-16">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 mb-8">
            <div>
                <h3 class="text-lg font-semibold mb-3 text-white">CareerLynk</h3>
                <p class="text-sm text-gray-400">Your Connection to Opportunity.</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-3 text-white">Quick Links</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="about.php" class="text-gray-400 hover:text-white">About Us</a></li>
                    <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact Us</a></li>
                    <li><a href="faq.php" class="text-gray-400 hover:text-white">FAQ</a></li>
                    <li><a href="blog.php" class="text-gray-400 hover:text-white">Blog</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-3 text-white">Legal</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                    <li><a href="privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-3 text-white">Connect</h3>
                <div class="flex space-x-4 text-xl mb-3">
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-700 pt-8 text-center text-sm text-gray-400">
            &copy; <?php echo date("Y"); ?> CareerLynk. All rights reserved.
        </div>
    </div>
</footer>

<script>
// Mobile menu toggle
document.getElementById('mobileMenuButton').addEventListener('click', function() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('hidden');
});

// User menu toggle
function toggleUserMenu() {
    const userMenu = document.getElementById('userMenu');
    userMenu.classList.toggle('hidden');
}

// Close user menu when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('userMenu');
    const userAvatar = document.querySelector('.rounded-full.cursor-pointer');
    
    if (userMenu && !userMenu.classList.contains('hidden') && 
        userAvatar && !userAvatar.contains(event.target) && 
        !userMenu.contains(event.target)) {
        userMenu.classList.add('hidden');
    }
});
</script>
</body>
</html>
