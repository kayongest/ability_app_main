<?php
// debug_roles.php - REMOVE AFTER TESTING!
require_once 'bootstrap.php';
require_once 'includes/functions.php';

if (!isAdmin()) {
    die('Admin access only');
}

function getRolePageAccess($role) {
    $pages = [
        'Dashboard' => true,
        'Events' => !in_array($role, ['technician']),
        'Equipment' => !in_array($role, ['technician']),
        'Import' => in_array($role, ['admin', 'manager', 'stock_manager']),
        'Single Scan' => true,
        'Bulk Scan' => true,
        'Scan History' => in_array($role, ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead']),
        'Reports' => in_array($role, ['admin', 'manager', 'stock_controller']),
        'User Management' => $role == 'admin',
        'Technicians' => in_array($role, ['admin', 'manager', 'tech_lead']),
        'Stock Locations' => in_array($role, ['admin', 'manager', 'stock_manager']),
        'Batch History' => in_array($role, ['admin', 'manager', 'stock_controller']),
        'Settings' => $role == 'admin',
        'Profile' => true
    ];
    
    return $pages;
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>Role Access Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f7fb; }
        .table thead th { background: #234c6a; color: white; }
        .accessible { background-color: #d4edda; color: #155724; }
        .restricted { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4" style="color: #234c6a;">Role Access Matrix</h2>';
        
$roles = ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'];
$all_pages = array_keys(getRolePageAccess('admin'));

echo '<table class="table table-bordered table-hover">';
echo '<thead><tr><th>Page</th>';

foreach ($roles as $role) {
    echo '<th>' . ucfirst(str_replace('_', ' ', $role)) . '</th>';
}
echo '</tr></thead><tbody>';

foreach ($all_pages as $page) {
    echo '<tr>';
    echo '<td><strong>' . $page . '</strong></td>';
    
    foreach ($roles as $role) {
        $access = getRolePageAccess($role);
        $has_access = $access[$page] ?? false;
        $class = $has_access ? 'accessible' : 'restricted';
        $icon = $has_access ? '✓' : '✗';
        echo '<td class="' . $class . ' text-center">' . $icon . '</td>';
    }
    
    echo '</tr>';
}

// Add totals row
echo '<tr><td><strong>TOTAL</strong></td>';
foreach ($roles as $role) {
    $access = getRolePageAccess($role);
    $count = count(array_filter($access));
    echo '<td class="text-center"><strong>' . $count . '</strong></td>';
}
echo '</tr>';

echo '</tbody></table>';
echo '<p class="text-muted mt-3"><i class="fas fa-info-circle me-1"></i> This debug page should be removed in production!</p>';
echo '</div></body></html>';
?>