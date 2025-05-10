</main>

<footer class="bg-gray-800 text-white pt-10 pb-6">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">CareerLynk</h3>
                <p class="text-gray-300 mb-4">Your connection to opportunity. Find your dream job or the perfect candidate.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">For Job Seekers</h3>
                <ul class="space-y-2">
                    <li><a href="jobs.php" class="text-gray-300 hover:text-white">Browse Jobs</a></li>
                    <li><a href="companies.php" class="text-gray-300 hover:text-white">Companies</a></li>
                    <li><a href="job-alerts.php" class="text-gray-300 hover:text-white">Job Alerts</a></li>
                    <li><a href="resources.php" class="text-gray-300 hover:text-white">Career Resources</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">For Employers</h3>
                <ul class="space-y-2">
                    <li><a href="employer/post-job.php" class="text-gray-300 hover:text-white">Post a Job</a></li>
                    <li><a href="employer/applications.php" class="text-gray-300 hover:text-white">Manage Applications</a></li>
                    <li><a href="employer/dashboard.php" class="text-gray-300 hover:text-white">Employer Dashboard</a></li>
                    <li><a href="pricing.php" class="text-gray-300 hover:text-white">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Company</h3>
                <ul class="space-y-2">
                    <li><a href="about.php" class="text-gray-300 hover:text-white">About Us</a></li>
                    <li><a href="blog.php" class="text-gray-300 hover:text-white">Blog</a></li>
                    <li><a href="contact.php" class="text-gray-300 hover:text-white">Contact Us</a></li>
                    <li><a href="privacy.php" class="text-gray-300 hover:text-white">Privacy Policy</a></li>
                    <li><a href="terms.php" class="text-gray-300 hover:text-white">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-6 text-sm text-gray-400 text-center">
            <p>&copy; <?php echo date('Y'); ?> CareerLynk. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
// Toggle mobile menu
document.getElementById('mobileMenuButton').addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
});

// Toggle user menu
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('hidden');
}

// Close menus when clicking outside
window.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (userMenu && !e.target.closest('img') && !userMenu.contains(e.target)) {
        userMenu.classList.add('hidden');
    }
    
    if (mobileMenu && !e.target.closest('#mobileMenuButton') && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.add('hidden');
    }
});
</script>
</body>
</html>
