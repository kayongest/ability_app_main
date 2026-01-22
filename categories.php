<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// categories.php - Categories Management
require_once 'bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once 'includes/functions.php';

// Get database connection
function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $host = 'localhost';
            $dbname = 'ability_db';
            $username = 'root';
            $password = '';

            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Initialize database connection
$pdo = getDBConnection();

$pageTitle = "Categories - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Settings' => '#',
    'Categories' => ''
];


// FIXED SECTION: Get all categories
$categories = [];
try {
    // Simple query without department_code (which doesn't exist in your table)
    $sql = "SELECT * FROM categories ORDER BY parent_id, name";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Check if 'code' field exists
    if (!empty($categories) && !isset($categories[0]['code'])) {
        error_log("WARNING: 'code' field not found in categories table!");
        // Check what fields we actually have
        if (isset($categories[0])) {
            error_log("Available fields: " . implode(', ', array_keys($categories[0])));
        }
    }

    // Add parent names
    foreach ($categories as &$category) {
        if (!empty($category['parent_id'])) {
            try {
                $parentStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $parentStmt->execute([$category['parent_id']]);
                $category['parent_name'] = $parentStmt->fetchColumn() ?: 'N/A';
            } catch (Exception $e) {
                $category['parent_name'] = 'N/A';
            }
        } else {
            $category['parent_name'] = 'N/A';
        }

        // Ensure 'code' field exists (use 'id' as fallback)
        if (!isset($category['code'])) {
            $category['code'] = 'CAT-' . $category['id'];
        }
    }
    unset($category);
} catch (Exception $e) {
    $error = "Error loading categories: " . $e->getMessage();
    error_log($error);
}

// Get all departments for dropdown
$departments = [];
try {
    $deptStmt = $pdo->query("SELECT code, name FROM departments WHERE is_active = 1 ORDER BY name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading departments: " . $e->getMessage());
}

// Get parent categories for dropdown
$parentCategories = [];
try {
    $parentStmt = $pdo->query("SELECT id, name, code FROM categories WHERE parent_id IS NULL ORDER BY name");
    $parentCategories = $parentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading parent categories: " . $e->getMessage());
}

// Get item counts for each category
$itemCounts = [];
try {
    // Try different possible column names for category reference in items table
    $possibleColumns = ['category_code', 'category_id', 'cat_code', 'cat_id', 'category'];

    foreach ($possibleColumns as $col) {
        try {
            // Check if column exists
            $checkStmt = $pdo->prepare("SHOW COLUMNS FROM items LIKE ?");
            $checkStmt->execute([$col]);
            if ($checkStmt->rowCount() > 0) {
                $countStmt = $pdo->query("SELECT $col, COUNT(*) as count FROM items WHERE $col IS NOT NULL AND $col != '' GROUP BY $col");
                $countResults = $countStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($countResults as $row) {
                    $itemCounts[$row[$col]] = $row['count'];
                }
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }
} catch (Exception $e) {
    error_log("Could not get item counts: " . $e->getMessage());
}

require_once 'views/partials/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-list me-2"></i>Categories
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-2"></i>Add Category
        </button>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table me-2"></i>All Categories
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="categoriesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Parent Category</th>
                            <th>Items Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-list fa-2x text-muted mb-3"></i>
                                    <h5>No Categories Found</h5>
                                    <p class="text-muted">Add your first category to get started</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($category['code']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($category['description'] ?? 'No description'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($category['parent_name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $itemCounts[$category['code']] ?? 0; ?> items</span>
                                    </td>
                                    <td>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-warning edit-category"
                                                data-id="<?php echo $category['id']; ?>"
                                                data-code="<?php echo htmlspecialchars($category['code']); ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                                data-parent="<?php echo $category['parent_id'] ?? ''; ?>"
                                                data-active="<?php echo $category['is_active']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger delete-category"
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="code" class="form-label">Category Code *</label>
                        <input type="text" class="form-control" id="code" name="code"
                            required maxlength="10" placeholder="e.g., IT-EQ">
                        <div class="form-text">Unique code (max 10 characters, will be converted to uppercase)</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name"
                            required placeholder="e.g., IT Equipment">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="3" placeholder="Brief description of this category"></textarea>
                    </div>


                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- No Parent (Root Category) --</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?> (<?php echo htmlspecialchars($parent['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active Category</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Category Code *</label>
                        <input type="text" class="form-control" id="edit_code" name="code"
                            required maxlength="10">
                    </div>

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <!-- REMOVE THIS ENTIRE DEPARTMENT SECTION -->
                    <!--
                    <div class="mb-3">
                        <label for="edit_department_code" class="form-label">Department</label>
                        <select class="form-control" id="edit_department_code" name="department_code">
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['code']); ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?> (<?php echo htmlspecialchars($dept['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    -->

                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Parent Category</label>
                        <select class="form-control" id="edit_parent_id" name="parent_id">
                            <option value="">-- No Parent (Root Category) --</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?> (<?php echo htmlspecialchars($parent['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Active Category</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">

                    <p>Are you sure you want to delete the category: <strong id="delete_name"></strong>?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        This action cannot be undone. Any items assigned to this category will need to be reassigned.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Category
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const code = this.dataset.code;
                const name = this.dataset.name;
                const description = this.dataset.description;
                const parent = this.dataset.parent;
                const isActive = this.dataset.active === '1';

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_code').value = code;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_parent_id').value = parent;
                document.getElementById('edit_is_active').checked = isActive;

                const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                editModal.show();
            });
        });

        // Delete Category
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;

                document.getElementById('delete_id').value = id;
                document.getElementById('delete_name').textContent = name;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                deleteModal.show();
            });
        });

        // Initialize DataTable with error handling
        // Initialize DataTable - Minimal version
        if ($.fn.DataTable) {
            // Wait a bit to ensure DOM is fully loaded
            setTimeout(function() {
                try {
                    var table = $('#categoriesTable').DataTable({
                        "paging": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "responsive": true
                    });
                } catch (e) {
                    console.error('DataTables error:', e);
                    // If DataTables fails, remove the initialization and keep basic table
                    $('#categoriesTable').removeAttr('id').addClass('table-striped');
                }
            }, 100);
        }
    });
</script>

<style>
    #categoriesTable th {
        font-weight: 600;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge {
        font-size: 0.85em;
        padding: 4px 8px;
    }
</style>

<?php require_once 'views/partials/footer.php'; ?>