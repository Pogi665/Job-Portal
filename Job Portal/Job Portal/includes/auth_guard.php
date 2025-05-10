<?php
/**
 * Auth Guard - Manages authentication requirements for different pages
 * Include this file at the top of pages that require login
 */

require_once 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    // Remember the page the user was trying to access
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Only show login message if not directly accessing the main page
    $isMainPage = false;
    
    // Check if this is the main index page
    $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
    if ($scriptName === 'index.php' && 
        (empty($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] === '')) {
        $isMainPage = true;
    }
    
    // Set message only if not directly accessing the main page
    if (!$isMainPage) {
        setMessage('info', 'Please log in to access this page');
    }
    
    header('Location: ' . getRelativePath() . 'login.php');
    exit;
}

/**
 * Get the relative path to the root directory
 * This is needed for proper redirects regardless of directory depth
 */
function getRelativePath() {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $rootDir = $_SERVER['DOCUMENT_ROOT'];
    
    // Check if we're in a subdirectory
    if ($scriptDir != '/') {
        // Count directory levels to get back to root
        $path = str_replace($rootDir, '', $scriptDir);
        $levels = substr_count($path, '/');
        
        // If we're in the root directory
        if ($levels <= 1) {
            return '';
        }
        
        // Build the relative path
        $relativePath = '';
        for ($i = 1; $i < $levels; $i++) {
            $relativePath .= '../';
        }
        return $relativePath;
    }
    
    return '';
} 