<?php
// Database setup script
require_once 'config.php';

echo "<h1>JobHunt Database Setup</h1>";

try {
    // Initialize database
    initializeDatabase();
    echo "<p style='color: green;'>✅ Database initialized successfully!</p>";
    
    // Test database connection
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Show database statistics
    $tables = ['users', 'job_categories', 'companies', 'jobs', 'job_applications', 'testimonials'];
    
    echo "<h2>Database Statistics:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table</th><th>Records</th></tr>";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Sample Data:</h2>";
    
    // Show sample jobs
    $stmt = $pdo->query("SELECT j.title, c.name as company, j.location, j.job_type 
                         FROM jobs j 
                         LEFT JOIN companies c ON j.company_id = c.id 
                         LIMIT 5");
    $jobs = $stmt->fetchAll();
    
    echo "<h3>Sample Jobs:</h3>";
    echo "<ul>";
    foreach ($jobs as $job) {
        echo "<li><strong>{$job['title']}</strong> at {$job['company']} - {$job['location']} ({$job['job_type']})</li>";
    }
    echo "</ul>";
    
    // Show sample categories
    $stmt = $pdo->query("SELECT name, job_count FROM job_categories");
    $categories = $stmt->fetchAll();
    
    echo "<h3>Job Categories:</h3>";
    echo "<ul>";
    foreach ($categories as $category) {
        echo "<li><strong>{$category['name']}</strong> - {$category['job_count']} jobs</li>";
    }
    echo "</ul>";
    
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Make sure your web server (Apache/Nginx) is running</li>";
    echo "<li>Access your website at: <a href='index.php'>index.php</a></li>";
    echo "<li>Test the registration and login functionality</li>";
    echo "<li>Browse and apply for jobs</li>";
    echo "</ol>";
    
    echo "<p style='color: blue;'><strong>Setup completed successfully! Your JobHunt website is ready to use.</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

table {
    margin: 20px 0;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #f4f4f4;
}

ul, ol {
    margin: 10px 0;
    padding-left: 20px;
}

a {
    color: #6a38c2;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
