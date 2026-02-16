<?php
// batch_history.php - CONNECTED TO REAL DATA
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$current_page = basename(__FILE__);
require_once 'includes/bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Database connection - USE YOUR ACTUAL DATABASE
$conn = new mysqli('localhost', 'root', '', 'ability_db');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// REMOVE THESE TABLE CREATION AND SAMPLE DATA LINES:
// --------------------------------------------------
// Delete or comment out these sections:
// 1. Create/update table if not exists
// 2. Check and add columns if they don't exist
// 3. Create users table if not exists
// 4. Insert default users if not exists
// 5. Add sample data if table is empty
// --------------------------------------------------

// INSTEAD: Get batches from your REAL data source
// Assuming your real data is in a table called 'scans' or similar
// Adjust table and column names based on your actual database structure

// batch_history.php - REPLACE the query section with this:

// Get batches from batch_scans table (YOUR ACTUAL DATA TABLE)
$result = $conn->query("
    SELECT 
        b.*,
        b.id as batch_id,
        b.batch_id as batch_identifier,
        b.batch_name,
        b.total_items,
        b.unique_items,
        b.submitted_by,
        b.requested_by,
        b.technician_name,
        b.approved_by,
        b.approved_at,
        b.submitted_at,
        b.status,
        b.approval_status,
        b.action_applied,
        b.location_applied,
        b.notes,
        u1.username as submitted_by_name,
        u1.full_name as submitted_by_fullname,
        u1.role as submitted_by_role,
        u2.username as requested_by_name,
        u2.full_name as requested_by_fullname,
        u2.role as requested_by_role,
        u3.username as approved_by_name,
        u3.full_name as approved_by_fullname,
        u3.role as approved_by_role,
        DATE_FORMAT(b.submitted_at, '%M %d, %Y %h:%i %p') as formatted_submitted_at,
        DATE_FORMAT(b.approved_at, '%M %d, %Y %h:%i %p') as formatted_approved_at
    FROM batch_scans b
    LEFT JOIN users u1 ON b.submitted_by = u1.id
    LEFT JOIN users u2 ON b.requested_by = u2.id
    LEFT JOIN users u3 ON b.approved_by = u3.id
    ORDER BY b.submitted_at DESC
");

$batches = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $batches[] = [
            'id' => $row['id'],
            'batch_id' => $row['batch_identifier'], // This is the BATCH-xxx value
            'batch_name' => $row['batch_name'] ?? 'Batch',
            'total_items' => $row['total_items'] ?? 0,
            'unique_items' => $row['unique_items'] ?? 0,
            'submitted_by' => $row['submitted_by'],
            'submitted_by_name' => $row['submitted_by_name'] ?? 'System',
            'submitted_by_fullname' => $row['submitted_by_fullname'] ?? 'System User',
            'submitted_by_role' => $row['submitted_by_role'] ?? 'user',
            'requested_by' => $row['requested_by'],
            'requested_by_name' => $row['requested_by_name'],
            'requested_by_fullname' => $row['requested_by_fullname'] ?? $row['technician_name'],
            'requested_by_role' => $row['requested_by_role'] ?? 'technician',
            'approved_by' => $row['approved_by'],
            'approved_by_name' => $row['approved_by_name'],
            'approved_by_fullname' => $row['approved_by_fullname'],
            'approved_by_role' => $row['approved_by_role'],
            'approved_at' => $row['approved_at'],
            'approved_at_formatted' => $row['formatted_approved_at'],
            'submitted_at' => $row['submitted_at'],
            'formatted_submitted_at' => $row['formatted_submitted_at'],
            'status' => $row['status'] ?? 'completed',
            'approval_status' => $row['approval_status'] ?? 'pending',
            'action_applied' => $row['action_applied'] ?? 'release',
            'location_applied' => $row['location_applied'] ?? 'KCC',
            'notes' => $row['notes'] ?? ''
        ];
    }
} else {
    // No batches found - this is normal for a new system
    error_log("No batches found in batch_scans table");
}

$conn->close();

$pageTitle = "Batch History - aBility";
$breadcrumbItems = ['Dashboard' => 'dashboard.php', 'Batch History' => ''];

require_once 'views/partials/header.php';
?>

<style>
    /* Table Styling */
    #batchesTable {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    #batchesTable thead th {
        background-color: #4361ee;
        color: white;
        border: none;
        font-weight: 600;
        padding: 15px 10px;
        position: sticky;
        top: 0;
    }

    #batchesTable tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }

    #batchesTable tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }

    /* Status Badges */
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }

    .status-completed {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .status-processing {
        background-color: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .status-pending,
    .status-cancelled {
        background-color: rgba(108, 117, 125, 0.15);
        color: #6c757d;
    }

    /* Approval status badges */
    .approval-pending {
        background-color: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .approval-approved {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .approval-rejected {
        background-color: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .approval-not_required {
        background-color: rgba(108, 117, 125, 0.15);
        color: #6c757d;
    }

    /* Batch ID Styling */
    .batch-id {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        background: #f8f9fa;
        padding: 3px 8px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }

    /* Row Number Styling */
    .row-number {
        font-weight: 600;
        color: #6c757d;
        text-align: center;
        min-width: 40px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Card Styling */
    .card {
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        border: none;
        padding: 20px 25px;
    }

    .user-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 10px;
    }

    .role-technician {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
        border: 1px solid rgba(0, 123, 255, 0.2);
    }

    .role-stock_controller {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }

    .role-admin {
        background-color: rgba(111, 66, 193, 0.1);
        color: #6f42c1;
        border: 1px solid rgba(111, 66, 193, 0.2);
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-history me-2"></i>Batch History
            </h1>
            <p class="text-muted mb-0">View and manage all scanning batches with approval workflow</p>
        </div>
        <div>
            <a href="scan_2.php" class="btn btn-primary">
                <i class="fas fa-qrcode me-1"></i> New Scan
            </a>
            <?php if (isLoggedIn()): ?>
                <button class="btn btn-success" id="bulkApproveBtn">
                    <i class="fas fa-check-double me-1"></i> Bulk Approve
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Batches
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($batches); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Approval
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $pending = array_filter($batches, function ($batch) {
                                    return $batch['approval_status'] === 'pending';
                                });
                                echo count($pending);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Approved
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $approved = array_filter($batches, function ($batch) {
                                    return $batch['approval_status'] === 'approved';
                                });
                                echo count($approved);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Awaiting Action
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $processing = array_filter($batches, function ($batch) {
                                    return $batch['status'] === 'processing' || $batch['status'] === 'pending';
                                });
                                echo count($processing);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table me-2"></i>All Batches with Approval Status
            </h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshTable">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i> Filter by Status
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item quick-filter" href="#" data-approval="all">All Batches</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item quick-filter" href="#" data-approval="pending">Pending Approval</a></li>
                        <li><a class="dropdown-item quick-filter" href="#" data-approval="approved">Approved</a></li>
                        <li><a class="dropdown-item quick-filter" href="#" data-approval="rejected">Rejected</a></li>
                        <li><a class="dropdown-item quick-filter" href="#" data-approval="not_required">No Approval Needed</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="batchesTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Batch ID</th>
                            <th>Batch Name</th>
                            <th>Items</th>
                            <th>Action</th>
                            <th>Location</th>
                            <th>Requested By</th>
                            <th>Submitted By</th>
                            <th>Approved By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($batches as $batch): ?>
                            <tr data-batch-id="<?php echo $batch['id']; ?>"
                                data-approval-status="<?php echo $batch['approval_status']; ?>">
                                <td class="row-number"><?php echo $counter++; ?></td>
                                <td>
                                    <span class="batch-id"><?php echo htmlspecialchars($batch['batch_id']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($batch['batch_name']); ?></strong>
                                    <?php if ($batch['notes']): ?>
                                        <small class="d-block text-muted mt-1">
                                            <i class="fas fa-sticky-note me-1"></i>
                                            <?php echo htmlspecialchars(substr($batch['notes'], 0, 50)); ?>
                                            <?php if (strlen($batch['notes']) > 50): ?>...<?php endif; ?>
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
                                    <?php if ($batch['action_applied']): ?>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst(str_replace('_', ' ', $batch['action_applied'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $batch['location_applied'] ? htmlspecialchars($batch['location_applied']) : '<span class="text-muted">-</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($batch['requested_by_name']): ?>
                                        <div class="d-flex flex-column">
                                            <span><?php echo htmlspecialchars($batch['requested_by_fullname'] ?? $batch['requested_by_name']); ?></span>
                                            <small>
                                                <span class="user-badge role-<?php echo $batch['requested_by_role'] ?? 'user'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $batch['requested_by_role'] ?? 'Technician')); ?>
                                                </span>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($batch['submitted_by_name']): ?>
                                        <div class="d-flex flex-column">
                                            <span><?php echo htmlspecialchars($batch['submitted_by_fullname'] ?? $batch['submitted_by_name']); ?></span>
                                            <small>
                                                <span class="user-badge role-<?php echo $batch['submitted_by_role'] ?? 'user'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $batch['submitted_by_role'] ?? 'User')); ?>
                                                </span>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($batch['approved_by_name']): ?>
                                        <div class="d-flex flex-column">
                                            <span><?php echo htmlspecialchars($batch['approved_by_fullname'] ?? $batch['approved_by_name']); ?></span>
                                            <small>
                                                <span class="user-badge role-<?php echo $batch['approved_by_role'] ?? 'user'; ?>">
                                                    Stock Controller
                                                </span>
                                            </small>
                                            <?php if ($batch['approved_at_formatted']): ?>
                                                <small class="text-muted">
                                                    <?php echo date('M j', strtotime($batch['approved_at_formatted'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($batch['approval_status'] == 'pending'): ?>
                                        <span class="text-warning"><i class="fas fa-clock me-1"></i> Awaiting</span>
                                    <?php elseif ($batch['approval_status'] == 'not_required'): ?>
                                        <span class="text-muted">Not Required</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-order="<?php echo strtotime($batch['submitted_at']); ?>">
                                    <?php echo date('M j, Y', strtotime($batch['submitted_at'])); ?>
                                    <small class="d-block text-muted">
                                        <?php echo date('g:i A', strtotime($batch['submitted_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $batch['status']; ?>">
                                        <?php echo ucfirst($batch['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge approval-<?php echo $batch['approval_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $batch['approval_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- View Details Button - Opens Modal -->
                                        <button class="btn btn-sm btn-primary action-btn view-batch-details"
                                            data-batch-id="<?php echo $batch['id']; ?>"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- Copy ID -->
                                        <button class="btn btn-sm btn-secondary action-btn copy-batch-id"
                                            data-batch-id="<?php echo htmlspecialchars($batch['batch_id']); ?>"
                                            title="Copy ID">
                                            <i class="fas fa-copy"></i>
                                        </button>

                                        <!-- Approval Actions -->
                                        <?php if (($batch['approval_status'] == 'pending') && isLoggedIn()): ?>
                                            <button class="btn btn-sm btn-success action-btn approve-batch"
                                                data-batch-id="<?php echo $batch['id']; ?>"
                                                title="Approve Batch">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger action-btn reject-batch"
                                                data-batch-id="<?php echo $batch['id']; ?>"
                                                title="Reject Batch">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Batch Details Modal -->
<!-- Batch Details Modal -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="batchDetailsModalLabel">
                    <i class="fas fa-clipboard-list me-2"></i>Batch Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="batchDetailsContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading batch details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printBatchDetailsPDF()">
                        <i class="fas fa-print me-1"></i> Print PDF
                    </button>
                    <button type="button" class="btn btn-success" onclick="downloadAsPDF()">
                        <i class="fas fa-download me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvalModalLabel">Approve Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this batch?</p>
                <div class="mb-3">
                    <label for="approvalNotes" class="form-label">Approval Notes (Optional)</label>
                    <textarea class="form-control" id="approvalNotes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprove">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectionModalLabel">Reject Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject this batch?</p>
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label">Rejection Reason *</label>
                    <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Approval Modal -->
<div class="modal fade" id="bulkApprovalModal" tabindex="-1" aria-labelledby="bulkApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bulkApprovalModalLabel">Bulk Approval</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select batches to approve:</p>
                <div id="bulkApprovalList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Batches will be listed here -->
                </div>
                <div class="mt-3">
                    <label for="bulkApprovalNotes" class="form-label">Approval Notes (Optional)</label>
                    <textarea class="form-control" id="bulkApprovalNotes" rows="3" placeholder="Add notes for all selected batches..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="selectAllBatches">Select All</button>
                <button type="button" class="btn btn-success" id="confirmBulkApprove">Approve Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables CSS & JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

<script>
    // Debug: Log all AJAX requests and responses
    $(document).ajaxSend(function(event, jqxhr, settings) {
        console.log('AJAX Request:', settings.url, settings.data);
    });

    $(document).ajaxComplete(function(event, xhr, settings) {
        console.log('AJAX Complete:', settings.url, xhr.status, xhr.responseText);
    });

    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#batchesTable').DataTable({
            "pageLength": 5,
            "order": [
                [9, 'asc']
            ], // Sort by date
            "columnDefs": [{
                    "targets": 0,
                    "orderable": false
                },
                {
                    "targets": 12,
                    "orderable": false
                }
            ],
            "dom": '<"top"f>rt<"bottom"lip><"clear">'
        });

        // Refresh button
        $('#refreshTable').on('click', function() {
            table.draw();
            showToast('success', 'Table refreshed successfully!');
        });

        // Copy Batch ID functionality
        $(document).on('click', '.copy-batch-id', function() {
            var batchId = $(this).data('batch-id');
            copyToClipboard(batchId, 'Batch ID copied to clipboard!');
        });

        // Fix for print button in modal footer
        $(document).on('click', '#batchDetailsModal .btn-primary', function(e) {
            // Check if this is the print button
            if ($(this).find('.fa-print').length > 0 || $(this).text().includes('Print')) {
                e.preventDefault();
                printBatchDetailsPDF();
                return false;
            }
        });

        // Also fix any existing onclick handlers
        $(document).ready(function() {
            // Replace any incorrect onclick handlers
            $('[onclick*="printBatchDetails()"]').each(function() {
                var oldOnClick = $(this).attr('onclick');
                if (oldOnClick === 'printBatchDetails()') {
                    $(this).attr('onclick', 'printBatchDetailsPDF()');
                }
            });
        });

        // Load saved logo from localStorage on page load
        const savedLogo = localStorage.getItem('companyLogo');
        if (savedLogo) {
            // This will be applied when the modal opens
            $(document).on('shown.bs.modal', '#batchDetailsModal', function() {
                setTimeout(() => {
                    const logoImg = document.getElementById('companyLogo');
                    if (logoImg) {
                        logoImg.src = savedLogo;
                        logoImg.style.display = 'block';
                    }
                }, 100);
            });
        }

        // View Batch Details in Modal
        $(document).on('click', '.view-batch-details', function() {
            var batchId = $(this).data('batch-id');

            console.log('Loading batch details for:', batchId); // Debug log

            // Show modal with loading state
            $('#batchDetailsContent').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading batch details...</p>
                </div>
            `);

            $('#batchDetailsModal').modal('show');

            // Load batch details via AJAX
            $.ajax({
                url: 'ajax/get_batch_details.php',
                method: 'GET',
                data: {
                    batch_id: batchId
                },
                dataType: 'json', // Explicitly expect JSON
                success: function(response) {
                    console.log('AJAX Response:', response); // Debug log

                    if (response.error) {
                        $('#batchDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${response.error}
                            </div>
                        `);
                    } else {
                        displayBatchDetails(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText); // Debug log

                    // Try to parse the response anyway
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            $('#batchDetailsContent').html(`
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${response.error}
                                </div>
                            `);
                        } else {
                            displayBatchDetails(response);
                        }
                    } catch (e) {
                        $('#batchDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Failed to load batch details. Server response: ${xhr.responseText.substring(0, 100)}
                            </div>
                        `);
                    }
                }
            });
        });

        // Add this function to debug technician state
        function debugTechnicianState() {
            console.log('=== DEBUG TECHNICIAN STATE ===');
            console.log('authenticatedTechnician:', authenticatedTechnician);
            console.log('isTechnicianAuthenticated:', isTechnicianAuthenticated);
            console.log('isSubmitting:', isSubmitting);
            console.log('sessionStorage technician:', sessionStorage.getItem('authenticatedTechnician'));

            // Check what would be in collectBatchData
            try {
                const testData = collectBatchData();
                console.log('collectBatchData() technician:', testData.technician);
            } catch (e) {
                console.log('collectBatchData() error:', e.message);
            }
        }

        // Expose to window
        window.debugTechnicianState = debugTechnicianState;

        // Add a test button to your HTML temporarily:
        // <button class="btn btn-sm btn-info" onclick="debugTechnicianState()">
        //     <i class="fas fa-bug"></i> Debug Tech
        // </button>

        // Function to display batch details in PDF format
        function displayBatchDetails(batch) {
            // Format date and time from timestamp
            const formatDate = (timestamp) => {
                if (!timestamp) return 'N/A';
                const date = new Date(timestamp);
                return date.toLocaleDateString('en-GB');
            };

            const formatTime = (timestamp) => {
                if (!timestamp) return 'N/A';
                const date = new Date(timestamp);
                return date.toLocaleTimeString('en-GB', {
                    hour12: false
                });
            };

            // Get dates
            const submittedDate = formatDate(batch.submitted_at || new Date());
            const submittedTime = formatTime(batch.submitted_at || new Date());
            const approvedDate = batch.approved_at ? formatDate(batch.approved_at) : '';
            const approvedTime = batch.approved_at ? formatTime(batch.approved_at) : '';

            // Determine authentication status
            const authStatus = '✓ Verified';

            // Determine submission status
            const submissionStatus = batch.approval_status === 'approved' ? 'Approved' :
                batch.approval_status === 'pending' ? 'Pending Approval' :
                batch.approval_status === 'rejected' ? 'Rejected' : 'Not Required';

            // Format job sheet number
            const jobSheet = batch.job_sheet_number || 'N/A';

            const html = `
                <div style="padding: 20px; font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; position: relative; margin-top: 10px;">
                    <!-- Page Number - Top Right -->
                    <div style="text-align: right; margin-bottom: 10px; color: #666; font-size: 0.9em; position: absolute; top: 10px; right: 20px;">
                        1
                    </div>
                    
                    <!-- Logo on Top Left -->
                    <div style="position: absolute; top: 10px; left: 20px; z-index: 10;">
                        <img id="companyLogo" src="" alt="Company Logo" 
                             style="max-height: 70px; max-width: 150px; display: none;" 
                             onerror="this.style.display='none'">
                    </div>
                    
                    <!-- Main Title - Centered with logo offset -->
                    <div style="text-align: center; margin-top: 50px; margin-bottom: 15px;">
                        <h1 style="color: #333; border-bottom: 2px solid #4361ee; padding-bottom: 8px; margin: 0; display: inline-block; font-size: 22px;">
                            📦 Batch Items Summary - Equipment Release
                        </h1>
                    </div>
                    
                    <!-- Authentication Details -->
                    <div style="background: #fff8e1; padding: 12px; border-left: 4px solid #ffc107; margin-bottom: 15px;">
                        <h3 style="color: #856404; margin-top: 0; margin-bottom: 10px; font-size: 1em;">
                            🔐 Authentication Details
                        </h3>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9em;">
                            <div style="flex: 1;">
                                <strong>Technician Requesting:</strong> ${batch.requested_by_fullname || batch.requested_by_name || 'N/A'}<br>
                                <strong>Authentication Time:</strong> ${submittedDate},  ${submittedTime}<br>
                                <strong>Authentication Status:</strong> <span style="color: green;">${authStatus}</span>
                            </div>
                            <div style="flex: 1; text-align: right;">
                                <strong>Submitted By (Stock Controller):</strong> ${batch.submitted_by_fullname || batch.submitted_by_name || 'N/A'}<br>
                                <strong>Submission Time:</strong> ${submittedDate}, ${submittedTime}<br>
                                <strong>Submission Status:</strong> ${submissionStatus}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Header Info - More Compact -->
                    <div style="display: flex; justify-content: space-between; background: #f8f9fa; padding: 10px; margin-bottom: 10px; border-radius: 5px; font-size: 0.9em;">
                        <div>
                            <strong>Date:</strong> ${submittedDate}<br>
                            <strong>Time:</strong> ${submittedTime}<br>
                            <strong>Total Items:</strong> ${batch.total_items || '0'}
                        </div>
                        <div style="text-align: right;">
                            <strong>Job Sheet:</strong> JS-${jobSheet}<br>
                            <strong>Event:</strong> ${batch.batch_name || 'N/A'}<br>
                            <strong>Reference:</strong> ${batch.batch_id || 'N/A'}
                        </div>
                    </div>
                    
                    <!-- Job Details Box - More Compact -->
                    <div style="background: #e7f3ff; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #4361ee; font-size: 0.9em;">
                        <strong>📋 Job Details:</strong><br>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px;">
                            <div>
                                <strong>Stock Location:</strong><br>
                                ${batch.location_applied || 'N/A'}
                            </div>
                            <div>
                                <strong>Project Manager:</strong><br>
                                ${batch.project_manager || batch.requested_by_fullname || 'N/A'}
                            </div>
                            <div>
                                <strong>Vehicle:</strong><br>
                                ${batch.vehicle_number || 'N/A'}
                            </div>
                            <div>
                                <strong>Driver:</strong><br>
                                ${batch.driver_name || 'N/A'}
                            </div>
                            <div>
                                <strong>Destination:</strong><br>
                                ${batch.destination || batch.location_applied || 'N/A'}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Table - More Compact -->
                    <div style="margin-bottom: 20px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; font-size: 0.85em;">
                            <thead>
                                <tr style="background: #4361ee; color: white;">
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">#</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Item Name</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Serial Number</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Category</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Status</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Destination</th>
                                    <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Qty</th>
                                </tr>
                            </thead>
                            <tbody id="batchItemsTable">
                                <tr>
                                    <td colspan="7" style="padding: 15px; text-align: center; color: #666;">
                                        Loading items...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Signature Section - More Compact -->
                    <div style="margin-top: 25px;">
                        <div style="float: left; width: 45%;">
                            <div style="border-top: 2px solid #333; padding-top: 8px; margin-bottom: 40px;">
                                <strong>Requested By (Technician):</strong><br>
                                <div style="border-bottom: 2px solid #333; width: 80%; height: 30px; margin: 5px 0 8px 0;"></div>
                                <div style="color: #666; font-size: 0.85em;">
                                    ${batch.requested_by_fullname || batch.requested_by_name || 'N/A'}<br>
                                    Technician Signature
                                </div>
                            </div>
                        </div>
                        
                        <div style="float: right; width: 45%;">
                            <div style="border-top: 2px solid #333; padding-top: 8px; margin-bottom: 40px;">
                                <strong>Approved By (Stock Controller):</strong><br>
                                <div style="border-bottom: 2px solid #333; width: 80%; height: 30px; margin: 5px 0 8px 0;"></div>
                                <div style="color: #666; font-size: 0.85em;">
                                    ${batch.approved_by_fullname || batch.approved_by_name || 'Pending'}<br>
                                    Stock Controller Signature<br>
                                    ${approvedDate ? `Approved on: ${approvedDate}` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div style="clear: both;"></div>
                        
                        <!-- Notes Section - More Compact -->
                        <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em;">
                            <strong>Important Notes:</strong><br>
                            ${batch.notes ? batch.notes.replace(/\n/g, '<br>') : 'No additional notes provided.'}
                        </div>
                    </div>
                    
                    <!-- Footer - More Compact -->
                    <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; text-align: center; font-size: 0.75em; color: #666;">
                        <div>Document ID: ${batch.batch_id}</div>
                        <div>Generated on: ${new Date().toLocaleString()}</div>
                        <div class="no-print" style="margin-top: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="copyBatchToClipboard()">
                                <i class="fas fa-copy me-1"></i> Copy Summary
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="uploadLogo()">
                                <i class="fas fa-upload me-1"></i> Upload Logo
                            </button>
                            <input type="file" id="logoUpload" accept="image/*" style="display: none;" 
                                   onchange="handleLogoUpload(this)">
                        </div>
                    </div>
                </div>
                
                <!-- Print Styles - Optimized for single page -->
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        body { 
                            margin: 0; 
                            padding: 10px; 
                            background: white;
                            font-size: 10pt !important;
                        }
                        div { page-break-inside: avoid; }
                        h1 { 
                            font-size: 18pt !important; 
                            margin-top: 0 !important;
                            padding-bottom: 6px !important;
                        }
                        table { 
                            font-size: 8pt !important; 
                            margin: 10px 0 !important;
                        }
                        th, td {
                            padding: 6px !important;
                        }
                        .signature-box { margin-top: 30px !important; }
                        .logo-section { margin-bottom: 5px !important; }
                        @page {
                            margin: 10mm !important;
                            size: A4 portrait !important;
                        }
                    }
                </style>
            `;

            $('#batchDetailsContent').html(html);

            // Load saved logo if exists
            setTimeout(() => {
                const savedLogo = localStorage.getItem('companyLogo');
                if (savedLogo) {
                    const logoImg = document.getElementById('companyLogo');
                    if (logoImg) {
                        logoImg.src = savedLogo;
                        logoImg.style.display = 'block';
                    }
                }
            }, 100);

            // Load items after HTML is rendered
            loadBatchItems(batch.batch_id);
        }

        // Function to load batch items
        function loadBatchItems(batchId) {
            $.ajax({
                url: 'ajax/get_batch_items.php',
                method: 'GET',
                data: {
                    batch_id: batchId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Items Response:', response); // Debug log

                    try {
                        const items = Array.isArray(response) ? response : [];
                        let itemsHtml = '';

                        if (items.length === 0) {
                            itemsHtml = `
                                <tr>
                                    <td colspan="7" style="padding: 15px; text-align: center; color: #666; font-size: 0.9em;">
                                        No items found in this batch
                                    </td>
                                </tr>
                            `;
                        } else {
                            items.forEach((item, index) => {
                                const statusColor = item.status === 'available' ? '#28a745' :
                                    item.status === 'in_use' ? '#ffc107' :
                                    item.status === 'maintenance' ? '#dc3545' : '#6c757d';

                                itemsHtml += `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">${index + 1}</td>
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">${item.item_name || 'N/A'}</td>
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">
                                            <code style="font-size: 0.8em;">${item.serial_number || 'N/A'}</code>
                                        </td>
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">${item.category || 'N/A'}</td>
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">
                                            <span style="background: ${statusColor}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.75em;">
                                                ${item.status || 'unknown'}
                                            </span>
                                        </td>
                                        <td style="padding: 6px; border: 1px solid #ddd; font-size: 0.85em;">${item.destination || 'N/A'}</td>
                                        <td style="padding: 6px; border: 1px solid #ddd; text-align: center; font-size: 0.85em;">${item.quantity || '1'}</td>
                                    </tr>
                                `;
                            });
                        }

                        $('#batchItemsTable').html(itemsHtml);
                    } catch (e) {
                        console.error('Items parsing error:', e);
                        $('#batchItemsTable').html(`
                            <tr>
                                <td colspan="7" style="padding: 15px; text-align: center; color: #dc3545; font-size: 0.9em;">
                                    Error loading items: ${e.message}
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Items AJAX Error:', xhr.responseText);
                    $('#batchItemsTable').html(`
                        <tr>
                            <td colspan="7" style="padding: 15px; text-align: center; color: #dc3545; font-size: 0.9em;">
                                Failed to load items. Please try again.
                            </td>
                        </tr>
                    `);
                }
            });
        }

        // Print function for PDF-style layout - Optimized for single page
        window.printBatchDetailsPDF = function() {
            const printContent = $('#batchDetailsContent').html();

            try {
                // Remove the no-print elements
                const cleanContent = printContent
                    .replace(/<div class="no-print"[^>]*>[\s\S]*?<\/div>/g, '')
                    .replace(/<button[^>]*onclick="uploadLogo[^>]*>[\s\S]*?<\/button>/g, '')
                    .replace(/<input[^>]*id="logoUpload"[^>]*>/g, '')
                    .replace(/<button[^>]*onclick="copyBatchToClipboard[^>]*>[\s\S]*?<\/button>/g, '');

                // Try to open print window
                const printWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');

                if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
                    // Popup was blocked, fall back to iframe method
                    printUsingIframe(cleanContent);
                } else {
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Batch Summary - Equipment Release</title>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    margin: 10px; 
                                    padding: 0; 
                                    color: #333;
                                    font-size: 10pt;
                                    background: white;
                                    max-width: 800px;
                                    margin: 0 auto;
                                }
                                @media print {
                                    @page {
                                        margin: 10mm !important;
                                        size: A4 portrait !important;
                                    }
                                    body { 
                                        margin: 0 !important; 
                                        padding: 10px !important;
                                        background: white !important;
                                        font-size: 9pt !important;
                                    }
                                    .no-print { display: none !important; }
                                    div { page-break-inside: avoid !important; }
                                    table { 
                                        font-size: 8pt !important; 
                                        margin: 8px 0 !important;
                                    }
                                    th, td {
                                        padding: 5px !important;
                                    }
                                    h1 { 
                                        font-size: 16pt !important; 
                                        margin-top: 5px !important;
                                        margin-bottom: 10px !important;
                                        padding-bottom: 5px !important;
                                    }
                                }
                                h1 { 
                                    text-align: center; 
                                    color: #333; 
                                    border-bottom: 2px solid #4361ee; 
                                    padding-bottom: 8px;
                                    font-size: 18pt;
                                    margin-top: 50px;
                                    margin-bottom: 15px;
                                }
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin: 10px 0;
                                    font-size: 9pt;
                                }
                                th, td {
                                    border: 1px solid #333;
                                    padding: 6px;
                                    text-align: left;
                                }
                                th {
                                    background-color: #f0f0f0;
                                    font-weight: bold;
                                }
                                .page-number {
                                    text-align: right;
                                    font-size: 9pt;
                                    color: #666;
                                    position: absolute;
                                    top: 10px;
                                    right: 20px;
                                }
                                .company-logo {
                                    max-height: 70px;
                                    max-width: 150px;
                                    position: absolute;
                                    top: 10px;
                                    left: 20px;
                                }
                                .compact-section {
                                    margin-bottom: 8px !important;
                                    padding: 8px !important;
                                }
                            </style>
                        </head>
                        <body style="margin-top: 10px;">
                            <!-- Add logo if available -->
                            ${document.getElementById('companyLogo') && document.getElementById('companyLogo').style.display !== 'none' ? 
                                `<img src="${document.getElementById('companyLogo').src}" alt="Company Logo" class="company-logo">` : ''}
                            
                            <!-- Page Number -->
                            <div class="page-number">1</div>
                            
                            ${cleanContent}
                            
                            <div style="text-align: center; margin-top: 15px; font-size: 8pt; color: #666; border-top: 1px solid #eee; padding-top: 8px;">
                                Generated on: ${new Date().toLocaleString()}
                            </div>
                        </body>
                        </html>
                    `);

                    printWindow.document.close();

                    // Focus and print after a short delay
                    setTimeout(() => {
                        printWindow.focus();
                        printWindow.print();

                        // Close window after printing (optional)
                        setTimeout(() => {
                            if (!printWindow.closed) {
                                printWindow.close();
                            }
                        }, 1000);
                    }, 500);
                }
            } catch (error) {
                console.error('Print error:', error);
                alert('Unable to open print window. Please allow popups for this site.');

                // Fallback: Use iframe method
                printUsingIframe(printContent);
            }
        };

        // Alternative print method using iframe
        function printUsingIframe(content) {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = 'none';
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);

            const iframeDoc = iframe.contentWindow || iframe.contentDocument.document || iframe.contentDocument;
            iframeDoc.document.open();
            iframeDoc.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Batch Summary - Equipment Release</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 10px; 
                            padding: 0; 
                            color: #333;
                            font-size: 10pt;
                        }
                        @media print {
                            @page {
                                margin: 10mm;
                            }
                            body { 
                                margin: 0; 
                                padding: 10px;
                                font-size: 9pt;
                            }
                            h1 { font-size: 16pt; }
                            table { font-size: 8pt; }
                        }
                        h1 { 
                            text-align: center; 
                            color: #333; 
                            border-bottom: 2px solid #4361ee; 
                            padding-bottom: 8px;
                            margin-top: 50px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 10px 0;
                        }
                        th, td {
                            border: 1px solid #333;
                            padding: 6px;
                            text-align: left;
                        }
                    </style>
                </head>
                <body>${content.replace(/<div class="no-print"[^>]*>[\s\S]*?<\/div>/g, '')}</body>
                </html>
            `);
            iframeDoc.document.close();

            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }, 500);
        }

        // Logo upload functions
        window.uploadLogo = function() {
            $('#logoUpload').click();
        };

        window.handleLogoUpload = function(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const logoImg = document.getElementById('companyLogo');
                    logoImg.src = e.target.result;
                    logoImg.style.display = 'block';

                    // Save to localStorage for future use
                    localStorage.setItem('companyLogo', e.target.result);

                    showToast('success', 'Logo uploaded successfully!');
                };

                reader.readAsDataURL(input.files[0]);
            }
        };

        // Download as PDF function - Optimized for single page
        window.downloadAsPDF = function() {
            // Check if html2pdf is available
            if (typeof html2pdf !== 'undefined') {
                const element = document.getElementById('batchDetailsContent');
                const opt = {
                    margin: 10, // 10mm margin
                    filename: `batch-summary-${new Date().getTime()}.pdf`,
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        width: 800, // Fixed width
                        height: 1120, // A4 height in pixels at 96 DPI
                        windowWidth: 800
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait',
                        compress: true,
                        hotfixes: ['px_scaling']
                    },
                    pagebreak: {
                        mode: ['avoid-all', 'css', 'legacy']
                    }
                };

                html2pdf().set(opt).from(element).save()
                    .then(() => {
                        showToast('success', 'PDF downloaded successfully!');
                    })
                    .catch((error) => {
                        console.error('PDF download error:', error);
                        showToast('error', 'Failed to download PDF. Please try printing instead.');
                    });
            } else {
                // Fallback to print if html2pdf not available
                showToast('info', 'PDF download requires html2pdf library. Falling back to print.');
                printBatchDetailsPDF();
            }
        };

        // Copy batch summary to clipboard
        window.copyBatchToClipboard = function() {
            const content = document.getElementById('batchDetailsContent').innerText;
            copyToClipboard(content, 'Batch summary copied to clipboard!');
        };

        // Individual batch approval
        $(document).on('click', '.approve-batch', function() {
            var batchId = $(this).data('batch-id');
            $('#approvalModal').data('batch-id', batchId).modal('show');
        });

        $('#confirmApprove').on('click', function() {
            var batchId = $('#approvalModal').data('batch-id');
            var notes = $('#approvalNotes').val();

            $.ajax({
                url: 'ajax/approve_batch.php',
                method: 'POST',
                data: {
                    batch_id: batchId,
                    notes: notes,
                    action: 'approve'
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#approvalModal').modal('hide');
                        showToast('success', result.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', result.message);
                    }
                },
                error: function() {
                    showToast('error', 'Failed to approve batch. Please try again.');
                }
            });
        });

        // Individual batch rejection
        $(document).on('click', '.reject-batch', function() {
            var batchId = $(this).data('batch-id');
            $('#rejectionModal').data('batch-id', batchId).modal('show');
        });

        $('#confirmReject').on('click', function() {
            var batchId = $('#rejectionModal').data('batch-id');
            var reason = $('#rejectionReason').val();

            if (!reason.trim()) {
                showToast('warning', 'Please provide a rejection reason.');
                return;
            }

            $.ajax({
                url: 'ajax/approve_batch.php',
                method: 'POST',
                data: {
                    batch_id: batchId,
                    notes: reason,
                    action: 'reject'
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#rejectionModal').modal('hide');
                        showToast('success', result.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', result.message);
                    }
                },
                error: function() {
                    showToast('error', 'Failed to reject batch. Please try again.');
                }
            });
        });

        // Bulk approval
        $('#bulkApproveBtn').on('click', function() {
            // Get all pending batches
            var pendingBatches = table.rows().nodes().to$().filter(function() {
                return $(this).data('approval-status') === 'pending';
            });

            if (pendingBatches.length === 0) {
                showToast('info', 'No batches pending approval.');
                return;
            }

            var html = '';
            pendingBatches.each(function() {
                var batchId = $(this).data('batch-id');
                var batchName = $(this).find('td:nth-child(3)').text().trim();
                var batchCode = $(this).find('td:nth-child(2)').text().trim();
                html += `
                    <div class="form-check mb-2">
                        <input class="form-check-input batch-checkbox" type="checkbox" value="${batchId}" id="batch${batchId}">
                        <label class="form-check-label" for="batch${batchId}">
                            <strong>${batchName}</strong><br>
                            <small class="text-muted">${batchCode}</small>
                        </label>
                    </div>
                `;
            });

            $('#bulkApprovalList').html(html);
            $('#bulkApprovalModal').modal('show');
        });

        $('#selectAllBatches').on('click', function() {
            $('.batch-checkbox').prop('checked', true);
        });

        $('#confirmBulkApprove').on('click', function() {
            var selectedBatches = [];
            $('.batch-checkbox:checked').each(function() {
                selectedBatches.push($(this).val());
            });

            if (selectedBatches.length === 0) {
                showToast('warning', 'Please select at least one batch.');
                return;
            }

            var notes = $('#bulkApprovalNotes').val();

            $.ajax({
                url: 'ajax/bulk_approve.php',
                method: 'POST',
                data: {
                    batch_ids: selectedBatches,
                    notes: notes
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#bulkApprovalModal').modal('hide');
                        showToast('success', result.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', result.message);
                    }
                },
                error: function() {
                    showToast('error', 'Failed to approve batches. Please try again.');
                }
            });
        });

        // Quick filter by approval status
        $(document).on('click', '.quick-filter', function(e) {
            e.preventDefault();
            var status = $(this).data('approval');

            if (status === 'all') {
                table.column(11).search('').draw();
            } else {
                table.column(11).search(status).draw();
            }
        });

        // Utility functions
        function copyToClipboard(text, successMessage) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('success', successMessage);
                }).catch(function(err) {
                    fallbackCopyText(text, successMessage);
                });
            } else {
                fallbackCopyText(text, successMessage);
            }
        }

        function fallbackCopyText(text, successMessage) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showToast('success', successMessage);
                } else {
                    showToast('error', 'Failed to copy to clipboard');
                }
            } catch (err) {
                showToast('error', 'Failed to copy: ' + err);
            }
            document.body.removeChild(textArea);
        }

        function showToast(type, message) {
            // Remove existing toasts
            $('.toast').remove();

            var toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; bottom: 20px; right: 20px; z-index: 1055;">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            var toast = $(toastHtml);
            $('body').append(toast);

            var bsToast = new bootstrap.Toast(toast[0], {
                delay: 3000
            });
            bsToast.show();

            toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<?php
if (file_exists('views/partials/footer.php')) {
    require_once 'views/partials/footer.php';
}
?>