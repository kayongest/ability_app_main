<?php
// views/items/index.php

// Check for success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$pageTitle = "Equipment List - aBility";
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Equipment' => 'items.php'
];

// Set this to show breadcrumb in header
$showBreadcrumb = true;

// Include header - adjust path since we're already in views/items/
require_once __DIR__ . '/../partials/header.php';  // Go up one level, then into partials
?>


<div class="container-fluid">
    <!-- Page Header -->
    <div class="export-btn-group">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-download me-2"></i>Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportToExcel()"><i class="fas fa-file-excel text-success me-2"></i>Export to Excel</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportToPDF()"><i class="fas fa-file-pdf text-danger me-2"></i>Export to PDF</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportToCSV()"><i class="fas fa-file-csv text-info me-2"></i>Export to CSV</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#" onclick="printTable()"><i class="fas fa-print me-2"></i>Print Table</a></li>
            </ul>
        </div>
        <a href="items.php?action=create" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Add New Equipment
        </a>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Controls Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="globalSearch"
                            placeholder="Search by name, serial, category, or description..."
                            onkeyup="if(event.keyCode === 13) applyFilters()">
                        <button class="btn btn-primary" type="button" onclick="applyFilters()">
                            Search
                        </button>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                        <select class="form-select" id="pageLength" onchange="changePageLength()">
                            <option value="5" selected>5 per page</option>
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                            <option value="0">All items</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="refreshTable()" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="toggleFilters()">
                            <i class="fas fa-filter"></i> Advanced Filters
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info" onclick="showQuickStats()" title="Quick Stats">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="showQRScanner()" title="Scan QR Code">
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filter Row -->
    <div class="card shadow mb-4" id="advancedFilters" style="display: none;">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-sliders-h me-2"></i>Advanced Filters
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Category</label>
                    <select class="form-control form-control-sm" id="filterCategory">
                        <option value="">All Categories</option>
                        <?php
                        // Direct database connection for categories
                        $conn = new mysqli("localhost", "root", "", "ability_db");

                        if (!$conn->connect_error) {
                            // Get categories
                            $stmt = $conn->prepare("SELECT DISTINCT category FROM items 
                                         WHERE category IS NOT NULL AND category != '' 
                                         ORDER BY category");
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['category']) . '">' .
                                    htmlspecialchars($row['category']) . '</option>';
                            }

                            $stmt->close();
                            $conn->close();
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select class="form-control form-control-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="in_use">In Use</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="reserved">Reserved</option>
                        <option value="lost">Lost</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Location</label>
                    <select class="form-control form-control-sm" id="filterLocation">
                        <option value="">All Locations</option>
                        <?php
                        // Direct database connection for locations
                        $conn2 = new mysqli("localhost", "root", "", "ability_db");

                        if (!$conn2->connect_error) {
                            // Get locations
                            $stmt = $conn2->prepare("SELECT DISTINCT stock_location FROM items 
                                         WHERE stock_location IS NOT NULL AND stock_location != '' 
                                         ORDER BY stock_location");
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['stock_location']) . '">' .
                                    htmlspecialchars($row['stock_location']) . '</option>';
                            }

                            $stmt->close();
                            $conn2->close();
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Condition</label>
                    <select class="form-control form-control-sm" id="filterCondition">
                        <option value="">All Conditions</option>
                        <option value="new">New</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                        <option value="repair">Needs Repair</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-sm btn-primary" onclick="applyFilters()">
                            <i class="fas fa-check me-1"></i> Apply Filters
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-times me-1"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>Equipment List
                <small class="text-muted ms-2" id="totalCountText">(Loading...)</small>
            </h6>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="checkOutMultiple()" id="bulkCheckoutBtn" disabled>
                    <i class="fas fa-sign-out-alt me-1"></i> Bulk Check-out
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMultiple()" id="bulkDeleteBtn" disabled>
                    <i class="fas fa-trash me-1"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="itemsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll(this)">
                            </th>
                            <th width="60">#</th>
                            <th width="80" data-sort="id">ID <i class="fas fa-sort-down sort-icon ms-1"></i></th>
                            <th data-sort="item_name">Name</th>
                            <th data-sort="serial_number">Serial Number</th>
                            <th data-sort="category">Category</th>
                            <th data-sort="status">Status</th>
                            <th data-sort="stock_location">Location</th>
                            <th>Quantity</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be loaded via AJAX -->
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading equipment data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stats Modal -->
    <div class="modal fade" id="statsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>Equipment Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="statsContent">
                    Loading statistics...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- External Libraries for Export -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<style>
    /* Custom styles for the datatable */
    #itemsTable th {
        font-weight: 600;
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
        position: relative;
        cursor: pointer;
    }

    #itemsTable th.sort-asc .sort-icon::before {
        content: "\f0de";
    }

    #itemsTable th.sort-desc .sort-icon::before {
        content: "\f0dd";
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: nowrap;
    }

    .action-buttons .btn {
        padding: 4px 8px;
        font-size: 0.875rem;
        min-width: 32px;
    }

    /* Hover effect */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    /* Selected row */
    .selected-row {
        background-color: #e8f4fd !important;
        border-left: 3px solid #1c4481;
    }

    /* Status badges */
    .badge-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    /* Quick stats */
    .stat-card {
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-total {
        background: linear-gradient(135deg, #5368c7 0%, #764ba2 100%);
    }

    .stat-available {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    }

    .stat-inuse {
        background: linear-gradient(135deg, #3381c0 0%, #0D47A1 100%);
    }

    .stat-maintenance {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    }

    .stat-lost {
        background: linear-gradient(135deg, #d74338 0%, #b02323 100%);
    }

    .stat-card h5 {
        font-size: 1.75rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0;
    }

    /* Export button group */
    .export-btn-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        align-items: center;
        justify-content: space-between;
    }

    .export-btn-group .dropdown {
        flex: 0 0 auto;
    }

    .export-btn-group .btn-success {
        flex: 0 0 auto;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #1c4481;
        border: 1px solid #dee2e6;
        padding: 0.375rem 0.75rem;
    }

    .page-item.active .page-link {
        background-color: #1c4481;
        border-color: #1c4481;
        color: white;
    }

    .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 3px;
        }

        .action-buttons .btn {
            width: 100%;
            justify-content: center;
        }

        .export-btn-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .export-btn-group .dropdown,
        .export-btn-group .btn-success {
            width: 100%;
        }

        .card-header .btn-group {
            flex-wrap: wrap;
            gap: 5px;
        }
    }

    /* Loading animation */
    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }

    .loading-row {
        animation: pulse 1.5s infinite;
    }

    /* Notification styling */
    .alert-notification {
        animation: slideInRight 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: none;
        border-left: 4px solid;
    }

    .alert-notification.alert-success {
        border-left-color: #198754;
    }

    .alert-notification.alert-danger {
        border-left-color: #dc3545;
    }

    .alert-notification.alert-warning {
        border-left-color: #ffc107;
    }

    .alert-notification.alert-info {
        border-left-color: #0dcaf0;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Filter row */
    #advancedFilters .card-body {
        padding: 1.25rem;
    }

    #advancedFilters .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    /* Table responsive */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Checkbox styling */
    .form-check-input:checked {
        background-color: #1c4481;
        border-color: #1c4481;
    }

    .form-check-input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const API_BASE = '<?php echo BASE_URL; ?>api/';

    // Global variables
    let currentPage = 1;
    let itemsPerPage = 5;
    let totalItems = 0;
    let currentFilters = {};
    let selectedItems = new Set();
    let statsModal = null;
    let sortField = 'id';
    let sortDirection = 'desc';
    let isLoading = false;

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap modal
        const modalElement = document.getElementById('statsModal');
        if (modalElement) {
            statsModal = new bootstrap.Modal(modalElement);
        }

        // Set up sort column headers
        setupSortableHeaders();

        // Load initial data
        loadItems();

        // Set up event listeners
        setupEventListeners();
    });

    // Set up event listeners
    function setupEventListeners() {
        // Global search on Enter key
        document.getElementById('globalSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Filter dropdowns on change
        document.getElementById('filterCategory').addEventListener('change', function() {
            if (this.value) applyFilters();
        });

        document.getElementById('filterStatus').addEventListener('change', function() {
            if (this.value) applyFilters();
        });

        document.getElementById('filterLocation').addEventListener('change', function() {
            if (this.value) applyFilters();
        });

        document.getElementById('filterCondition').addEventListener('change', function() {
            if (this.value) applyFilters();
        });
    }

    // Set up sortable column headers
    function setupSortableHeaders() {
        const headers = document.querySelectorAll('#itemsTable thead th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const field = header.getAttribute('data-sort');
                toggleSort(field);
            });
        });
    }

    // Toggle sort field and direction
    function toggleSort(field) {
        if (sortField === field) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDirection = (field === 'id' || field === 'created_at' || field === 'updated_at') ? 'desc' : 'asc';
        }

        updateSortIndicators();
        loadItems(1);
    }

    // Update sort indicators in headers
    function updateSortIndicators() {
        const headers = document.querySelectorAll('#itemsTable thead th');
        headers.forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
            const icon = header.querySelector('.sort-icon');
            if (icon) {
                icon.className = 'sort-icon ms-1 fas';
            }
        });

        const currentHeader = document.querySelector(`#itemsTable thead th[data-sort="${sortField}"]`);
        if (currentHeader) {
            currentHeader.classList.add(`sort-${sortDirection}`);
            const icon = currentHeader.querySelector('.sort-icon');
            if (icon) {
                icon.classList.add(sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
            }
        }
    }

    // Load items from API
    async function loadItems(page = 1) {
        if (isLoading) return;

        currentPage = page;
        isLoading = true;

        // Show loading
        const tableBody = document.getElementById('tableBody');
        tableBody.innerHTML = `
        <tr class="loading-row">
            <td colspan="10" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading equipment data...</p>
            </td>
        </tr>
        `;

        try {
            // Build query string with filters
            const queryParams = new URLSearchParams({
                page: page,
                limit: itemsPerPage,
                sort: sortField,
                order: sortDirection,
                ...currentFilters
            });

            const apiUrl = '/ability_app-master/api/items/list.php?' + queryParams.toString();

            const response = await fetch(apiUrl);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                throw new Error('Server returned non-JSON response');
            }

            const data = await response.json();

            if (data.success) {
                renderItems(data.items);
                createPagination(data.total, data.total_pages);
                updateTotalCount(data.total);
            } else {
                showError('Failed to load items: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading items:', error);
            showError('Error: ' + error.message);
        } finally {
            isLoading = false;
        }
    }

    // Export to Excel
    // Export to Excel - All Items (Fixed with proper headers)
    async function exportToExcel() {
        try {
            showNotification('info', 'Preparing Excel export...', 3000);

            // Build query parameters
            const queryParams = new URLSearchParams();

            // Add all current filters
            if (currentFilters.search) queryParams.set('search', currentFilters.search);
            if (currentFilters.category) queryParams.set('category', currentFilters.category);
            if (currentFilters.status) queryParams.set('status', currentFilters.status);
            if (currentFilters.location) queryParams.set('location', currentFilters.location);
            if (currentFilters.condition) queryParams.set('condition', currentFilters.condition);

            // Add sort parameters
            queryParams.set('sort', sortField);
            queryParams.set('order', sortDirection);

            // Use the dedicated export endpoint
            const apiUrl = '/ability_app-master/api/items/export.php?' + queryParams.toString();

            console.log('Exporting from:', apiUrl);

            const response = await fetch(apiUrl);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch data for export');
            }

            const items = data.items;

            if (!items || items.length === 0) {
                showNotification('warning', 'No items found to export', 3000);
                return;
            }

            showNotification('info', `Processing ${items.length} items...`, 3000);

            // Prepare data for Excel with proper headers
            const excelData = [
                // FIRST: Define the header row
                [
                    'No.',
                    'Equipment ID',
                    'Equipment Name',
                    'Serial Number',
                    'Category',
                    'Status',
                    'Stock Location',
                    'Quantity',
                    'Condition',
                    'Brand/Model',
                    'Description',
                    'Created Date',
                    'Last Updated',
                    'Created By',
                    'Last Modified By',
                    'Purchase Date',
                    'Purchase Price',
                    'Supplier',
                    'Warranty Info',
                    'Notes'
                ],
                // THEN: Add the data rows
                ...items.map((item, index) => [
                    index + 1,
                    item.id,
                    item.name || 'Unnamed Item',
                    item.serial_number || 'N/A',
                    item.category || 'N/A',
                    getStatusLabel(item.status),
                    item.stock_location || 'Not Set',
                    item.quantity || 1,
                    item.condition || 'N/A',
                    item.brand_model || '',
                    item.description || '',
                    formatDate(item.created_at),
                    formatDate(item.updated_at),
                    item.created_by || '',
                    item.modified_by || '',
                    item.purchase_date || '',
                    item.purchase_price ? '$' + parseFloat(item.purchase_price).toFixed(2) : '',
                    item.supplier || '',
                    item.warranty_info || '',
                    item.notes || ''
                ])
            ];

            // Create worksheet from the 2D array (includes headers)
            const worksheet = XLSX.utils.aoa_to_sheet(excelData);

            // Auto-size columns
            const wscols = [{
                    wch: 5
                }, // No.
                {
                    wch: 10
                }, // Equipment ID
                {
                    wch: 30
                }, // Equipment Name
                {
                    wch: 20
                }, // Serial Number
                {
                    wch: 15
                }, // Category
                {
                    wch: 12
                }, // Status
                {
                    wch: 20
                }, // Stock Location
                {
                    wch: 8
                }, // Quantity
                {
                    wch: 12
                }, // Condition
                {
                    wch: 25
                }, // Brand/Model
                {
                    wch: 40
                }, // Description
                {
                    wch: 15
                }, // Created Date
                {
                    wch: 15
                }, // Last Updated
                {
                    wch: 15
                }, // Created By
                {
                    wch: 15
                }, // Last Modified By
                {
                    wch: 12
                }, // Purchase Date
                {
                    wch: 12
                }, // Purchase Price
                {
                    wch: 20
                }, // Supplier
                {
                    wch: 20
                }, // Warranty Info
                {
                    wch: 30
                } // Notes
            ];
            worksheet['!cols'] = wscols;

            // Style the header row
            const headerRange = XLSX.utils.decode_range(worksheet['!ref']);
            for (let C = headerRange.s.c; C <= headerRange.e.c; ++C) {
                const cellAddress = XLSX.utils.encode_cell({
                    r: 0,
                    c: C
                });
                if (!worksheet[cellAddress]) continue;

                // Make header row bold
                worksheet[cellAddress].s = {
                    font: {
                        bold: true
                    },
                    fill: {
                        fgColor: {
                            rgb: "E0E0E0"
                        }
                    },
                    alignment: {
                        vertical: "center",
                        horizontal: "center"
                    }
                };
            }

            // Add title rows above the data
            const titleRows = [
                ['EQUIPMENT LIST - COMPLETE EXPORT'],
                [`Generated: ${new Date().toLocaleString()}`],
                [`Total Items: ${items.length}`],
                [`Filters: ${Object.keys(currentFilters).length > 0 ? 'Applied' : 'None'}`],
                [''] // Empty row before headers
            ];

            // Add title rows to the top of the sheet
            XLSX.utils.sheet_add_aoa(worksheet, titleRows, {
                origin: "A1"
            });

            // Merge title cells
            if (!worksheet['!merges']) worksheet['!merges'] = [];
            worksheet['!merges'].push({
                    s: {
                        r: 0,
                        c: 0
                    },
                    e: {
                        r: 0,
                        c: 19
                    }
                }, // Main title
                {
                    s: {
                        r: 1,
                        c: 0
                    },
                    e: {
                        r: 1,
                        c: 19
                    }
                }, // Generated date
                {
                    s: {
                        r: 2,
                        c: 0
                    },
                    e: {
                        r: 2,
                        c: 19
                    }
                }, // Total items
                {
                    s: {
                        r: 3,
                        c: 0
                    },
                    e: {
                        r: 3,
                        c: 19
                    }
                } // Filters
            );

            // Style title rows
            const titleCell = XLSX.utils.encode_cell({
                r: 0,
                c: 0
            });
            worksheet[titleCell].s = {
                font: {
                    bold: true,
                    size: 16
                },
                alignment: {
                    horizontal: "center"
                }
            };

            // Create workbook
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Equipment List');

            // Generate file
            const excelBuffer = XLSX.write(workbook, {
                bookType: 'xlsx',
                type: 'array'
            });

            const blob = new Blob([excelBuffer], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });

            // Download file
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Equipment_Export_${formatDateForFilename()}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showNotification('success', `Excel export completed! (${items.length} items)`, 5000);

        } catch (error) {
            console.error('Export error:', error);
            showNotification('error', 'Error exporting to Excel: ' + error.message, 5000);
        }
    }

    // Export to PDF
    async function exportToPDF() {
        try {
            showNotification('info', 'Preparing PDF export...', 3000);

            const queryParams = new URLSearchParams({
                limit: 0,
                sort: sortField,
                order: sortDirection,
                ...currentFilters
            });

            const apiUrl = '/ability_app-master/api/items/list.php?' + queryParams.toString();
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch data');
            }

            // Create PDF
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            // Add title
            doc.setFontSize(16);
            doc.text('Equipment List', 14, 15);
            doc.setFontSize(10);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);
            doc.text(`Total Items: ${data.items.length}`, 14, 28);

            // Prepare table data
            const tableData = data.items.map((item, index) => [
                index + 1,
                item.id,
                truncateText(item.name || '', 25),
                item.serial_number || '',
                item.category || '',
                getStatusLabel(item.status),
                truncateText(item.stock_location || '', 20),
                item.quantity || 1
            ]);

            // Create table
            doc.autoTable({
                startY: 35,
                head: [
                    ['#', 'ID', 'Name', 'Serial', 'Category', 'Status', 'Location', 'Qty']
                ],
                body: tableData,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [41, 128, 185],
                    textColor: 255
                },
                columnStyles: {
                    0: {
                        cellWidth: 8
                    },
                    1: {
                        cellWidth: 10
                    },
                    2: {
                        cellWidth: 30
                    },
                    3: {
                        cellWidth: 20
                    },
                    4: {
                        cellWidth: 20
                    },
                    5: {
                        cellWidth: 15
                    },
                    6: {
                        cellWidth: 25
                    },
                    7: {
                        cellWidth: 10
                    }
                },
                margin: {
                    left: 14,
                    right: 14
                }
            });

            // Save PDF
            doc.save(`equipment_${formatDateForFilename()}.pdf`);

            showNotification('success', 'PDF file downloaded successfully!', 3000);

        } catch (error) {
            console.error('PDF export error:', error);
            showNotification('error', 'Error exporting to PDF: ' + error.message, 5000);
        }
    }

    // Export to CSV
    async function exportToCSV() {
        try {
            showNotification('info', 'Preparing CSV export...', 3000);

            const queryParams = new URLSearchParams({
                limit: 0,
                sort: sortField,
                order: sortDirection,
                ...currentFilters
            });

            const apiUrl = '/ability_app-master/api/items/list.php?' + queryParams.toString();
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch data');
            }

            // Prepare CSV content
            const headers = ['#', 'ID', 'Name', 'Serial Number', 'Category', 'Status', 'Location', 'Quantity', 'Condition', 'Description', 'Brand/Model', 'Last Updated'];
            const csvRows = [];

            // Add header row
            csvRows.push(headers.join(','));

            // Add data rows
            data.items.forEach((item, index) => {
                const row = [
                    index + 1,
                    item.id,
                    `"${(item.name || '').replace(/"/g, '""')}"`,
                    `"${(item.serial_number || '').replace(/"/g, '""')}"`,
                    `"${(item.category || '').replace(/"/g, '""')}"`,
                    `"${getStatusLabel(item.status).replace(/"/g, '""')}"`,
                    `"${(item.stock_location || '').replace(/"/g, '""')}"`,
                    item.quantity || 1,
                    `"${(item.condition || '').replace(/"/g, '""')}"`,
                    `"${(item.description || '').replace(/"/g, '""')}"`,
                    `"${(item.brand_model || '').replace(/"/g, '""')}"`,
                    `"${formatDate(item.updated_at).replace(/"/g, '""')}"`
                ];
                csvRows.push(row.join(','));
            });

            // Create CSV content
            const csvContent = csvRows.join('\n');
            const blob = new Blob(['\uFEFF' + csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);

            // Create download link
            const a = document.createElement('a');
            a.href = url;
            a.download = `equipment_${formatDateForFilename()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showNotification('success', 'CSV file downloaded successfully!', 3000);

        } catch (error) {
            console.error('CSV export error:', error);
            showNotification('error', 'Error exporting to CSV: ' + error.message, 5000);
        }
    }

    // Print table
    function printTable() {
        try {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html>
                <head>
                    <title>Equipment List - Print</title>
                    <style>
                        @media print {
                            body { 
                                font-family: Arial, sans-serif; 
                                margin: 20px; 
                                font-size: 12px;
                            }
                            h1 { 
                                color: #333; 
                                margin-bottom: 10px;
                            }
                            .print-header { 
                                margin-bottom: 20px; 
                                border-bottom: 2px solid #333; 
                                padding-bottom: 10px; 
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                margin-top: 20px; 
                                font-size: 11px;
                            }
                            th { 
                                background-color: #f8f9fa; 
                                text-align: left; 
                                padding: 8px; 
                                border: 1px solid #dee2e6; 
                                font-weight: bold;
                            }
                            td { 
                                padding: 8px; 
                                border: 1px solid #dee2e6; 
                                vertical-align: top;
                            }
                            .badge { 
                                padding: 2px 6px; 
                                border-radius: 10px; 
                                font-size: 10px; 
                                font-weight: bold;
                            }
                            .bg-success { background-color: #d1e7dd !important; color: #0f5132; }
                            .bg-primary { background-color: #cfe2ff !important; color: #084298; }
                            .bg-warning { background-color: #fff3cd !important; color: #664d03; }
                            .bg-info { background-color: #d8f2ff !important; color: #055160; }
                            .bg-danger { background-color: #f8d7da !important; color: #842029; }
                            .bg-secondary { background-color: #e9ecef !important; color: #495057; }
                            .bg-dark { background-color: #343a40 !important; color: white; }
                            .text-muted { color: #6c757d; }
                            .footer { 
                                margin-top: 30px; 
                                font-size: 10px; 
                                color: #6c757d; 
                                text-align: center;
                            }
                            .page-break { page-break-after: always; }
                        }
                        @page {
                            size: landscape;
                            margin: 0.5cm;
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>Equipment List</h1>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>Total Items: ${totalItems}</p>
                    </div>
            `);

            // Get current table data
            const table = document.getElementById('itemsTable');
            const clonedTable = table.cloneNode(true);

            // Remove action buttons
            clonedTable.querySelectorAll('td:last-child, th:last-child').forEach(cell => cell.remove());

            // Convert badges to print-friendly format
            clonedTable.querySelectorAll('.badge').forEach(badge => {
                badge.classList.remove('badge');
                badge.style.cssText = `
                    padding: 2px 6px;
                    border-radius: 10px;
                    font-size: 10px;
                    font-weight: bold;
                    display: inline-block;
                    margin: 1px;
                `;
            });

            printWindow.document.write(clonedTable.outerHTML);
            printWindow.document.write(`
                    <div class="footer">
                        <p>Generated by aBility Equipment Management System</p>
                        <p>Page 1 of 1</p>
                    </div>
                </body>
            </html>
            `);

            printWindow.document.close();

            // Print after content loads
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);

        } catch (error) {
            console.error('Print error:', error);
            showNotification('error', 'Error printing table: ' + error.message, 5000);
        }
    }

    // Render items in table
    function renderItems(items) {
        const tableBody = document.getElementById('tableBody');

        if (!items || items.length === 0) {
            tableBody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5>No equipment found</h5>
                    <p class="text-muted">No equipment records match your criteria</p>
                    <a href="items.php?action=create" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i>Add New Equipment
                    </a>
                </td>
            </tr>
            `;
            return;
        }

        let html = '';
        const startNumber = (currentPage - 1) * itemsPerPage + 1;

        items.forEach((item, index) => {
            const isSelected = selectedItems.has(item.id.toString());
            const statusClass = getStatusClass(item.status);
            const statusText = getStatusLabel(item.status);
            const sequenceNumber = startNumber + index;

            html += `
            <tr id="row-${item.id}" class="${isSelected ? 'selected-row' : ''}">
                <td>
                    <input type="checkbox" class="form-check-input item-checkbox" 
                           value="${item.id}" 
                           onchange="toggleSelectItem(this, ${item.id})"
                           ${isSelected ? 'checked' : ''}>
                </td>
                <td><span class="badge bg-secondary">#${sequenceNumber}</span></td>
                <td><span class="badge bg-dark">ID: ${item.id}</span></td>
                <td>
                    <strong>${escapeHtml(item.name || 'Unnamed Item')}</strong>
                    ${item.description ? `<br><small class="text-muted">${escapeHtml(item.description.substring(0, 50))}${item.description.length > 50 ? '...' : ''}</small>` : ''}
                </td>
                <td>
                    <code class="text-nowrap">${escapeHtml(item.serial_number || 'N/A')}</code>
                    ${item.qr_code ? `
                        <br>
                        <small>
                            <a href="${BASE_URL}${escapeHtml(item.qr_code)}" target="_blank" title="View QR Code">
                                <i class="fas fa-qrcode text-primary"></i> QR Code
                            </a>
                        </small>
                    ` : ''}
                </td>
                <td>${escapeHtml(item.category || 'N/A')}</td>
                <td>
                    <span class="badge ${statusClass} badge-status">
                        ${statusText}
                    </span>
                </td>
                <td>${escapeHtml(item.stock_location || 'Not Set')}</td>
                <td>
                    <span class="badge bg-info">${item.quantity || 1}</span>
                    ${item.condition ? `<br><small class="text-muted">Condition: ${escapeHtml(item.condition)}</small>` : ''}
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="items.php?action=view&id=${item.id}" class="btn btn-sm btn-info" 
                           title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="items.php?action=edit&id=${item.id}" class="btn btn-sm btn-warning" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" 
                                onclick="confirmDelete(${item.id}, '${escapeHtml(item.name || 'this item')}')"
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });

        tableBody.innerHTML = html;
    }

    // Create pagination
    function createPagination(totalItems, totalPages) {
        const table = document.getElementById('itemsTable');
        let tfoot = table.querySelector('tfoot');

        if (!tfoot) {
            tfoot = document.createElement('tfoot');
            table.appendChild(tfoot);
        }

        if (totalPages <= 1) {
            tfoot.innerHTML = '';
            return;
        }

        let paginationHtml = `
            <tr>
                <td colspan="10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Page ${currentPage} of ${totalPages} 
                            • Showing ${(currentPage - 1) * itemsPerPage + 1} to 
                            ${Math.min(currentPage * itemsPerPage, totalItems)} of ${totalItems} items
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
        `;

        // Previous button
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadItems(${currentPage - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        // First page
        if (startPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadItems(1); return false;">1</a>
                </li>
                ${startPage > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
            `;
        }

        // Page range
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${i}); return false;">${i}</a></li>`;
            }
        }

        // Last page
        if (endPage < totalPages) {
            paginationHtml += `
                ${endPage < totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadItems(${totalPages}); return false;">${totalPages}</a>
                </li>
            `;
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadItems(${currentPage + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginationHtml += `
                            </ul>
                        </nav>
                    </div>
                </td>
            </tr>
        `;

        tfoot.innerHTML = paginationHtml;
    }

    // Update total count display
    function updateTotalCount(total) {
        totalItems = total;
        const totalCountText = document.getElementById('totalCountText');
        if (totalCountText) {
            totalCountText.textContent = `(${total} items found)`;
        }
    }

    // Toggle filters visibility
    function toggleFilters() {
        const filterRow = document.getElementById('advancedFilters');
        const button = document.querySelector('button[onclick="toggleFilters()"]');

        if (filterRow.style.display === 'none' || filterRow.style.display === '') {
            filterRow.style.display = 'block';
            if (button) button.innerHTML = '<i class="fas fa-filter"></i> Hide Filters';
        } else {
            filterRow.style.display = 'none';
            if (button) button.innerHTML = '<i class="fas fa-filter"></i> Advanced Filters';
        }
    }

    // Apply filters
    function applyFilters() {
        const globalSearch = document.getElementById('globalSearch').value.trim();

        currentFilters = {
            category: document.getElementById('filterCategory').value,
            status: document.getElementById('filterStatus').value,
            location: document.getElementById('filterLocation').value,
            condition: document.getElementById('filterCondition').value,
            search: globalSearch || ''
        };

        // Remove empty filters
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });

        loadItems(1);
        showNotification('info', 'Filters applied', 2000);
    }

    // Reset filters
    function resetFilters() {
        document.getElementById('globalSearch').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterLocation').value = '';
        document.getElementById('filterCondition').value = '';

        currentFilters = {};
        loadItems(1);
        showNotification('info', 'All filters cleared', 2000);
    }

    // Change page length
    function changePageLength() {
        const select = document.getElementById('pageLength');
        itemsPerPage = parseInt(select.value);
        loadItems(1);
    }

    // Refresh table
    function refreshTable() {
        loadItems(currentPage);
        showNotification('info', 'Table refreshed', 2000);
    }

    // Show error message
    function showError(message) {
        const tableBody = document.getElementById('tableBody');
        tableBody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Error Loading Data</h5>
                    <p class="text-muted">${escapeHtml(message)}</p>
                    <button class="btn btn-primary mt-2" onclick="loadItems()">
                        <i class="fas fa-redo me-2"></i>Try Again
                    </button>
                </td>
            </tr>
        `;
    }

    // Toggle select all items
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
            const itemId = cb.value;
            if (checkbox.checked) {
                selectedItems.add(itemId);
                const row = document.getElementById(`row-${itemId}`);
                if (row) row.classList.add('selected-row');
            } else {
                selectedItems.delete(itemId);
                const row = document.getElementById(`row-${itemId}`);
                if (row) row.classList.remove('selected-row');
            }
        });
        updateBulkButtons();
    }

    // Toggle individual item selection
    function toggleSelectItem(checkbox, itemId) {
        if (checkbox.checked) {
            selectedItems.add(itemId.toString());
            const row = document.getElementById(`row-${itemId}`);
            if (row) row.classList.add('selected-row');
        } else {
            selectedItems.delete(itemId.toString());
            const row = document.getElementById(`row-${itemId}`);
            if (row) row.classList.remove('selected-row');
            document.getElementById('selectAll').checked = false;
        }
        updateBulkButtons();
    }

    // Update bulk action buttons
    function updateBulkButtons() {
        const bulkCheckoutBtn = document.getElementById('bulkCheckoutBtn');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

        if (bulkCheckoutBtn) {
            bulkCheckoutBtn.disabled = selectedItems.size === 0;
            bulkCheckoutBtn.title = selectedItems.size > 0 ? `Check out ${selectedItems.size} item(s)` : 'Select items to check out';
        }
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = selectedItems.size === 0;
            bulkDeleteBtn.title = selectedItems.size > 0 ? `Delete ${selectedItems.size} item(s)` : 'Select items to delete';
        }
    }

    // Check out multiple items
    async function checkOutMultiple() {
        if (selectedItems.size === 0) {
            showNotification('warning', 'Please select items to check out', 3000);
            return;
        }

        try {
            showNotification('info', `Preparing to check out ${selectedItems.size} item(s)...`, 3000);

            // Here you would implement the check-out functionality
            // For now, just show a confirmation
            const confirmCheckout = confirm(`Check out ${selectedItems.size} selected item(s)?\n\nThis will mark them as "In Use".`);

            if (confirmCheckout) {
                // Call API to update status
                const response = await fetch('/ability_app-master/api/items/bulk_update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ids: Array.from(selectedItems),
                        status: 'in_use'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', `Successfully checked out ${selectedItems.size} item(s)`, 3000);
                    selectedItems.clear();
                    document.getElementById('selectAll').checked = false;
                    loadItems(currentPage);
                } else {
                    showNotification('error', 'Failed to check out items: ' + (data.message || 'Unknown error'), 5000);
                }
            }
        } catch (error) {
            console.error('Checkout error:', error);
            showNotification('error', 'Error checking out items: ' + error.message, 5000);
        }
    }

    // Delete multiple items
    async function deleteMultiple() {
        if (selectedItems.size === 0) {
            showNotification('warning', 'Please select items to delete', 3000);
            return;
        }

        if (confirm(`Are you sure you want to delete ${selectedItems.size} selected item(s)?\n\nThis action cannot be undone.`)) {
            try {
                showNotification('info', `Deleting ${selectedItems.size} item(s)...`, 3000);

                const response = await fetch('/ability_app-master/api/items/bulk_delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ids: Array.from(selectedItems)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', `Successfully deleted ${selectedItems.size} item(s)`, 3000);
                    selectedItems.clear();
                    document.getElementById('selectAll').checked = false;
                    loadItems(currentPage);
                } else {
                    showNotification('error', 'Failed to delete items: ' + (data.message || 'Unknown error'), 5000);
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
                showNotification('error', 'Error deleting items: ' + error.message, 5000);
            }
        }
    }

    // Confirm delete single item
    function confirmDelete(itemId, itemName) {
        if (confirm(`Are you sure you want to delete "${itemName}"?\n\nThis action cannot be undone.`)) {
            deleteItem(itemId);
        }
    }

    // Delete single item
    async function deleteItem(itemId) {
        try {
            showNotification('info', 'Deleting item...', 2000);

            const response = await fetch(`/ability_app-master/api/items/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: itemId
                })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', 'Item deleted successfully', 3000);
                selectedItems.delete(itemId.toString());
                loadItems(currentPage);
            } else {
                showNotification('error', 'Failed to delete item: ' + (data.message || 'Unknown error'), 5000);
            }
        } catch (error) {
            console.error('Delete error:', error);
            showNotification('error', 'Network error. Please try again.', 5000);
        }
    }

    // Show quick stats
    async function showQuickStats() {
        try {
            showNotification('info', 'Loading statistics...', 2000);

            const response = await fetch('/ability_app-master/api/items/stats.php');
            const data = await response.json();

            let statsHtml = '<div class="row">';

            if (data.success) {
                const stats = data.stats;

                statsHtml += `
                    <div class="col-md-3">
                        <div class="stat-card stat-total">
                            <h5>${stats.total || 0}</h5>
                            <p class="mb-0">Total Items</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-available">
                            <h5>${stats.available || 0}</h5>
                            <p class="mb-0">Available</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-inuse">
                            <h5>${stats.in_use || 0}</h5>
                            <p class="mb-0">In Use</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-maintenance">
                            <h5>${stats.maintenance || 0}</h5>
                            <p class="mb-0">Maintenance</p>
                        </div>
                    </div>
                `;

                // Add category breakdown if available
                if (stats.categories && stats.categories.length > 0) {
                    statsHtml += `
                        <div class="col-12 mt-3">
                            <h6>Categories</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Category</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;

                    stats.categories.forEach(cat => {
                        const percentage = stats.total ? ((cat.count / stats.total) * 100).toFixed(1) : 0;
                        statsHtml += `
                            <tr>
                                <td>${escapeHtml(cat.category)}</td>
                                <td>${cat.count}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: ${percentage}%" 
                                             aria-valuenow="${percentage}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            ${percentage}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });

                    statsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }
            } else {
                statsHtml += `
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Could not load statistics: ${data.message || 'Unknown error'}
                        </div>
                    </div>
                `;
            }

            statsHtml += '</div>';

            document.getElementById('statsContent').innerHTML = statsHtml;
            if (statsModal) {
                statsModal.show();
            }
        } catch (error) {
            console.error('Stats error:', error);
            document.getElementById('statsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading statistics: ${error.message}
                </div>
            `;
            if (statsModal) {
                statsModal.show();
            }
        }
    }

    // Show QR scanner
    function showQRScanner() {
        window.location.href = '/ability_app-master/scan.php';
    }

    // Show notification
    function showNotification(type, message, duration = 5000) {
        // Remove existing notifications
        const existing = document.querySelectorAll('.alert-notification');
        existing.forEach(el => el.remove());

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show alert-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }

    // Add this helper function to show/hide notifications
    let activeNotification = null;

    function showNotification(type, message, duration = 5000) {
        // Remove existing notification
        if (activeNotification) {
            activeNotification.remove();
            activeNotification = null;
        }

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show alert-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
        notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${escapeHtml(message)}
        <button type="button" class="btn-close" onclick="hideNotification()"></button>
    `;

        document.body.appendChild(notification);
        activeNotification = notification;

        if (duration > 0) {
            setTimeout(() => {
                hideNotification();
            }, duration);
        }

        return notification;
    }

    function hideNotification() {
        if (activeNotification) {
            activeNotification.remove();
            activeNotification = null;
        }
    }

    // Helper functions
    function getStatusLabel(status) {
        const statusMap = {
            'available': 'Available',
            'in_use': 'In Use',
            'maintenance': 'Maintenance',
            'reserved': 'Reserved',
            'lost': 'Lost',
            'damaged': 'Damaged'
        };
        return statusMap[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ') : 'Available');
    }

    function getStatusClass(status) {
        const classMap = {
            'available': 'bg-success',
            'in_use': 'bg-primary',
            'maintenance': 'bg-warning',
            'reserved': 'bg-info',
            'lost': 'bg-danger',
            'damaged': 'bg-danger'
        };
        return classMap[status] || 'bg-secondary';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }

    function formatDateForFilename() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}${month}${day}_${hours}${minutes}`;
    }

    function truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength - 3) + '...';
    }
</script>

<?php
// Include footer
require_once __DIR__ . '/../partials/footer.php';
?>