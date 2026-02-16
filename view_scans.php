<?php
// view_scans.php - Simple view of scan logs
require_once 'bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$pageTitle = "View Scans - aBility";
require_once 'views/partials/header.php';

try {
    $db = getDatabase();
    $conn = $db->getConnection();
    
    // Get all scan logs with item and user info
    $sql = "SELECT 
                sl.*,
                i.item_name,
                i.serial_number,
                i.category,
                i.status as item_status,
                u.username,
                u.full_name
            FROM scan_logs sl
            LEFT JOIN items i ON sl.item_id = i.id
            LEFT JOIN users u ON sl.user_id = u.id
            ORDER BY sl.scan_timestamp DESC";
    
    $result = $conn->query($sql);
    
    echo '<div class="container-fluid mt-4">';
    echo '<h1><i class="fas fa-history me-2"></i>Scan Logs</h1>';
    echo '<p>Total scans: ' . $result->num_rows . '</p>';
    
    if ($result->num_rows > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead class="table-dark">';
        echo '<tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>Item</th>
                <th>Serial</th>
                <th>Type</th>
                <th>User</th>
                <th>Location</th>
                <th>Notes</th>
                <th>Actions</th>
              </tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = $result->fetch_assoc()) {
            // Format timestamp
            $timestamp = date('Y-m-d H:i:s', strtotime($row['scan_timestamp']));
            
            // Color code for scan type
            $type_colors = [
                'check_in' => 'success',
                'check_out' => 'primary',
                'maintenance' => 'warning',
                'inventory' => 'info'
            ];
            $type_color = $type_colors[$row['scan_type']] ?? 'secondary';
            
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $timestamp . '</td>';
            echo '<td><a href="items/view.php?id=' . $row['item_id'] . '">' . htmlspecialchars($row['item_name']) . '</a></td>';
            echo '<td><code>' . htmlspecialchars($row['serial_number']) . '</code></td>';
            echo '<td><span class="badge bg-' . $type_color . '">' . $row['scan_type'] . '</span></td>';
            echo '<td>' . htmlspecialchars($row['full_name'] ?? $row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['location'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
            echo '<td>
                    <button class="btn btn-sm btn-info view-scan" data-id="' . $row['id'] . '">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-scan" data-id="' . $row['id'] . '">
                        <i class="fas fa-trash"></i>
                    </button>
                  </td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">No scan logs found yet. Scan some items first!</div>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

require_once 'views/partials/footer.php';