<?php
// create_tables.php
require_once 'config/database.php';

$conn = getConnection();

echo "<h1>Creating Database Tables</h1>";

$sql_queries = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        email VARCHAR(100),
        department VARCHAR(100),
        role ENUM('admin', 'stock_controller', 'technician') DEFAULT 'technician',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Batches table
    "CREATE TABLE IF NOT EXISTS batches (
        id INT PRIMARY KEY AUTO_INCREMENT,
        batch_id VARCHAR(50) UNIQUE NOT NULL,
        batch_name VARCHAR(255),
        requested_by INT,
        submitted_by INT,
        approved_by INT,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME,
        approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        job_sheet_number VARCHAR(50),
        location_applied VARCHAR(100),
        project_manager VARCHAR(100),
        vehicle_number VARCHAR(50),
        driver_name VARCHAR(100),
        destination VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Items table
    "CREATE TABLE IF NOT EXISTS items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        serial_number VARCHAR(100) UNIQUE,
        category VARCHAR(100),
        status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
        stock_location VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Batch items table
    "CREATE TABLE IF NOT EXISTS batch_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        batch_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity INT DEFAULT 1,
        status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
        destination VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($sql_queries as $sql) {
    echo "<h3>Executing:</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Success</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
    echo "<hr>";
}

// Add some test data if tables are empty
echo "<h2>Adding test data if tables are empty:</h2>";

// Check if users table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Add test users
    $test_users = [
        "('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Administrator', 'admin@company.com', 'IT', 'admin')",
        "('kayongest', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'Kayonga Ernest', 'kayonga@company.com', 'Stock Control', 'stock_controller')",
        "('raul_tech', '" . password_hash('raul123', PASSWORD_DEFAULT) . "', 'Kayonga Raul', 'raul@company.com', 'Technical Department', 'technician')",
        "('irene_ops', '" . password_hash('irene123', PASSWORD_DEFAULT) . "', 'Mudacumura Irene', 'irene@company.com', 'Operations', 'technician')"
    ];
    
    $sql = "INSERT INTO users (username, password, full_name, email, department, role) VALUES " . implode(', ', $test_users);
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Added test users</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding users: " . $conn->error . "</p>";
    }
}

// Check if items table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM items");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Add test items
    $test_items = [
        "('Laptop Dell XPS 15', 'DLXPS15-001', 'Electronics', 'available', 'IT Department')",
        "('24\" Monitor', 'MON-24-001', 'Electronics', 'available', 'IT Department')",
        "('Wireless Keyboard', 'KB-WIRE-001', 'Electronics', 'available', 'IT Department')",
        "('Office Chair', 'OC-ERG-001', 'Furniture', 'available', 'Warehouse B')",
        "('Power Drill', 'PD-2024-001', 'Tools', 'in_use', 'Construction Site')"
    ];
    
    $sql = "INSERT INTO items (name, serial_number, category, status, stock_location) VALUES " . implode(', ', $test_items);
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Added test items</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding items: " . $conn->error . "</p>";
    }
}

$conn->close();
?>