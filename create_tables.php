<?php
// create_tables.php - Run this once to create the accessories tables
session_start();
require_once 'includes/bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Create Tables - aBility";
require_once 'views/partials/header.php';

try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();
    
    $messages = [];
    
    // Check if accessories table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'accessories'");
    if ($checkTable->num_rows === 0) {
        // Create accessories table
        $sql = "CREATE TABLE accessories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            total_quantity INT DEFAULT 1,
            available_quantity INT DEFAULT 1,
            minimum_stock INT DEFAULT 5,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            $messages[] = "✅ Table 'accessories' created successfully.";
        } else {
            $messages[] = "❌ Failed to create 'accessories' table: " . $conn->error;
        }
    } else {
        $messages[] = "✅ Table 'accessories' already exists.";
    }
    
    // Check if item_accessories table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'item_accessories'");
    if ($checkTable->num_rows === 0) {
        // Create item_accessories table
        $sql = "CREATE TABLE item_accessories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            item_id INT NOT NULL,
            accessory_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (accessory_id) REFERENCES accessories(id) ON DELETE CASCADE,
            UNIQUE KEY unique_item_accessory (item_id, accessory_id)
        )";
        
        if ($conn->query($sql)) {
            $messages[] = "✅ Table 'item_accessories' created successfully.";
        } else {
            $messages[] = "❌ Failed to create 'item_accessories' table: " . $conn->error;
        }
    } else {
        $messages[] = "✅ Table 'item_accessories' already exists.";
    }
    
    // Add some sample accessories
    $checkSample = $conn->query("SELECT COUNT(*) as count FROM accessories");
    $row = $checkSample->fetch_assoc();
    
    if ($row['count'] == 0) {
        $sampleAccessories = [
            ['Power Cable', 'Standard power cable', 10, 10],
            ['HDMI Cable', 'High-speed HDMI cable', 15, 15],
            ['USB Cable', 'USB Type-A to Type-B cable', 20, 20],
            ['Remote Control', 'Universal remote control', 5, 5],
            ['Carrying Case', 'Protective carrying case', 8, 8],
            ['Battery Pack', 'Rechargeable battery pack', 12, 12],
            ['Power Adapter', 'AC/DC power adapter', 6, 6],
            ['Ethernet Cable', 'CAT6 Ethernet cable', 10, 10],
            ['User Manual', 'Printed user manual', 25, 25],
            ['Mounting Bracket', 'Wall mounting bracket', 4, 4]
        ];
        
        $insertStmt = $conn->prepare("
            INSERT INTO accessories (name, description, total_quantity, available_quantity) 
            VALUES (?, ?, ?, ?)
        ");
        
        $inserted = 0;
        foreach ($sampleAccessories as $accessory) {
            $insertStmt->bind_param("ssii", $accessory[0], $accessory[1], $accessory[2], $accessory[3]);
            if ($insertStmt->execute()) {
                $inserted++;
            }
        }
        $insertStmt->close();
        
        $messages[] = "✅ Added $inserted sample accessories.";
    }
    
    $db->close();
    
} catch (Exception $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-database me-2"></i>Create Database Tables
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This script will create the necessary tables for accessories management.
                        Run it only once.
                    </div>
                    
                    <div class="mt-4">
                        <h5>Results:</h5>
                        <div class="list-group">
                            <?php foreach ($messages as $message): ?>
                                <div class="list-group-item">
                                    <?php echo $message; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <a href="accessories.php" class="btn btn-success ms-2">
                            <i class="fas fa-puzzle-piece me-1"></i> Go to Accessories
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/partials/footer.php'; ?>