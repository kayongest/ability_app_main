<?php
// batch_history.php
require_once 'includes/bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Use DatabaseFix class
try {
    $db = new DatabaseFix();
    $conn = $db->getConnection();

    // Get all batches
    $sql = "SELECT bs.*, u.username as submitted_by_name 
            FROM batch_scans bs 
            LEFT JOIN users u ON bs.submitted_by = u.id 
            ORDER BY bs.submitted_at DESC";

    $result = $conn->query($sql);

    $batches = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "Batch History - aBility";
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Batch History' => ''
];

require_once 'views/partials/header.php';
?>

<style>
    .batch-card {
        border-left: 4px solid #007bff;
        margin-bottom: 15px;
        transition: transform 0.2s;
    }

    .batch-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .batch-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-completed {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .status-processing {
        background-color: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .status-failed {
        background-color: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-history me-2"></i>Batch History
        </h1>
        <div>
            <a href="scan_2.php" class="btn btn-primary">
                <i class="fas fa-qrcode me-1"></i> New Scan
            </a>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <input type="date" class="form-control" id="dateFilter">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="completed">Completed</option>
                        <option value="processing">Processing</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Search batch ID or name...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="fas fa-filter-circle-xmark me-1"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batches List -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Submitted Batches</h6>
        </div>
        <div class="card-body">
            <?php if (empty($batches)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No batches found</h5>
                    <p class="text-muted">Start scanning items to create your first batch</p>
                    <a href="scan_2.php" class="btn btn-primary mt-2">
                        <i class="fas fa-qrcode me-1"></i> Start Scanning
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Batch Name</th>
                                <th>Items</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $batch): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($batch['batch_id']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($batch['batch_name']); ?></strong>
                                        <?php if ($batch['notes']): ?>
                                            <small class="d-block text-muted">
                                                <?php echo truncateText($batch['notes'], 50); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-primary"><?php echo $batch['total_items']; ?> total</span>
                                            <small class="text-muted"><?php echo $batch['unique_items']; ?> unique</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($batch['submitted_by_name'] ?? 'System'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($batch['submitted_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="batch-status status-<?php echo $batch['status']; ?>">
                                            <?php echo ucfirst($batch['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="batch_view.php?id=<?php echo urlencode($batch['batch_id']); ?>"
                                                class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-secondary"
                                                onclick="copyBatchId('<?php echo $batch['batch_id']; ?>')"
                                                title="Copy ID">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <a href="batch_export.php?id=<?php echo urlencode($batch['batch_id']); ?>"
                                                class="btn btn-outline-success" title="Export">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing <?php echo count($batches); ?> batches
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">2</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copyBatchId(batchId) {
        navigator.clipboard.writeText(batchId).then(() => {
            showToast('success', 'Batch ID copied to clipboard!');
        }).catch(err => {
            console.error('Copy failed:', err);
            showToast('error', 'Failed to copy');
        });
    }

    function showToast(type, message) {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    function clearFilters() {
        document.getElementById('dateFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('searchFilter').value = '';
        showToast('info', 'Filters cleared');
    }
</script>

<?php require_once 'views/partials/footer.php'; ?>