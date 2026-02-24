<?php
// items.php - Main entry point for equipment management
require_once 'bootstrap.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Get database connection for permission checks
$conn = getConnection();

// Define permission requirements for each action
$action_permissions = [
    'list' => 'view_equipment',
    'view' => 'view_equipment',
    'create' => 'add_equipment',
    'edit' => 'edit_equipment'
];

// Get the action from URL
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Check if user has permission for this action
$required_permission = $action_permissions[$action] ?? 'view_equipment';

if (!hasPermission($required_permission)) {
    $_SESSION['toast_message'] = 'You do not have permission to ' . 
        ($action === 'create' ? 'add equipment' : 
         ($action === 'edit' ? 'edit equipment' : 
          ($action === 'view' ? 'view equipment details' : 'access equipment')));
    $_SESSION['toast_type'] = 'error';
    header('Location: dashboard_sections.php');
    exit();
}

$pageTitle = "Equipment Management - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard_sections.php',
    'Equipment' => 'items.php'
];

// Set page title based on action
if ($action === 'create') {
    $pageTitle = "Add Equipment - aBility";
    $breadcrumbItems['Add New'] = '';
} elseif ($action === 'view' && $id) {
    // Try to get item name for page title
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT item_name FROM items WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        if ($item) {
            $pageTitle = htmlspecialchars($item['item_name']) . " - aBility";
        }
        $stmt->close();
        $db->close();
    } catch (Exception $e) {
        // Ignore error for title
    }
    $breadcrumbItems['View Item'] = '';
} elseif ($action === 'edit' && $id) {
    $pageTitle = "Edit Equipment - aBility";
    $breadcrumbItems['Edit'] = '';
}

// Include header
require_once 'views/partials/header.php';

// Include the appropriate view based on action
switch ($action) {
    case 'create':
        require_once 'views/items/create.php';
        break;
    case 'edit':
        require_once 'views/items/edit.php';
        break;
    case 'view':
        require_once 'views/items/view.php';
        break;
    case 'list':
    default:
        require_once 'views/items/index.php';
        break;
}

// Include footer
require_once 'views/partials/footer.php';
?>