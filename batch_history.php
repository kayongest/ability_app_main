<?php
// batch_history.php - ENHANCED VERSION WITH ROW NUMBERING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$current_page = basename(__FILE__);
require_once 'includes/bootstrap.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ability_db');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS batch_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id VARCHAR(100) UNIQUE NOT NULL,
    batch_name VARCHAR(255) NOT NULL,
    total_items INT DEFAULT 0,
    unique_items INT DEFAULT 0,
    action_applied VARCHAR(50),
    location_applied VARCHAR(255),
    status VARCHAR(20) DEFAULT 'processing',
    submitted_by INT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create users table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    password VARCHAR(255),
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default admin if not exists
$conn->query("INSERT IGNORE INTO users (id, username, email, password, role) 
              VALUES (1, 'admin', 'admin@example.com', MD5('admin123'), 'admin')");

// Add sample data if table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM batch_scans");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO batch_scans (batch_id, batch_name, total_items, unique_items, action_applied, location_applied, status, submitted_by, notes) VALUES
        ('BATCH-20240201123045-abc123', 'Office Equipment Check-in', 15, 10, 'check_in', 'Main Warehouse', 'completed', 1, 'Monthly inventory check for office equipment'),
        ('BATCH-20240202144530-def456', 'IT Department Audit', 8, 8, 'audit', 'IT Department', 'completed', 1, 'Annual IT equipment audit'),
        ('BATCH-20240203110000-ghi789', 'Conference Room Setup', 5, 5, 'setup', 'Conference Room A', 'completed', 1, 'Equipment for quarterly meeting'),
        ('BATCH-20240204140000-jkl012', 'Lab Equipment Maintenance', 10, 7, 'maintenance', 'Science Lab', 'processing', 1, 'Scheduled maintenance check'),
        ('BATCH-20240205103000-mno345', 'Library Books Inventory', 25, 22, 'inventory', 'Library', 'completed', 1, 'Annual book inventory'),
        ('BATCH-20260204204958-6b5fa39e', 'Video Equipment Inventory', 12, 8, 'inventory', 'Media Room', 'completed', 1, 'Video and recording equipment check'),
        ('BATCH-20240206140000-pqr678', 'Sports Equipment Check-out', 18, 12, 'check_out', 'Gymnasium', 'completed', 1, 'Equipment for sports event'),
        ('BATCH-20240207113000-stu901', 'Cleaning Supplies Audit', 7, 5, 'audit', 'Storage Room', 'failed', 1, 'Some items missing or damaged'),
        ('BATCH-20240208150000-vwx234', 'Electronics Update', 9, 9, 'update', 'IT Office', 'completed', 1, 'Updated software and firmware'),
        ('BATCH-20240209100000-yz5678', 'Classroom Setup', 14, 11, 'setup', 'Room 101', 'processing', 1, 'Setting up for new semester')");
}

// Get batches
$result = $conn->query("
    SELECT bs.*, u.username as submitted_by_name 
    FROM batch_scans bs 
    LEFT JOIN users u ON bs.submitted_by = u.id 
    ORDER BY bs.submitted_at DESC
");

$batches = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
}

$conn->close();

$pageTitle = "Batch History - aBility";
$breadcrumbItems = ['Dashboard' => 'dashboard.php', 'Batch History' => ''];

require_once 'views/partials/header.php';
?>

<!-- Custom CSS -->
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

    .status-failed {
        background-color: rgba(220, 53, 69, 0.15);
        color: #dc3545;
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

    /* DataTables Custom Styling */
    .dataTables_wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .dataTables_length select,
    .dataTables_filter input {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 0.875rem;
    }

    .dataTables_length select:focus,
    .dataTables_filter input:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        outline: 0;
    }

    .dataTables_info {
        padding: 0.75rem 0;
        color: #6c757d;
        font-size: 0.875rem;
    }

    /* Clean Pagination */
    .dataTables_paginate {
        margin-top: 15px;
    }

    .paginate_button {
        border: 1px solid #dee2e6 !important;
        padding: 6px 12px !important;
        margin-left: 2px !important;
        margin-right: 2px !important;
        border-radius: 4px !important;
        color: #4361ee !important;
        background-color: white !important;
        transition: all 0.2s !important;
    }

    .paginate_button:hover {
        background-color: #f8f9fa !important;
        border-color: #4361ee !important;
        text-decoration: none !important;
    }

    .paginate_button.current {
        background-color: #4361ee !important;
        border-color: #4361ee !important;
        color: white !important;
    }

    .paginate_button.disabled,
    .paginate_button.disabled:hover {
        color: #6c757d !important;
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
        cursor: not-allowed !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }

        .action-btn {
            width: 100%;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 10px;
        }

        .dataTables_wrapper .dataTables_paginate {
            text-align: center !important;
        }
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-history me-2"></i>Batch History
            </h1>
            <p class="text-muted mb-0">View and manage all scanning batches</p>
        </div>
        <div>
            <a href="scan_2.php" class="btn btn-primary">
                <i class="fas fa-qrcode me-1"></i> New Scan
            </a>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalBatches">
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
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $completed = array_filter($batches, function ($batch) {
                                    return $batch['status'] === 'completed';
                                });
                                echo count($completed);
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
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Processing
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $processing = array_filter($batches, function ($batch) {
                                    return $batch['status'] === 'processing';
                                });
                                echo count($processing);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sync-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Items Scanned
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $totalItems = array_sum(array_column($batches, 'total_items'));
                                echo number_format($totalItems);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
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
                <i class="fas fa-table me-2"></i>All Batches
            </h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshTable">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="exportCSV">
                    <i class="fas fa-download me-1"></i> Export CSV
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item quick-filter" href="#" data-status="all">All Batches</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item quick-filter" href="#" data-status="completed">Completed Only</a></li>
                        <li><a class="dropdown-item quick-filter" href="#" data-status="processing">Processing Only</a></li>
                        <li><a class="dropdown-item quick-filter" href="#" data-status="failed">Failed Only</a></li>
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
                            <th>Submitted By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($batches as $batch): ?>
                            <tr>
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
                                    <?php echo htmlspecialchars($batch['submitted_by_name'] ?? 'System'); ?>
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
                                    <div class="action-buttons">
                                        <a href="batch_view.php?id=<?php echo urlencode($batch['batch_id']); ?>"
                                            class="btn btn-sm btn-primary action-btn"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-secondary action-btn copy-batch-id"
                                            data-batch-id="<?php echo htmlspecialchars($batch['batch_id']); ?>"
                                            title="Copy ID">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <?php if ($batch['notes']): ?>
                                            <button class="btn btn-sm btn-info action-btn view-notes"
                                                data-batch-id="<?php echo htmlspecialchars($batch['batch_id']); ?>"
                                                data-notes="<?php echo htmlspecialchars($batch['notes'] ?? ''); ?>"
                                                title="View Notes">
                                                <i class="fas fa-sticky-note"></i>
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

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="notesModalLabel">Batch Notes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="notesContent" class="mb-0" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#batchesTable').DataTable({
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search batches...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No matching records found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            },
            "pageLength": 10,
            "lengthMenu": [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "All"]
            ],
            "order": [
                [7, 'desc']
            ], // Default sort by Date (column index 7 after adding # column)
            "columnDefs": [{
                    "targets": 0, // Row number column
                    "orderable": false,
                    "searchable": false,
                    "className": "text-center"
                },
                {
                    "targets": 9, // Actions column (index 9 after adding # column)
                    "orderable": false,
                    "searchable": false
                }
            ],
            "drawCallback": function(settings) {
                var api = this.api();
                var pageInfo = api.page.info();

                // Update row numbers on each page
                api.rows({
                    page: 'current'
                }).every(function(rowIdx) {
                    var cell = api.cell(rowIdx, 0).node();
                    $(cell).html(pageInfo.start + rowIdx + 1);
                });

                // Update total batches count
                $('#totalBatches').text(api.rows().count());
            },
            "initComplete": function() {
                // Add custom class to pagination elements
                $('.dataTables_paginate').addClass('pagination-sm');
                $('.dataTables_length label').addClass('form-label');
                $('.dataTables_filter label').addClass('form-label');
            }
        });

        // Refresh button
        $('#refreshTable').on('click', function() {
            table.ajax.reload(null, false);
            showToast('success', 'Table refreshed successfully!');
        });

        // Export CSV button
        $('#exportCSV').on('click', function() {
            exportToCSV();
        });

        // Copy Batch ID functionality
        $(document).on('click', '.copy-batch-id', function() {
            var batchId = $(this).data('batch-id');
            copyToClipboard(batchId, 'Batch ID copied to clipboard!');
        });

        // View Notes modal
        $(document).on('click', '.view-notes', function() {
            var notes = $(this).data('notes');
            var batchId = $(this).data('batch-id');

            if (notes && notes.trim() !== '') {
                $('#notesContent').text(notes);
                $('#notesModalLabel').text('Notes for Batch: ' + batchId);
                $('#notesModal').modal('show');
            } else {
                showToast('info', 'No notes available for this batch');
            }
        });

        // Quick filter buttons
        $(document).on('click', '.quick-filter', function(e) {
            e.preventDefault();
            var status = $(this).data('status');

            if (status === 'all') {
                table.search('').columns().search('').draw();
            } else {
                table.column(8).search(status).draw(); // Status column index is 8
            }

            // Update active state
            $('.quick-filter').removeClass('active');
            $(this).addClass('active');
        });

        // Utility functions
        function copyToClipboard(text, successMessage) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('success', successMessage);
                }, function() {
                    fallbackCopyText(text, successMessage);
                });
            } else {
                fallbackCopyText(text, successMessage);
            }
        }

        function fallbackCopyText(text, successMessage) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
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

        function exportToCSV() {
            // Get table data
            var headers = [];
            $('#batchesTable thead th').each(function() {
                var text = $(this).text().trim();
                if (text !== '#' && text !== 'Actions') { // Skip row number and actions columns
                    headers.push('"' + text.replace(/"/g, '""') + '"');
                }
            });

            var csv = [headers.join(',')];

            // Get all data (not just current page)
            var data = table.rows().data();

            data.each(function(row) {
                var rowData = [];
                $(row).find('td').each(function(index) {
                    if (index !== 0 && index !== 9) { // Skip row number (index 0) and actions (index 9)
                        var text = $(this).text().trim();
                        text = text.replace(/"/g, '""');
                        rowData.push('"' + text + '"');
                    }
                });
                csv.push(rowData.join(','));
            });

            // Create download link
            var csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "batch_history_" + new Date().toISOString().slice(0, 10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showToast('success', 'CSV exported successfully!');
        }

        function showToast(type, message) {
            // Remove existing toasts
            $('.toast').remove();

            var toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; bottom: 20px; right: 20px; z-index: 1055;">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
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

        // Auto-refresh every 60 seconds (optional)
        // setInterval(function() {
        //     table.ajax.reload(null, false);
        // }, 60000);
    });
</script>

<?php
// Try to load footer
if (file_exists('views/partials/footer.php')) {
    require_once 'views/partials/footer.php';
} else {
    echo '</div></body></html>';
}
?>