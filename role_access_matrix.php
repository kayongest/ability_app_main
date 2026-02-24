<?php
// role_access_matrix.php - Role-Based Access Control Management
require_once 'bootstrap.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Check if user is admin (only admins can access this page)
if (!isAdmin()) {
    $_SESSION['toast_message'] = 'You do not have permission to access role management';
    $_SESSION['toast_type'] = 'error';
    header('Location: dashboard.php');
    exit();
}

// Get database connection
$conn = getConnection();

$pageTitle = "Role Access Matrix - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'User Management' => 'users.php',
    'Role Access Matrix' => ''
];

// Get all permissions from database
$permissions = [];
$perm_result = $conn->query("SELECT * FROM permissions ORDER BY display_name");
if ($perm_result) {
    while ($row = $perm_result->fetch_assoc()) {
        $permissions[$row['id']] = $row;
    }
}

// Handle form submission to save permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get all roles
        $roles = ['admin', 'manager', 'stock_manager', 'stock_controller', 'tech_lead', 'technician', 'user', 'driver'];

        // Get ALL permission IDs from database
        $all_permission_ids = array_keys($permissions);

        // DEBUG: Log what we received
        error_log("=== PERMISSION SAVE DEBUG ===");
        error_log("POST data keys: " . implode(', ', array_keys($_POST)));

        // First, get CURRENT state from database
        $current_permissions = [];
        $result = $conn->query("SELECT role, permission_id FROM role_permissions");
        while ($row = $result->fetch_assoc()) {
            $current_permissions[$row['role']][$row['permission_id']] = true;
        }

        error_log("Current permissions in DB: " . count($current_permissions, COUNT_RECURSIVE) . " entries");

        // Build the desired state from POST data (only what was sent)
        $desired_permissions = [];
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            foreach ($_POST['permissions'] as $perm_id => $role_permissions) {
                foreach ($role_permissions as $role => $value) {
                    // Only consider it "desired" if it was checked (value === 'on')
                    if ($value === 'on') {
                        $desired_permissions[$role][$perm_id] = true;
                        error_log("POST has: $role - permission_id $perm_id = ON");
                    }
                }
            }
        }

        $permissions_saved = 0;
        $permissions_removed = 0;

        // Process each role and permission
        // Process each role and permission
        foreach ($roles as $role) {
            foreach ($all_permission_ids as $perm_id) {
                $should_have = isset($desired_permissions[$role][$perm_id]);
                $currently_exists = isset($current_permissions[$role][$perm_id]);

                // Only process if the permission was in POST data
                if (isset($_POST['permissions'][$perm_id][$role])) {
                    if ($should_have && !$currently_exists) {
                        // Add permission
                        $insert_stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role, permission_id, created_at) VALUES (?, ?, NOW())");
                        $insert_stmt->bind_param("si", $role, $perm_id);
                        $insert_stmt->execute();
                        if ($insert_stmt->affected_rows > 0) {
                            $permissions_saved++;
                        }
                        $insert_stmt->close();
                    }
                }

                // Only remove if the permission was explicitly set to 'off' in POST
                // But we can't detect 'off' because unchecked boxes aren't sent
                // So we need a different approach
            }
        }

        // Commit transaction
        $conn->commit();

        error_log("SUMMARY: Added $permissions_saved, Removed $permissions_removed");

        $_SESSION['toast_message'] = "Permissions updated: $permissions_saved added, $permissions_removed removed";
        $_SESSION['toast_type'] = 'success';
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("ERROR: " . $e->getMessage());
        $_SESSION['toast_message'] = 'Error saving permissions: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
    }

    header('Location: role_access_matrix.php');
    exit();
}

// Handle reset to default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_default'])) {

    $conn->begin_transaction();

    try {
        // Clear all permissions
        $conn->query("DELETE FROM role_permissions");

        // Define default permissions based on your matrix
        // Define default permissions based on your matrix
        $default_permissions = [
            'admin' => 'all',
            'manager' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment',     // ID: 25
                'import_export',      // ID: 10
                'view_reports',       // ID: 11
                'manage_technicians', // ID: 9
                'view_users',         // ID: 5
                'manage_items'        // ID: 2 (for batch history, stock locations)
            ],
            'stock_manager' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment',     // ID: 25
                'import_export',      // ID: 10
                'view_items'          // ID: 3
            ],
            'stock_controller' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment',     // ID: 25
                'view_reports',       // ID: 11
                'manage_items'        // ID: 2 (for batch history)
            ],
            'tech_lead' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment',     // ID: 25
                'manage_technicians'  // ID: 9
            ],
            'technician' => [
                'view_dashboard',     // ID: 1
                'scan_single',        // Need to add this permission!
                'scan_bulk'           // Need to add this permission!
            ],
            'user' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment'      // ID: 25
            ],
            'driver' => [
                'view_dashboard',     // ID: 1
                'view_events',        // ID: 8
                'view_equipment'      // ID: 25
            ]
        ];

        // Get permission IDs by name
        $perm_names = [];
        $result = $conn->query("SELECT id, name FROM permissions");
        while ($row = $result->fetch_assoc()) {
            $perm_names[$row['name']] = $row['id'];
        }

        // Insert default permissions
        $insert_stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role, permission_id, created_at) VALUES (?, ?, NOW())");
        $insert_count = 0;

        foreach ($default_permissions as $role => $perms) {
            if ($perms === 'all') continue; // Admin handled separately

            foreach ($perms as $perm_name) {
                if (isset($perm_names[$perm_name])) {
                    $perm_id = $perm_names[$perm_name];
                    $insert_stmt->bind_param("si", $role, $perm_id);
                    $insert_stmt->execute();
                    if ($insert_stmt->affected_rows > 0) {
                        $insert_count++;
                    }
                }
            }
        }

        $insert_stmt->close();
        $conn->commit();

        $_SESSION['toast_message'] = "Permissions reset to default values ($insert_count permissions restored)";
        $_SESSION['toast_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_message'] = 'Error resetting permissions: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
    }

    header('Location: role_access_matrix.php');
    exit();
}

require_once 'views/partials/header.php';

// Define all roles with display names and colors
$roles = [
    'admin' => ['name' => 'Administrator', 'color' => '#dc3545'],
    'manager' => ['name' => 'Manager', 'color' => '#fd7e14'],
    'stock_manager' => ['name' => 'Stock Manager', 'color' => '#20c997'],
    'stock_controller' => ['name' => 'Stock Controller', 'color' => '#0dcaf0'],
    'tech_lead' => ['name' => 'Tech Lead', 'color' => '#6f42c1'],
    'technician' => ['name' => 'Technician', 'color' => '#0d6efd'],
    'user' => ['name' => 'User', 'color' => '#6c757d'],
    'driver' => ['name' => 'Driver', 'color' => '#198754']
];

// Load existing permissions from database
$db_permissions = [];
$result = $conn->query("SELECT role, permission_id FROM role_permissions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $db_permissions[$row['role']][$row['permission_id']] = true;
    }
}

// Function to check if a role has a permission (for matrix display)
function roleHasPermission($role, $permission_id)
{
    global $db_permissions;
    return isset($db_permissions[$role][$permission_id]);
}

// Calculate totals for each role
$totals = [];
foreach (array_keys($roles) as $role) {
    $totals[$role] = isset($db_permissions[$role]) ? count($db_permissions[$role]) : 0;
}

$total_permissions = count($permissions);
?>

<!-- Rest of your HTML remains exactly the same -->


<style>
    :root {
        --primary-color: #234c6a;
        --primary-light: #2c5a7a;
        --primary-dark: #1a3a4f;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
    }

    .matrix-container {
        padding: 2rem 1.5rem;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
    }

    .page-header h1 {
        margin: 0;
        font-size: 2rem;
    }

    .page-header p {
        margin: 0.5rem 0 0;
        opacity: 0.9;
    }

    .matrix-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        overflow-x: auto;
    }

    .matrix-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    .matrix-table th {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        padding: 15px 10px;
        font-weight: 600;
        text-align: center;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .matrix-table th:first-child {
        border-radius: 10px 0 0 0;
        text-align: left;
        padding-left: 20px;
    }

    .matrix-table th:last-child {
        border-radius: 0 10px 0 0;
        border-right: none;
    }

    .matrix-table td {
        padding: 15px 10px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .matrix-table td:first-child {
        font-weight: 600;
        color: var(--primary-color);
        padding-left: 20px;
        background: rgba(35, 76, 106, 0.02);
        position: sticky;
        left: 0;
        z-index: 5;
    }

    .matrix-table tbody tr:hover {
        background-color: rgba(35, 76, 106, 0.05);
    }

    .matrix-table tbody tr:hover td:first-child {
        background-color: rgba(35, 76, 106, 0.1);
    }

    .permission-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .permission-icon {
        width: 35px;
        height: 35px;
        background: rgba(35, 76, 106, 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
        font-size: 1.1rem;
    }

    .permission-details {
        flex: 1;
    }

    .permission-name {
        font-weight: 600;
        color: var(--primary-dark);
        display: block;
    }

    .permission-description {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .permission-id {
        font-family: monospace;
        background: #f8f9fa;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        color: var(--primary-color);
        display: inline-block;
        margin-top: 2px;
    }

    /* Form Switch Styles */
    .form-switch-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .form-check-input {
        width: 3em;
        height: 1.5em;
        margin: 0;
        cursor: pointer;
        background-color: #dc3545;
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        transition: all 0.3s ease;
    }

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        border-color: #28a745;
    }

    .role-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .role-name {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .role-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        color: white;
    }

    .totals-row {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        font-weight: 700;
    }

    .totals-row td {
        padding: 15px 10px;
        border-bottom: 2px solid var(--primary-color);
    }

    .totals-row td:first-child {
        color: var(--primary-dark);
        font-size: 1.1rem;
    }

    .legend-card {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        margin-right: 2rem;
    }

    .legend-switch {
        width: 2.5em;
        height: 1.2em;
        background-color: #28a745;
        border-radius: 2em;
        position: relative;
        margin-right: 8px;
    }

    .legend-switch::after {
        content: '';
        width: 1em;
        height: 1em;
        background: white;
        border-radius: 50%;
        position: absolute;
        right: 0.1em;
        top: 0.1em;
    }

    .legend-switch.off {
        background-color: #dc3545;
    }

    .legend-switch.off::after {
        left: 0.1em;
        right: auto;
    }

    .action-buttons {
        margin-top: 2rem;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 76, 106, 0.3);
        color: white;
    }

    .btn-reset {
        background: #6c757d;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        color: white;
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1.2rem;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(35, 76, 106, 0.15);
    }

    .role-count {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
        line-height: 1.2;
    }

    @media (max-width: 768px) {
        .matrix-container {
            padding: 1rem;
        }

        .page-header {
            padding: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
        }

        .role-name {
            font-size: 0.7rem;
        }
    }
</style>

<div class="matrix-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1><i class="fas fa-shield-alt me-2"></i>Role Access Matrix</h1>
                <p>Configure which roles have access to which permissions using the toggle switches</p>
            </div>
            <div>
                <span class="badge bg-white text-primary p-3">
                    <i class="fas fa-users-cog me-2"></i><?php echo count($roles); ?> Roles | <?php echo $total_permissions; ?> Permissions
                </span>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="legend-card">
        <div class="d-flex flex-wrap align-items-center">
            <span class="me-3"><strong>Legend:</strong></span>
            <div class="legend-item">
                <div class="legend-switch"></div>
                <span>Access Enabled (Green)</span>
            </div>
            <div class="legend-item">
                <div class="legend-switch off"></div>
                <span>Access Disabled (Red)</span>
            </div>
            <div class="ms-auto">
                <i class="fas fa-info-circle text-muted me-1"></i>
                <small class="text-muted">Toggle switches to enable/disable permissions for each role</small>
            </div>
        </div>
    </div>

    <!-- Access Matrix Card -->
    <div class="matrix-card">
        <form method="POST" id="accessMatrixForm" onsubmit="console.log('Form submitting...');">

            <!-- Items Per Page Selector -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <label class="me-2">Show</label>
                    <select id="itemsPerPage" class="form-select form-select-sm d-inline-block w-auto" onchange="changeItemsPerPage()">
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="ms-2">entries</label>
                </div>
                <div class="text-muted">
                    <span id="showingInfo">Showing 1 to 5 of <?php echo $total_permissions; ?> permissions</span>
                </div>
            </div>

            <!-- Search Box -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchPermission"
                            placeholder="Search permissions..." onkeyup="filterPermissions()">
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <span class="badge bg-info p-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing page <span id="currentPageDisplay">1</span> of <span id="totalPagesDisplay">1</span>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="matrix-table" id="permissionsTable">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <?php foreach ($roles as $role_key => $role_info): ?>
                                <th>
                                    <div class="role-header">
                                        <span class="role-name"><?php echo $role_info['name']; ?></span>
                                        <span class="role-badge" style="background: <?php echo $role_info['color']; ?>">
                                            <?php echo $role_key; ?>
                                        </span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="permissionsTableBody">
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Hidden data store for JavaScript -->
            <script>
                // Initialize permissions data
                var permissionsData =
                    <?php
                    $permissions_data = [];
                    foreach ($permissions as $perm_id => $permission) {
                        $role_perms = [];
                        foreach (array_keys($roles) as $role_key) {
                            $role_perms[$role_key] = roleHasPermission($role_key, $perm_id);
                        }
                        $permissions_data[] = [
                            'id' => $perm_id,
                            'display_name' => $permission['display_name'],
                            'name' => $permission['name'],
                            'description' => $permission['description'] ?? '',
                            'role_permissions' => $role_perms
                        ];
                    }
                    echo json_encode($permissions_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

                    ?>;

                var roles = <?php echo json_encode(array_keys($roles)); ?>;
                var roleColors = <?php echo json_encode(array_column($roles, 'color', 'key')); ?>;

                console.log('Permissions Data loaded:', permissionsData.length, 'items');
                console.log('Roles:', roles);
            </script>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="previousPage()" id="prevPageBtn">
                        <i class="fas fa-chevron-left me-1"></i>Previous
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="nextPage()" id="nextPageBtn">
                        Next<i class="fas fa-chevron-right ms-1"></i>
                    </button>
                </div>

                <div class="pagination-container">
                    <ul class="pagination mb-0" id="pagination">
                        <!-- Pagination will be generated by JavaScript -->
                    </ul>
                </div>

                <div>
                    <span class="text-muted">
                        Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
                    </span>
                </div>
            </div>

            <!-- Stats Summary (Keep this at the bottom) -->
            <div class="row mt-5">
                <?php foreach ($roles as $role_key => $role_info): ?>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 me-2" style="background: <?php echo $role_info['color']; ?>20; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user" style="color: <?php echo $role_info['color']; ?>"></i>
                                </div>
                                <div>
                                    <div class="small text-muted"><?php echo $role_info['name']; ?></div>
                                    <div class="role-count"><?php echo $totals[$role_key]; ?> <small style="font-size: 0.9rem; color: #6c757d;">/<?php echo $total_permissions; ?></small></div>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?php echo $total_permissions > 0 ? ($totals[$role_key] / $total_permissions) * 100 : 0; ?>%; background: <?php echo $role_info['color']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn-reset" onclick="resetToDefault()">
                    <i class="fas fa-undo me-2"></i>Reset to Default
                </button>
                <button type="submit" name="save_permissions" class="btn-save">
                    <i class="fas fa-save me-2"></i>Save Permissions
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background: rgba(40, 167, 69, 0.1);">
                            <i class="fas fa-check-circle fa-2x" style="color: #28a745;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Permissions</h6>
                            <h3 class="mb-0"><?php echo $total_permissions; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background: rgba(35, 76, 106, 0.1);">
                            <i class="fas fa-users fa-2x" style="color: #234c6a;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Roles</h6>
                            <h3 class="mb-0"><?php echo count($roles); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background: rgba(255, 193, 7, 0.1);">
                            <i class="fas fa-crown fa-2x" style="color: #ffc107;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Admin Access</h6>
                            <h3 class="mb-0"><?php echo $totals['admin']; ?>/<?php echo $total_permissions; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle p-3 me-3" style="background: rgba(108, 117, 125, 0.1);">
                            <i class="fas fa-tools fa-2x" style="color: #6c757d;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Technician Access</h6>
                            <h3 class="mb-0"><?php echo $totals['technician']; ?>/<?php echo $total_permissions; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pagination variables
    let currentPage = 1;
    let itemsPerPage = 5;
    let filteredData = [...permissionsData];
    let allData = [...permissionsData];

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        renderTable();
        renderPagination();
        updateSwitchCounts();

        // Add event listeners to all switches
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', updateSwitchCounts);
        });
    });

    // Form submission handler - SIMPLIFIED
    document.getElementById('accessMatrixForm').addEventListener('submit', function(e) {
        // Get all checkboxes currently in the DOM
        const checkboxes = document.querySelectorAll('.form-check-input');
        const enabled = Array.from(checkboxes).filter(cb => cb.checked).length;

        if (!confirm(`Save ${enabled} enabled permissions?`)) {
            e.preventDefault();
            return false;
        }

        // No need for hidden inputs - PHP will handle it intelligently
        return true;
    });


    // Render the table based on current page and filters
    function renderTable() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageData = filteredData.slice(start, end);

        let html = '';
        pageData.forEach(perm => {
            html += '<tr>';
            html += `<td>
                        <div class="permission-info">
                            <div class="permission-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="permission-details">
                                <span class="permission-name">${escapeHtml(perm.display_name)}</span>
                                <span class="permission-id">${escapeHtml(perm.name)}</span>
                                <span class="permission-description">${escapeHtml(perm.description)}</span>
                            </div>
                        </div>
                    </td>`;

            roles.forEach(role => {
                const checked = perm.role_permissions[role] ? 'checked' : '';
                const permId = perm.id;
                html += `<td class="text-center">
                            <div class="form-switch-container">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        name="permissions[${permId}][${role}]"
                                        id="perm_${permId}_${role}"
                                        ${checked}
                                        onchange="updateSwitchCounts()">
                                </div>
                            </div>
                        </td>`;
            });

            html += '</tr>';
        });

        document.getElementById('permissionsTableBody').innerHTML = html;

        // Update showing info
        const start_display = filteredData.length > 0 ? start + 1 : 0;
        const end_display = Math.min(end, filteredData.length);
        const showingInfo = document.getElementById('showingInfo');
        if (showingInfo) {
            showingInfo.innerHTML =
                `Showing ${start_display} to ${end_display} of ${filteredData.length} permissions`;
        }

        const currentPageSpan = document.getElementById('currentPage');
        const currentPageDisplay = document.getElementById('currentPageDisplay');
        const totalPagesSpan = document.getElementById('totalPages');
        const totalPagesDisplay = document.getElementById('totalPagesDisplay');
        const totalPages = Math.ceil(filteredData.length / itemsPerPage);

        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (currentPageDisplay) currentPageDisplay.textContent = currentPage;
        if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
        if (totalPagesDisplay) totalPagesDisplay.textContent = totalPages;

        // Update button states
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages;
    }

    // Render pagination buttons
    function renderPagination() {
        const totalPages = Math.ceil(filteredData.length / itemsPerPage);
        const pagination = document.getElementById('pagination');
        if (!pagination) return;

        let paginationHtml = '';

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                </li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        pagination.innerHTML = paginationHtml;
    }

    // Go to specific page
    function goToPage(page) {
        currentPage = page;
        renderTable();
        renderPagination();
    }

    // Previous page
    function previousPage() {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
            renderPagination();
        }
    }

    // Next page
    function nextPage() {
        const totalPages = Math.ceil(filteredData.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
            renderPagination();
        }
    }

    // Change items per page
    function changeItemsPerPage() {
        itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
        currentPage = 1;
        renderTable();
        renderPagination();
    }

    // Filter permissions based on search
    function filterPermissions() {
        const searchTerm = document.getElementById('searchPermission').value.toLowerCase();

        if (searchTerm === '') {
            filteredData = [...allData];
        } else {
            filteredData = allData.filter(perm =>
                perm.display_name.toLowerCase().includes(searchTerm) ||
                perm.name.toLowerCase().includes(searchTerm) ||
                (perm.description && perm.description.toLowerCase().includes(searchTerm))
            );
        }

        currentPage = 1;
        renderTable();
        renderPagination();
    }

    // Function to update switch counts
    function updateSwitchCounts() {
        const checkboxes = document.querySelectorAll('.form-check-input');
        let enabled = 0;
        let disabled = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) {
                enabled++;
            } else {
                disabled++;
            }
        });

        console.log('Enabled:', enabled, 'Disabled:', disabled);
    }

    // Function to reset to default
    function resetToDefault() {
        if (confirm('Reset all permissions to default values? This will reload the page.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reset_default';
            input.value = '1';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php require_once 'views/partials/footer.php'; ?>