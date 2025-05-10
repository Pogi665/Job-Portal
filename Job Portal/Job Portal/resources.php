<?php
require_once 'includes/header.php';

// Define categories of resources
$categories = [
    'resume' => [
        'name' => 'Resume & CV',
        'icon' => 'fa-file-alt',
        'color' => 'bg-blue-100 text-blue-600'
    ],
    'interview' => [
        'name' => 'Interview Tips',
        'icon' => 'fa-comments',
        'color' => 'bg-green-100 text-green-600'
    ],
    'career' => [
        'name' => 'Career Development',
        'icon' => 'fa-chart-line',
        'color' => 'bg-purple-100 text-purple-600'
    ],
    'job-search' => [
        'name' => 'Job Search Strategies',
        'icon' => 'fa-search',
        'color' => 'bg-indigo-100 text-indigo-600'
    ],
    'skills' => [
        'name' => 'Skills Development',
        'icon' => 'fa-tools',
        'color' => 'bg-red-100 text-red-600'
    ],
    'workplace' => [
        'name' => 'Workplace Success',
        'icon' => 'fa-briefcase',
        'color' => 'bg-yellow-100 text-yellow-600'
    ]
];

// Sample resources (in a real application, these would come from a database)
$resources = [
    [
        'id' => 1,
        'title' => 'How to Write a Winning Resume',
        'description' => 'Learn the essential components of a strong resume that will get you noticed by employers.',
        'type' => 'article',
        'category' => 'resume',
        'image' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 2,
        'title' => 'Top 10 Interview Questions and Answers',
        'description' => 'Prepare for your next interview with these common questions and expert advice on how to answer them.',
        'type' => 'article',
        'category' => 'interview',
        'image' => 'https://images.unsplash.com/photo-1516387938699-a93567ec168e?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 3,
        'title' => 'Mastering the Art of Networking',
        'description' => 'Learn how to build and leverage professional connections for career growth and job opportunities.',
        'type' => 'video',
        'category' => 'career',
        'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 4,
        'title' => 'Remote Work Success Strategies',
        'description' => 'Tips and tools to help you stay productive and engaged while working remotely.',
        'type' => 'webinar',
        'category' => 'workplace',
        'image' => 'https://images.unsplash.com/photo-1593642634402-b0eb5e2eebc9?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 5,
        'title' => 'Using LinkedIn Effectively in Your Job Search',
        'description' => 'Maximize your LinkedIn presence to attract recruiters and find job opportunities.',
        'type' => 'guide',
        'category' => 'job-search',
        'image' => 'https://images.unsplash.com/photo-1611944212129-29977ae1398c?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 6,
        'title' => 'Building a Professional Portfolio',
        'description' => 'Showcase your work effectively with these portfolio building tips for creative professionals.',
        'type' => 'guide',
        'category' => 'resume',
        'image' => 'https://images.unsplash.com/photo-1586281380117-5a60ae2050cc?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 7,
        'title' => 'Negotiating Your Salary',
        'description' => 'Learn effective strategies to negotiate a better compensation package.',
        'type' => 'article',
        'category' => 'career',
        'image' => 'https://images.unsplash.com/photo-1589666564459-93cdd3ab856a?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ],
    [
        'id' => 8,
        'title' => 'In-Demand Tech Skills for 2023',
        'description' => 'The technology skills employers are looking for this year and how to acquire them.',
        'type' => 'report',
        'category' => 'skills',
        'image' => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
        'link' => '#'
    ]
];

// Handle category filtering
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
if ($selectedCategory && !isset($categories[$selectedCategory])) {
    $selectedCategory = null;
}

// Apply filter
$filteredResources = $resources;
if ($selectedCategory) {
    $filteredResources = array_filter($resources, function($resource) use ($selectedCategory) {
        return $resource['category'] === $selectedCategory;
    });
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Career Resources</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Explore our collection of resources designed to help you advance your career, improve your job search, and develop professional skills.
            </p>
        </div>
        
        <!-- Categories Filter -->
        <div class="mb-10">
            <div class="flex flex-wrap justify-center gap-3">
                <a href="resources.php" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $selectedCategory === null ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> transition-colors">
                    All Resources
                </a>
                
                <?php foreach ($categories as $catKey => $category): ?>
                    <a href="?category=<?php echo $catKey; ?>" class="px-4 py-2 rounded-full text-sm font-medium flex items-center <?php echo $selectedCategory === $catKey ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> transition-colors">
                        <i class="fas <?php echo $category['icon']; ?> mr-2"></i>
                        <?php echo $category['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Resources Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($filteredResources as $resource): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:shadow-lg hover:-translate-y-1">
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="<?php echo h($resource['image']); ?>" alt="<?php echo h($resource['title']); ?>" class="w-full h-48 object-cover">
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $categories[$resource['category']]['color']; ?>">
                                <i class="fas <?php echo $categories[$resource['category']]['icon']; ?> mr-1"></i>
                                <?php echo $categories[$resource['category']]['name']; ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <?php echo ucfirst($resource['type']); ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            <?php echo h($resource['title']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php echo h($resource['description']); ?>
                        </p>
                        <a href="<?php echo $resource['link']; ?>" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                            Read More <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($filteredResources)): ?>
                <div class="col-span-full py-16 text-center">
                    <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No resources found</h3>
                    <p class="text-gray-500 mb-6">Try selecting a different category</p>
                    <a href="resources.php" class="btn btn-primary">View All Resources</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Newsletter Signup -->
        <div class="mt-16 bg-indigo-700 rounded-lg shadow-lg p-8 text-white">
            <div class="max-w-4xl mx-auto">
                <div class="flex flex-wrap md:flex-nowrap items-center">
                    <div class="w-full md:w-2/3 mb-6 md:mb-0 md:pr-8">
                        <h2 class="text-2xl font-bold mb-3">Get Career Tips in Your Inbox</h2>
                        <p class="text-indigo-100">Subscribe to our newsletter to receive the latest career advice, job search tips, and industry insights.</p>
                    </div>
                    <div class="w-full md:w-1/3">
                        <form action="#" method="POST" class="flex">
                            <input type="email" name="email" placeholder="Your email address" required 
                                   class="flex-grow px-4 py-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-400 text-gray-800">
                            <button type="submit" class="bg-indigo-900 px-4 py-2 rounded-r-md hover:bg-indigo-800 transition-colors">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 