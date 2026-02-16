<?php
// Test database connection
try {
    $conn = new mysqli('localhost', 'root', '', 'ability_db');
    
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error . PHP_EOL;
        exit(1);
    }
    
    echo "Database connection: SUCCESS" . PHP_EOL;
    echo "Server version: " . $conn->server_info . PHP_EOL;
    
    // Test query
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "Database tables found: " . $result->num_rows . PHP_EOL;
        echo PHP_EOL . "Tables:" . PHP_EOL;
        while ($row = $result->fetch_array()) {
            echo "  - " . $row[0] . PHP_EOL;
        }
    }
    
    $conn->close();
    echo PHP_EOL . "All database tests passed!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
?>
