<?php
require_once 'includes/header.php';
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Page Title -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800">Career Blog</h1>
            <p class="text-gray-600 mt-2">Industry insights, career advice, and job market trends</p>
        </div>
        
        <!-- Featured Article -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-10">
            <div class="md:flex">
                <div class="md:w-1/3">
                    <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                         alt="Featured Article" class="w-full h-full object-cover">
                </div>
                <div class="md:w-2/3 p-6">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold mb-1">Featured Article</div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">How to Stand Out in a Competitive Job Market</h2>
                    <p class="text-gray-600 mb-4">In today's competitive job landscape, it's not enough to just have qualifications. Learn effective strategies to make your application rise to the top of the pile.</p>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Author Avatar" class="w-8 h-8 rounded-full mr-2">
                        <span>By John Smith • </span>
                        <span class="ml-1">5 days ago</span>
                    </div>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        Read More <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Blog Categories -->
        <div class="mb-10">
            <div class="flex flex-wrap justify-center gap-2 mb-6">
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">All</a>
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">Career Advice</a>
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">Job Search</a>
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">Interview Tips</a>
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">Resume Writing</a>
                <a href="#" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-full hover:bg-indigo-100 hover:text-indigo-800">Industry Insights</a>
            </div>
        </div>
        
        <!-- Blog Post Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Blog Post 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1573497620053-ea5300f94f21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Career Advice</span>
                        <span class="text-xs text-gray-500">June 18, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">10 Essential Skills for Remote Work Success</h3>
                    <p class="text-gray-600 mb-4">Master the skills needed to thrive in the remote work environment that has become increasingly common post-pandemic.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Blog Post 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Interview Tips</span>
                        <span class="text-xs text-gray-500">June 12, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">How to Answer the "What's Your Weakness?" Question</h3>
                    <p class="text-gray-600 mb-4">Tackle this dreaded interview question with confidence using our expert strategies and real-world examples.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Blog Post 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Resume Writing</span>
                        <span class="text-xs text-gray-500">June 5, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Resume Trends for 2023: What's In and What's Out</h3>
                    <p class="text-gray-600 mb-4">Stay current with the latest resume trends to ensure your application materials reflect today's best practices.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Blog Post 4 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Industry Insights</span>
                        <span class="text-xs text-gray-500">May 29, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">The Top 5 Growing Industries in 2023</h3>
                    <p class="text-gray-600 mb-4">Discover which industries are projected to see significant growth this year and what opportunities they might offer.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Blog Post 5 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Job Search</span>
                        <span class="text-xs text-gray-500">May 22, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">How to Use LinkedIn Effectively in Your Job Search</h3>
                    <p class="text-gray-600 mb-4">Maximize your LinkedIn presence with these expert tips to help you network and find your next career opportunity.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Blog Post 6 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <img src="https://images.unsplash.com/photo-1558403194-611308249627?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" 
                     alt="Blog Post" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider">Career Advice</span>
                        <span class="text-xs text-gray-500">May 15, 2023</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Making a Career Change at 40: Is It Too Late?</h3>
                    <p class="text-gray-600 mb-4">Explore the challenges and opportunities of pivoting to a new career path later in life, with advice from those who've done it.</p>
                    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Read Article <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="mt-10 flex justify-center">
            <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-indigo-500 bg-indigo-50 text-sm font-medium text-indigo-600">
                    1
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    2
                </a>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    3
                </a>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                    ...
                </span>
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    8
                </a>
                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        </div>
        
        <!-- Newsletter Signup -->
        <div class="mt-12 bg-indigo-50 rounded-lg p-8 text-center">
            <h3 class="text-2xl font-bold text-gray-800 mb-3">Subscribe to Our Newsletter</h3>
            <p class="text-gray-600 mb-6 max-w-xl mx-auto">Get the latest career advice, job searching tips, and industry insights delivered directly to your inbox.</p>
            <form class="max-w-md mx-auto">
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="email" placeholder="Your email address" class="flex-grow px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Subscribe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 