<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Connection successful!\n\n";
    
    // Check categories
    $stmt = $db->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll();
    echo "Categories:\n";
    foreach ($categories as $cat) {
        echo "- ID: {$cat['id']}, Name: {$cat['name']}, Slug: {$cat['slug']}\n";
    }
    
    // Check animals/products count
    $stmt = $db->query("SELECT COUNT(*) as count FROM animals");
    $count = $stmt->fetch();
    echo "\nTotal animals/products count: {$count['count']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
