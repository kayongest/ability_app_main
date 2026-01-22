<?php

/**
 * Common functions for aBility Manager
 */

// Check if user is logged in
function isLoggedIn()
{
    // Check if session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user_id exists in session
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get condition badge HTML - UPDATED
 */
function getConditionBadge($condition)
{
    $conditions = [
        'excellent' => ['label' => 'Excellent', 'class' => 'bg-success'],
        'good'      => ['label' => 'Good', 'class' => 'bg-primary'],
        'fair'      => ['label' => 'Fair', 'class' => 'bg-info'],
        'poor'      => ['label' => 'Poor', 'class' => 'bg-warning'],
        'broken'    => ['label' => 'Broken', 'class' => 'bg-danger'],
        'repair'    => ['label' => 'Needs Repair', 'class' => 'bg-dark'],
        'new'       => ['label' => 'New', 'class' => 'bg-success'],
        'damaged'   => ['label' => 'Damaged', 'class' => 'bg-danger']
    ];

    if (isset($conditions[$condition])) {
        $badge = $conditions[$condition];
        return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
    }

    return '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($condition)) . '</span>';
}

/**
 * Get status badge HTML - UPDATED
 */
function getStatusBadge($status)
{
    $statuses = [
        'available'     => ['label' => 'Available', 'class' => 'bg-success'],
        'in_use'        => ['label' => 'In Use', 'class' => 'bg-primary'],
        'maintenance'   => ['label' => 'Maintenance', 'class' => 'bg-warning'],
        'reserved'      => ['label' => 'Reserved', 'class' => 'bg-info'],
        'disposed'      => ['label' => 'Disposed', 'class' => 'bg-secondary'],
        'lost'          => ['label' => 'Lost', 'class' => 'bg-danger'],
        'retired'       => ['label' => 'Retired', 'class' => 'bg-dark']
    ];

    if (isset($statuses[$status])) {
        $badge = $statuses[$status];
        return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
    }

    return '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

/**
 * Get category badge HTML - UPDATED to match your categories
 */
function getCategoryBadge($category)
{
    $colors = [
        'Audio'         => 'primary',
        'Video'         => 'success',
        'Lighting'      => 'warning',
        'Translation'   => 'info',
        'IT'            => 'danger',
        'Rigging'       => 'secondary',
        'Electrical'    => 'dark',
        'Furniture'     => 'purple',
        'Other'         => 'light'
    ];

    $color = isset($colors[$category]) ? $colors[$category] : 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($category) . '</span>';
}

// Get item accessories
// Look for this function and update it:
function getItemAccessories($item_id, $conn)
{
    $accessories = [];

    try {
        // Check if item_accessories table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'item_accessories'");
        if (!$checkTable || $checkTable->num_rows === 0) {
            return $accessories; // Return empty array if table doesn't exist
        }

        // Use a simpler query to avoid SQL errors
        $stmt = $conn->prepare("
            SELECT a.id, a.name, a.description 
            FROM accessories a
            INNER JOIN item_accessories ia ON a.id = ia.accessory_id 
            WHERE ia.item_id = ? AND a.is_active = 1
        ");

        // Debug: Check if prepare was successful
        if (!$stmt) {
            error_log("SQL prepare error: " . $conn->error);
            return $accessories;
        }

        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $accessories[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in getItemAccessories: " . $e->getMessage());
        // Return empty array on error
    }

    return $accessories;
}

// Get accessory badge
function getAccessoryBadge($accessory_name)
{
    if (empty($accessory_name)) {
        return '<span class="badge bg-secondary">None</span>';
    }

    $accessoryColors = [
        'power cable' => 'danger',
        'hdmi cable' => 'info',
        'usb cable' => 'success',
        'remote' => 'warning',
        'stand' => 'dark',
        'case' => 'secondary',
        'battery' => 'danger',
        'adapter' => 'primary',
        'ethernet' => 'info',
        'manual' => 'light text-dark',
        'warranty' => 'success',
        'screws' => 'secondary',
        'lens' => 'info',
        'memory' => 'primary',
        'stylus' => 'warning',
        'dongle' => 'info',
        'microphone' => 'success',
        'tripod' => 'dark',
        'other' => 'secondary'
    ];

    $color = 'secondary';
    foreach ($accessoryColors as $key => $col) {
        if (stripos($accessory_name, $key) !== false) {
            $color = $col;
            break;
        }
    }

    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($accessory_name) . '</span>';
}

function formatDate($date, $format = 'M d, Y H:i')
{
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

// Add this function for low stock accessories
function getLowStockAccessories($conn, $limit = 10)
{
    $low_stock = [];

    try {
        // First check if the accessories table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'accessories'");
        if ($checkTable->num_rows === 0) {
            error_log("Accessories table does not exist");
            return $low_stock;
        }

        $sql = "
            SELECT 
                a.id,
                a.name,
                a.description,
                a.available_quantity,
                a.minimum_stock,
                a.total_quantity,
                COUNT(DISTINCT ia.item_id) as assigned_count
            FROM accessories a
            LEFT JOIN item_accessories ia ON a.id = ia.accessory_id
            WHERE a.is_active = 1 
            AND a.available_quantity <= a.minimum_stock
            GROUP BY a.id
            ORDER BY a.available_quantity ASC
            LIMIT ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $low_stock = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error getting low stock accessories: " . $e->getMessage());
    }

    return $low_stock;
}

// Add this function for stock alerts
function getStockAlerts($conn)
{
    $alerts = [
        'low_stock_count' => 0,
        'out_of_stock_count' => 0,
        'low_stock_items' => []
    ];

    try {
        // First check if the accessories table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'accessories'");
        if ($checkTable->num_rows === 0) {
            error_log("Accessories table does not exist");
            return $alerts;
        }

        // Get low stock count
        $sql = "
            SELECT 
                COUNT(*) as low_stock,
                SUM(CASE WHEN available_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
            FROM accessories 
            WHERE is_active = 1 
            AND available_quantity <= minimum_stock
        ";

        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $alerts['low_stock_count'] = $row['low_stock'];
            $alerts['out_of_stock_count'] = $row['out_of_stock'];
        }

        // Get top 5 low stock items
        $lowStmt = $conn->prepare("
            SELECT name, available_quantity, minimum_stock
            FROM accessories
            WHERE is_active = 1 
            AND available_quantity <= minimum_stock
            ORDER BY available_quantity ASC
            LIMIT 5
        ");

        if ($lowStmt) {
            $lowStmt->execute();
            $lowResult = $lowStmt->get_result();
            $alerts['low_stock_items'] = $lowResult->fetch_all(MYSQLI_ASSOC);
            $lowStmt->close();
        }
    } catch (Exception $e) {
        error_log("Error getting stock alerts: " . $e->getMessage());
    }

    return $alerts;
}

function getCategories()
{
    return [
        'Audio'         => 'Audio Equipment',
        'Video'         => 'Video Equipment',
        'Lighting'      => 'Lighting Equipment',
        'Translation'   => 'Translation Equipment',
        'IT'            => 'IT Equipment',
        'Rigging'       => 'Rigging Equipment',
        'Electrical'    => 'Electrical Equipment',
        'Furniture'     => 'Furniture',
        'Other'         => 'Other'
    ];
}

function getDepartments()
{
    return [
        'AUD'         => 'Audio',
        'VID'         => 'Video',
        'LIGT'      => 'Lighting',
        'TRN'   => 'Translation',
        'IT'            => 'IT',
        'RIG'       => 'Rigging',
        'ELECTR'    => 'Electrical',
        'FURNT'     => 'Furniture',
    ];
}

function getLocations()
{
    return [
        'BK Arena'      => 'BK Arena',
        'Ndera'         => 'Ndera',
        'Masoro'        => 'Masoro',
        'KCC'           => 'KCC',
        'Warehouse A'   => 'Warehouse A',
        'Warehouse B'   => 'Warehouse B',
        'On Site'       => 'On Site',
        'In Transit'    => 'In Transit'
    ];
}

function getConditions()
{
    return [
        'new'       => 'New',
        'good'      => 'Good',
        'fair'      => 'Fair',
        'poor'      => 'Poor',
        'damaged'   => 'Damaged',
        'repair'    => 'Needs Repair',
        'excellent' => 'Excellent',
        'broken'    => 'Broken'
    ];
}

function getStatuses()
{
    return [
        'available'     => 'Available',
        'in_use'        => 'In Use',
        'reserved'      => 'Reserved',
        'maintenance'   => 'Maintenance',
        'disposed'      => 'Disposed',
        'lost'          => 'Lost',
        'retired'       => 'Retired'
    ];
}

// Check if user is logged in (for API endpoints)
function requireApiAuthentication()
{
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'redirect' => BASE_URL . 'login.php'
        ]);
        exit();
    }
}

// Add this to your functions.php if not already there
function redirect($url, $statusCode = 303)
{
    header('Location: ' . $url, true, $statusCode);
    exit();
}

// Get dashboard statistics
function getDashboardStats($db)
{
    $stats = [
        'total_items'   => 0,
        'available'     => 0,
        'in_use'        => 0,
        'maintenance'   => 0,
        'categories'    => 0,
        'reserved'      => 0,
        'disposed'      => 0,
        'lost'          => 0
    ];

    try {
        // Total items
        $result = $db->query("SELECT COUNT(*) as count FROM items");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_items'] = $row['count'] ?? 0;
        }

        // Available items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'available'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['available'] = $row['count'] ?? 0;
        }

        // In use items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'in_use'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['in_use'] = $row['count'] ?? 0;
        }

        // Maintenance items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'maintenance'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['maintenance'] = $row['count'] ?? 0;
        }

        // Distinct categories
        $result = $db->query("SELECT COUNT(DISTINCT category) as count FROM items");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['categories'] = $row['count'] ?? 0;
        }

        // Reserved items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'reserved'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['reserved'] = $row['count'] ?? 0;
        }

        // Disposed items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'disposed'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['disposed'] = $row['count'] ?? 0;
        }

        // Lost items
        $result = $db->query("SELECT COUNT(*) as count FROM items WHERE status = 'lost'");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['lost'] = $row['count'] ?? 0;
        }

        return $stats;
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return $stats;
    }
}

// Get recent items - PRODUCTION READY VERSION
function getRecentItems($conn, $limit = 100)
{
    $items = [];

    if (!$conn) {
        return $items;
    }

    try {
        $sql = "SELECT 
                    i.id, 
                    i.item_name, 
                    i.serial_number, 
                    i.category, 
                    i.brand, 
                    i.model, 
                    i.department, 
                    i.description,
                    i.condition,
                    i.stock_location,
                    i.quantity,
                    i.status,
                    i.qr_code,
                    i.created_at,
                    i.updated_at
                FROM items i
                ORDER BY i.created_at DESC 
                LIMIT " . (int)$limit;

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }

        return $items;
    } catch (Exception $e) {
        // Log error but don't break the page
        error_log("Error in getRecentItems: " . $e->getMessage());
        return $items;
    }
}


/**
 * Generate QR code for an item
 */
function generateQRCode($item_id, $item_name, $serial_number)
{
    // Check if QR code library is available
    if (!class_exists('QRcode')) {
        // You may need to install a QR code library
        // Example: using PHP QR Code library
        // require_once 'phpqrcode/qrlib.php';

        // For now, return null
        return null;
    }

    try {
        // Create data for QR code
        $data = json_encode([
            'id' => $item_id,
            'name' => $item_name,
            'serial' => $serial_number,
            'system' => 'aBility Inventory'
        ]);

        // Generate QR code
        $qr_dir = '../uploads/qr_codes/';
        if (!file_exists($qr_dir)) {
            mkdir($qr_dir, 0777, true);
        }

        $filename = 'qr_' . $item_id . '_' . time() . '.png';
        $filepath = $qr_dir . $filename;

        // Generate QR code using library
        // QRcode::png($data, $filepath, QR_ECLEVEL_L, 10);

        // For now, return a placeholder
        return 'uploads/qr_codes/' . $filename;
    } catch (Exception $e) {
        error_log("QR Code generation error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if serial number exists
 */
function serialExists($serial_number, $conn)
{
    try {
        $stmt = $conn->prepare("SELECT id FROM items WHERE serial_number = ?");
        $stmt->bind_param("s", $serial_number);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    } catch (Exception $e) {
        error_log("Error checking serial: " . $e->getMessage());
        return false;
    }
}


// Function to get total quantity per item name (aggregating all serial numbers)
function getTotalQuantityPerItem($conn, $item_name = null)
{
    if ($item_name) {
        $stmt = $conn->prepare("
            SELECT 
                item_name,
                COUNT(*) as unique_serial_numbers,
                SUM(quantity) as total_units,
                category,
                brand_model,
                GROUP_CONCAT(serial_number SEPARATOR ', ') as serial_numbers
            FROM items 
            WHERE item_name = ?
            GROUP BY item_name, brand_model, category
        ");
        $stmt->bind_param("s", $item_name);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } else {
        // Get all items grouped by name
        return $conn->query("
            SELECT 
                item_name,
                category,
                brand_model,
                COUNT(*) as unique_serial_numbers,
                SUM(quantity) as total_units,
                MIN(created_at) as first_added,
                MAX(updated_at) as last_updated
            FROM items 
            GROUP BY item_name, brand_model, category
            ORDER BY total_units DESC, item_name
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

// Export equipment data to CSV
function exportToCSV($data, $filename = 'equipment_export.csv')
{
    if (empty($data)) {
        return false;
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");

    // Add header
    fputcsv($output, array_keys($data[0]));

    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Calculate depreciation
function calculateDepreciation($purchasePrice, $purchaseDate, $lifespanYears = 5, $depreciationMethod = 'straight')
{
    if (empty($purchaseDate) || $purchasePrice <= 0) {
        return $purchasePrice;
    }

    $purchaseTimestamp = strtotime($purchaseDate);
    $currentTimestamp = time();

    if ($purchaseTimestamp === false || $purchaseTimestamp > $currentTimestamp) {
        return $purchasePrice;
    }

    $secondsInYear = 365 * 24 * 60 * 60;
    $yearsUsed = ($currentTimestamp - $purchaseTimestamp) / $secondsInYear;

    if ($depreciationMethod === 'straight') {
        $annualDepreciation = $purchasePrice / $lifespanYears;
        $totalDepreciation = $annualDepreciation * min($yearsUsed, $lifespanYears);
        $currentValue = $purchasePrice - $totalDepreciation;
    } else {
        // Double declining balance method
        $rate = 2 / $lifespanYears;
        $currentValue = $purchasePrice;
        for ($i = 0; $i < min($yearsUsed, $lifespanYears); $i++) {
            $currentValue -= $currentValue * $rate;
        }
    }

    return max(0, round($currentValue, 2));
}

// Generate next equipment code
function generateNextEquipmentCode($db)
{
    try {
        $sql = "SELECT MAX(id) as max_id FROM items";
        $result = $db->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            $nextId = ($row['max_id'] ?? 0) + 1;
            return 'EQ-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }
    } catch (Exception $e) {
        error_log("Equipment code error: " . $e->getMessage());
        return 'EQ-' . date('Ymd') . rand(100, 999);
    }
}

// Get equipment by ID
function getEquipmentById($db, $id)
{
    try {
        $sql = "SELECT * FROM items WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row;
    } catch (Exception $e) {
        error_log("Get equipment error: " . $e->getMessage());
        return null;
    }
}

// Search equipment
function searchEquipment($db, $searchTerm, $category = '', $status = '', $location = '')
{
    $items = [];

    try {
        $sql = "SELECT * FROM items WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($searchTerm)) {
            $sql .= " AND (item_name LIKE ? OR serial_number LIKE ? OR description LIKE ? OR brand_model LIKE ?)";
            $searchTerm = "%$searchTerm%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }

        if (!empty($category)) {
            $sql .= " AND category = ?";
            $params[] = $category;
            $types .= 's';
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= 's';
        }

        if (!empty($location)) {
            $sql .= " AND stock_location = ?";
            $params[] = $location;
            $types .= 's';
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
        return $items;
    } catch (Exception $e) {
        error_log("Search equipment error: " . $e->getMessage());
        return [];
    }
}

// Get equipment count by status
function getEquipmentCountByStatus($db)
{
    $counts = [
        'available'     => 0,
        'in_use'        => 0,
        'maintenance'   => 0,
        'reserved'      => 0,
        'disposed'      => 0,
        'lost'          => 0,
        'retired'       => 0
    ];

    try {
        $sql = "SELECT status, COUNT(*) as count FROM items GROUP BY status";
        $result = $db->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $status = $row['status'];
                if (isset($counts[$status])) {
                    $counts[$status] = $row['count'];
                }
            }
        }

        return $counts;
    } catch (Exception $e) {
        error_log("Count by status error: " . $e->getMessage());
        return $counts;
    }
}

// Get equipment count by category
function getEquipmentCountByCategory($db)
{
    $counts = [];

    try {
        $sql = "SELECT category, COUNT(*) as count FROM items GROUP BY category ORDER BY count DESC";
        $result = $db->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['category']] = $row['count'];
            }
        }

        return $counts;
    } catch (Exception $e) {
        error_log("Count by category error: " . $e->getMessage());
        return $counts;
    }
}

// Get equipment count by location
function getEquipmentCountByLocation($db)
{
    $counts = [];

    try {
        $sql = "SELECT stock_location, COUNT(*) as count FROM items GROUP BY stock_location ORDER BY count DESC";
        $result = $db->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['stock_location']] = $row['count'];
            }
        }

        return $counts;
    } catch (Exception $e) {
        error_log("Count by location error: " . $e->getMessage());
        return $counts;
    }
}

// Get all items with all fields
function getAllItems($db, $limit = null, $offset = 0)
{
    $items = [];

    try {
        $sql = "SELECT 
                    id, item_name, serial_number, category, department, 
                    description, brand_model, `condition`, stock_location, 
                    notes, quantity, status, image, qr_code,
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(updated_at, '%Y-%m-d %H:%i:%s') as updated_at
                FROM items 
                ORDER BY created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
        } else {
            $stmt = $db->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
        return $items;
    } catch (Exception $e) {
        error_log("Get all items error: " . $e->getMessage());
        return [];
    }
}

// Get item details for view page
function getItemDetails($db, $id)
{
    try {
        $sql = "SELECT 
                    id, item_name, serial_number, category, department, 
                    description, brand_model, `condition`, stock_location, 
                    notes, quantity, status, image, qr_code,
                    DATE_FORMAT(created_at, '%Y-%m-d %H:%i:%s') as created_at,
                    DATE_FORMAT(updated_at, '%Y-%m-d %H:%i:%s') as updated_at
                FROM items 
                WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();

        return $item;
    } catch (Exception $e) {
        error_log("Get item details error: " . $e->getMessage());
        return null;
    }
}

// Generate unique serial number
function generateUniqueSerial($db, $prefix = 'EQ')
{
    try {
        // Try to get the next ID
        $sql = "SELECT MAX(id) as max_id FROM items";
        $result = $db->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            $nextId = ($row['max_id'] ?? 0) + 1;

            // Generate serial with prefix and timestamp
            $timestamp = time();
            $serial = $prefix . '-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            // Check if serial exists
            $checkStmt = $db->prepare("SELECT id FROM items WHERE serial_number = ?");
            $checkStmt->bind_param('s', $serial);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // If exists, add random number
                $serial = $serial . '-' . rand(100, 999);
            }

            $checkStmt->close();
            return $serial;
        }
    } catch (Exception $e) {
        error_log("Generate serial error: " . $e->getMessage());
    }

    // Fallback
    return $prefix . '-' . date('YmdHis') . '-' . rand(1000, 9999);
}


/**
 * Map Excel columns to database fields with validation
 */
function mapExcelColumns($headers, $field_mapping)
{
    $mappedData = [];
    $requiredFields = ['item_name', 'serial_number', 'category'];
    $missingRequired = [];

    // Check required fields
    foreach ($requiredFields as $field) {
        if (empty($field_mapping[$field]) || $field_mapping[$field] === 'none') {
            $missingRequired[] = $field;
        }
    }

    if (!empty($missingRequired)) {
        throw new Exception('Missing required field mapping: ' . implode(', ', $missingRequired));
    }

    return $field_mapping;
}

/**
 * Process imported row data with validation
 */
function processImportedRow($rowData, $fieldMapping, $headers, $rowNumber)
{
    $processed = [];

    // Map Excel data based on field mapping
    foreach ($fieldMapping as $dbField => $excelCol) {
        if ($excelCol && $excelCol !== 'none') {
            $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($excelCol) - 1;
            $processed[$dbField] = isset($rowData[$colIndex]) ? trim($rowData[$colIndex]) : '';
        }
    }

    // Validate required fields
    $required = ['item_name', 'serial_number', 'category'];
    foreach ($required as $field) {
        if (empty($processed[$field])) {
            throw new Exception("Row $rowNumber: Missing required field '$field'");
        }
    }

    // Set defaults
    $defaults = [
        'quantity' => 1,
        'condition' => 'good',
        'status' => 'available',
        'brand_model' => ''
    ];

    foreach ($defaults as $field => $defaultValue) {
        if (empty($processed[$field])) {
            $processed[$field] = $defaultValue;
        }
    }

    // Create brand_model from brand and model
    if (!empty($processed['brand']) && !empty($processed['model'])) {
        $processed['brand_model'] = $processed['brand'] . ' ' . $processed['model'];
    } elseif (!empty($processed['brand'])) {
        $processed['brand_model'] = $processed['brand'];
    } elseif (!empty($processed['model'])) {
        $processed['brand_model'] = $processed['model'];
    }

    // Clean up empty values
    foreach ($processed as $key => $value) {
        if ($value === '') {
            unset($processed[$key]);
        }
    }

    return $processed;
}

/**
 * Import items from Excel/CSV file
 */
function importItemsFromFile($filePath, $fieldMapping, $testMode = false)
{
    require_once 'vendor/autoload.php';

    $results = [
        'success' => 0,
        'updated' => 0,
        'errors' => [],
        'skipped' => 0,
        'total_rows' => 0
    ];

    try {
        // Load spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Get data
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Database connection
        $db = new Database();
        $conn = $db->getConnection();

        $results['total_rows'] = $highestRow - 1; // Exclude header

        // Process rows starting from row 2 (skip header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];

            // Get cell values for this row
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $rowData[] = $cellValue ? trim($cellValue) : '';
            }

            try {
                // Process row
                $itemData = processImportedRow($rowData, $fieldMapping, [], $row);

                // Check if serial number exists
                $checkSql = "SELECT id, item_name FROM items WHERE serial_number = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param('s', $itemData['serial_number']);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    // Update existing item
                    if (!$testMode) {
                        $existing = $checkResult->fetch_assoc();

                        // Build update query
                        $updateFields = [];
                        $updateValues = [];
                        $types = '';

                        foreach ($itemData as $field => $value) {
                            if ($field !== 'serial_number') { // Don't update serial number
                                $updateFields[] = "`$field` = ?";
                                $updateValues[] = $value;

                                // Determine type
                                if ($field === 'quantity') {
                                    $types .= 'i';
                                } else {
                                    $types .= 's';
                                }
                            }
                        }

                        // Add serial number for WHERE clause
                        $updateValues[] = $itemData['serial_number'];
                        $types .= 's';

                        $updateSql = "UPDATE items SET " . implode(', ', $updateFields) .
                            ", updated_at = NOW() WHERE serial_number = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bind_param($types, ...$updateValues);

                        if ($updateStmt->execute()) {
                            $results['updated']++;
                        } else {
                            throw new Exception("Update failed: " . $updateStmt->error);
                        }

                        $updateStmt->close();
                    } else {
                        $results['updated']++; // Count in test mode
                    }
                } else {
                    // Insert new item
                    if (!$testMode) {
                        $fields = array_keys($itemData);
                        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
                        $values = array_values($itemData);

                        // Determine types
                        $types = '';
                        foreach ($itemData as $value) {
                            if (is_int($value)) {
                                $types .= 'i';
                            } else {
                                $types .= 's';
                            }
                        }

                        $insertSql = "INSERT INTO items (`" . implode('`,`', $fields) .
                            "`, created_at) VALUES ($placeholders, NOW())";
                        $insertStmt = $conn->prepare($insertSql);
                        $insertStmt->bind_param($types, ...$values);

                        if ($insertStmt->execute()) {
                            $results['success']++;

                            // Generate QR code in background
                            $item_id = $conn->insert_id;
                            // generateQRCode($item_id, $conn); // Uncomment if you want QR generation

                        } else {
                            throw new Exception("Insert failed: " . $insertStmt->error);
                        }

                        $insertStmt->close();
                    } else {
                        $results['success']++; // Count in test mode
                    }
                }

                $checkStmt->close();
            } catch (Exception $e) {
                $results['errors'][] = "Row $row: " . $e->getMessage();
                $results['skipped']++;
            }
        }

        $conn->close();
    } catch (Exception $e) {
        $results['errors'][] = "File processing error: " . $e->getMessage();
    }

    return $results;
}

/**
 * Download Excel template
 */
function downloadExcelTemplate()
{
    require_once 'vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
        'item_name',
        'serial_number',
        'category',
        'brand',
        'model',
        'department',
        'description',
        'specifications',
        'condition',
        'stock_location',
        'storage_location',
        'notes',
        'quantity',
        'status',
        'tags'
    ];

    // Write headers
    foreach ($headers as $index => $header) {
        $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
    }

    // Add example data
    $examples = [
        [
            'MacBook Pro 16"',
            'MBP-2023-001',
            'IT',
            'Apple',
            'MacBook Pro 16-inch M2',
            'IT Department',
            'Company laptop for developers',
            'M2 Pro, 32GB RAM, 1TB SSD',
            'excellent',
            'Warehouse A',
            'Shelf B3',
            'For development team',
            1,
            'available',
            'laptop,apple,development'
        ],
        [
            'Projector Epson',
            'PROJ-EP-045',
            'Video',
            'Epson',
            'EH-TW7000',
            'VID',
            '4K Projector for events',
            '4K, 3000 lumens, HDR',
            'good',
            'BK Arena',
            'Storage Room 2',
            'Needs calibration',
            2,
            'available',
            'projector,4k,event'
        ]
    ];

    foreach ($examples as $rowIndex => $exampleRow) {
        foreach ($exampleRow as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
        }
    }

    // Style the header row
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFE0E0E0']
        ]
    ];

    $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

    // Auto-size columns
    foreach (range('A', 'O') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Create writer and output
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="item_import_template.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}

/**
 * Get Excel column letters for dropdown
 */
function getExcelColumns($maxColumns = 26)
{
    $columns = [];
    for ($i = 1; $i <= $maxColumns; $i++) {
        $columns[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    }
    return $columns;
}

/**
 * Create field mapping dropdown HTML
 */
function renderFieldMappingDropdown($fieldName, $label, $isRequired = false, $headers = [])
{
    $requiredStar = $isRequired ? ' <span class="text-danger">*</span>' : '';

    $html = '<div class="mb-3">';
    $html .= '<label for="' . htmlspecialchars($fieldName) . '" class="form-label">';
    $html .= htmlspecialchars($label) . $requiredStar . '</label>';
    $html .= '<select class="form-select" id="' . htmlspecialchars($fieldName) . '" ';
    $html .= 'name="field_mapping[' . htmlspecialchars($fieldName) . ']">';
    $html .= '<option value="">-- Select Column --</option>';

    foreach ($headers as $index => $header) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
        $displayText = $colLetter . ': ' . htmlspecialchars($header);
        $html .= '<option value="' . $colLetter . '">' . $displayText . '</option>';
    }

    $html .= '</select>';
    $html .= '</div>';

    return $html;
}

/**
 * Quick CSV import for simple files
 */
function quickCSVImport($csvFile, $conn)
{
    $results = [
        'success' => 0,
        'errors' => [],
        'skipped' => 0
    ];

    try {
        $handle = fopen($csvFile, "r");
        if (!$handle) {
            throw new Exception("Cannot open CSV file");
        }

        // Read header
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new Exception("CSV file is empty or invalid");
        }

        // Simple mapping - assume first 3 columns are required
        $rowNum = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNum++;

            try {
                // Basic validation - require at least 3 columns
                if (count($data) < 3) {
                    $results['skipped']++;
                    continue;
                }

                $itemData = [
                    'item_name' => $data[0] ?? '',
                    'serial_number' => $data[1] ?? '',
                    'category' => $data[2] ?? '',
                    'brand' => $data[3] ?? '',
                    'model' => $data[4] ?? '',
                    'department' => $data[5] ?? '',
                    'description' => $data[6] ?? '',
                    'condition' => $data[7] ?? 'good',
                    'stock_location' => $data[8] ?? '',
                    'quantity' => intval($data[9] ?? 1),
                    'status' => $data[10] ?? 'available',
                    'notes' => $data[11] ?? ''
                ];

                // Check if serial exists
                $checkSql = "SELECT id FROM items WHERE serial_number = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param('s', $itemData['serial_number']);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    // Update
                    $updateSql = "UPDATE items SET 
                        item_name = ?, category = ?, brand = ?, model = ?, 
                        department = ?, description = ?, `condition` = ?, 
                        stock_location = ?, quantity = ?, status = ?, notes = ?,
                        updated_at = NOW()
                        WHERE serial_number = ?";

                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param(
                        'ssssssssisss',
                        $itemData['item_name'],
                        $itemData['category'],
                        $itemData['brand'],
                        $itemData['model'],
                        $itemData['department'],
                        $itemData['description'],
                        $itemData['condition'],
                        $itemData['stock_location'],
                        $itemData['quantity'],
                        $itemData['status'],
                        $itemData['notes'],
                        $itemData['serial_number']
                    );

                    if ($updateStmt->execute()) {
                        $results['success']++;
                    }

                    $updateStmt->close();
                } else {
                    // Insert
                    $insertSql = "INSERT INTO items (
                        item_name, serial_number, category, brand, model,
                        department, description, `condition`, stock_location,
                        quantity, status, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->bind_param(
                        'sssssssssiss',
                        $itemData['item_name'],
                        $itemData['serial_number'],
                        $itemData['category'],
                        $itemData['brand'],
                        $itemData['model'],
                        $itemData['department'],
                        $itemData['description'],
                        $itemData['condition'],
                        $itemData['stock_location'],
                        $itemData['quantity'],
                        $itemData['status'],
                        $itemData['notes']
                    );

                    if ($insertStmt->execute()) {
                        $results['success']++;
                    }

                    $insertStmt->close();
                }

                $checkStmt->close();
            } catch (Exception $e) {
                $results['errors'][] = "Row $rowNum: " . $e->getMessage();
                $results['skipped']++;
            }
        }

        fclose($handle);
    } catch (Exception $e) {
        $results['errors'][] = $e->getMessage();
    }

    return $results;
}
