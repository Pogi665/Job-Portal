<?php
session_start();
$is_public_page = true; // Flag for header.php
// No database connection needed for the public index page unless fetching dynamic content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerLynk - Find Your Next Opportunity</title>
    <?php include 'header.php'; // Includes Tailwind, FontAwesome, Inter font via style.css link ?>
    <style>
        /* Custom scrollbar (optional, for aesthetics for this page) */
        body { /* Ensure Inter is applied if header.php's style.css doesn't cover it strongly enough */
            font-family: 'Inter', sans-serif;
        }
        html {
            scroll-behavior: smooth;
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<?php // Static header from index.html is replaced by the include above ?>

    <section class="relative py-28 md:py-40">
        <!-- Background image with overlay -->
        <div class="absolute inset-0">
            <img src="images/career-team.jpg" alt="Career professionals" class="w-full h-full object-cover object-top">
            <div class="absolute inset-0 bg-black opacity-70"></div>
        </div>
        
        <!-- Content overlay -->
        <div class="container relative z-20 mx-auto px-6 text-center text-white pt-16">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Welcome to <span class="text-blue-300">CareerLynk</span>
            </h1>
            <p class="text-lg md:text-xl mb-10 max-w-2xl mx-auto">
                Your ultimate connection to career opportunities and professional growth.
            </p>
        </div>
    </section>

    <section id="features" class="py-16 md:py-24 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-6">
                Why Choose CareerLynk?
            </h2>
            <p class="text-center text-gray-600 mb-12 md:mb-16 max-w-xl mx-auto">
                We provide a seamless and efficient platform for job seekers and employers.
            </p>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-1">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Vast Job Directory</h3>
                    <p class="text-gray-600">
                        Explore thousands of job listings across diverse industries and locations. Find the perfect match for your skills.
                    </p>
                </div>
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-1">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Direct Employer Connections</h3>
                    <p class="text-gray-600">
                        Connect directly with hiring managers and companies, streamlining your application process.
                    </p>
                </div>
                <div class="bg-gray-50 p-8 rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-1">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Easy Application Process</h3>
                    <p class="text-gray-600">
                        Apply for jobs with a simple, intuitive interface. Build your profile and get noticed.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-blue-600 relative">
        <!-- Background image with overlay -->
        <div class="absolute inset-0">
            <img src="images/background.jpg" alt="Career opportunities" class="w-full h-full object-cover object-center">
            <div class="absolute inset-0 bg-blue-800 opacity-80"></div>
        </div>
        
        <div class="container relative z-20 mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Ready to Take the Next Step?
            </h2>
            <p class="text-blue-100 text-lg mb-10 max-w-lg mx-auto">
                Join CareerLynk today and unlock a world of career opportunities.
                Your dream job is just a few clicks away.
            </p>
            <div class="space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="signup_page.php" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-4 px-8 rounded-lg text-lg transition duration-300 shadow-lg hover:shadow-xl inline-block">
                    Join Us
                </a>
                <a href="login.php" class="bg-white hover:bg-gray-200 text-blue-600 font-bold py-4 px-8 rounded-lg text-lg transition duration-300 shadow-lg hover:shadow-xl inline-block">
                    Log In
                </a>
            </div>
        </div>
    </section>

<?php include 'footer.php'; ?>

<?php // Mobile menu script from header.php should handle the mobile menu if that structure is used by header.php's public view ?>
<?php // The original script for mobile menu was tied to the static header. If header.php provides its own, this can be removed. ?>
<?php // For now, assuming header.php's script part (if any for public mobile) is self-contained or global. ?>

</body>
</html> 