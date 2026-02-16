<?php
// views/items/index.php
$pageTitle = "Equipment List - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Equipment' => 'items.php'
];

require_once __DIR__ . '/../partials/header.php';
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
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Controls Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 border-end-0" id="globalSearch"
                            placeholder="Search by #, ID, Item Name, Serial Number, Category, Status, or Location...">
                        <button class="btn btn-primary" type="button" onclick="applyFilters()">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                    <div class="form-text text-muted small mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        Search in: #, ID, Item Name, Serial Number, Category, Status, Location
                    </div>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                        <select class="form-select" id="pageLength" onchange="changePageLength()">
                            <option value="8" selected>8 per page</option>
                            <option value="12">12 per page</option>
                            <option value="16">16 per page</option>
                            <option value="20">20 per page</option>
                            <option value="24">24 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
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
                    <select class="form-select" id="filterCategory">
                        <option value="">All Categories</option>
                        <?php
                        $conn = new mysqli("localhost", "root", "", "ability_db");
                        if (!$conn->connect_error) {
                            $result = $conn->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category");
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['category']) . '">' . htmlspecialchars($row['category']) . '</option>';
                            }
                            $conn->close();
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select class="form-select" id="filterStatus">
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
                    <select class="form-select" id="filterLocation">
                        <option value="">All Locations</option>
                        <?php
                        $conn2 = new mysqli("localhost", "root", "", "ability_db");
                        if (!$conn2->connect_error) {
                            $result = $conn2->query("SELECT DISTINCT stock_location FROM items WHERE stock_location IS NOT NULL AND stock_location != '' ORDER BY stock_location");
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['stock_location']) . '">' . htmlspecialchars($row['stock_location']) . '</option>';
                            }
                            $conn2->close();
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Condition</label>
                    <select class="form-select" id="filterCondition">
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
                <span class="badge bg-primary ms-2" id="totalCountText">0 items</span>
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
                <table class="table table-striped table-bordered align-middle w-100" id="itemsTable">
                    <thead>
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th width="60">#</th>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Serial Number</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Quantity</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading items...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Container -->
            <div id="paginationContainer"></div>
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

<style>
    /* Your existing styles plus these pagination fixes */

    /* Pagination container */
    #paginationContainer {
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    /* Pagination wrapper - horizontal layout */
    .pagination-wrapper {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        background: white;
        padding: 0.75rem 1.5rem;
        border-radius: 60px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        flex-wrap: wrap;
    }

    /* Pagination info text */
    .pagination-info {
        color: #1f5e4f;
        font-size: 0.9rem;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Pagination list */
    .pagination {
        display: flex;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
        justify-content: center;
    }

    /* Page items */
    .page-item {
        display: inline-block;
        margin: 0;
    }

    /* Page links - FIXED: Removed border-radius on hover */
    .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        background: white;
        color: #1f5e4f;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
        cursor: pointer;
        /* No border-radius by default - added on container instead */
    }

    /* Hover effect - no border-radius change */
    .page-item .page-link:hover {
        background: #ecf5f2;
        border-color: #1f5e4f;
        color: #1f5e4f;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* No border-radius on hover */
    }

    /* Active page */
    .page-item.active .page-link {
        background: #1f5e4f;
        color: white;
        border-color: #1f5e4f;
        box-shadow: 0 4px 12px rgba(31, 94, 79, 0.3);
    }

    /* Disabled page */
    .page-item.disabled .page-link {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
        background: #f8f9fa;
    }

    .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: none;
        border-color: #e9ecef;
    }

    /* Items per page dropdown */
    .items-per-page {
        padding: 0.4rem 2rem 0.4rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 30px;
        background: white;
        color: #1f5e4f;
        font-weight: 500;
        font-size: 0.85rem;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%231f5e4f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 12px;
        min-width: 120px;
    }

    .items-per-page:hover {
        border-color: #1f5e4f;
        background-color: #ecf5f2;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .pagination-wrapper {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
            border-radius: 30px;
        }

        .pagination-info {
            text-align: center;
        }

        .page-item .page-link {
            min-width: 32px;
            height: 32px;
            font-size: 0.8rem;
            padding: 0 6px;
        }

        .pagination {
            gap: 0.3rem;
        }
    }

    /* Status badges - keep your existing styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
        display: inline-block;
    }

    .status-available {
        background: #d4edda;
        color: #155724;
    }

    .status-in_use {
        background: #cce5ff;
        color: #004085;
    }

    .status-maintenance {
        background: #fff3cd;
        color: #856404;
    }

    .status-reserved {
        background: #e2d5f1;
        color: #563d7c;
    }

    .status-lost,
    .status-damaged {
        background: #f8d7da;
        color: #721c24;
    }

    .selected-row {
        background-color: #e8f4fd !important;
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

    .export-btn-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        align-items: center;
        justify-content: space-between;
    }

    /* Search input styling */
    #globalSearch:focus {
        box-shadow: none;
        border-color: #1f5e4f;
    }

    #globalSearch:focus+.btn-primary {
        border-color: #1f5e4f;
    }

    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }

        .export-btn-group {
            flex-direction: column;
            align-items: flex-start;
        }

        .export-btn-group .dropdown,
        .export-btn-group .btn-success {
            width: 100%;
        }
    }
</style>

<!-- DataTables CSS - We'll keep minimal for table styling only -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS - Minimal, without pagination features -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Export libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    const BASE_URL = '<?php echo BASE_URL ?? "/ability_app_main/"; ?>';

    // State
    let events = [];
    let currentPage = 1;
    let itemsPerPage = 8;
    let totalPages = 1;
    let totalItems = 0;
    let selectedItems = new Set();
    let statsModal = null;
    let currentFilters = {};
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap modal
        const modalElement = document.getElementById('statsModal');
        if (modalElement) {
            statsModal = new bootstrap.Modal(modalElement);
        }

        // Load initial data
        loadItems();

        // Set up event listeners
        setupEventListeners();
    });

    function setupEventListeners() {
        // Real-time search with debounce
        $('#globalSearch').on('input', function() {
            if (searchTimeout) clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });

        // Enter key in global search
        $('#globalSearch').on('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                applyFilters();
            }
        });

        // Filter changes
        $('#filterCategory, #filterStatus, #filterLocation, #filterCondition').on('change', function() {
            applyFilters();
        });

        // Select all checkbox
        $('#selectAll').on('change', function() {
            const checked = this.checked;
            $('.item-checkbox').each(function() {
                this.checked = checked;
                const itemId = $(this).val();
                if (checked) {
                    selectedItems.add(itemId.toString());
                } else {
                    selectedItems.delete(itemId.toString());
                }
            });
            updateBulkButtons();
            updateSelectedRowsStyle();
        });
    }

    // Load items from API
    async function loadItems() {
        const tbody = document.getElementById('tableBody');

        // Show loading
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading items...</p>
                </td>
            </tr>
        `;

        try {
            // Build query string with filters
            const params = new URLSearchParams({
                page: currentPage,
                limit: itemsPerPage,
                ...currentFilters
            });

            const response = await fetch(`${BASE_URL}api/items/list.php?${params}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                events = data.items || [];
                totalPages = data.pagination?.totalPages || 1;
                totalItems = data.pagination?.totalItems || 0;

                renderItems(events);
                renderPagination();
                updateTotalCount(totalItems);
            } else {
                showError(data.error || 'Failed to load items');
            }
        } catch (error) {
            console.error('Error loading items:', error);
            showError(error.message);
        }
    }

    // Render items in table
    function renderItems(items) {
        const tbody = document.getElementById('tableBody');

        if (!items || items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>No equipment found</h5>
                        <p class="text-muted">No equipment records match your criteria</p>
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
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input item-checkbox" 
                               value="${item.id}" 
                               onchange="toggleSelectItem(this, ${item.id})"
                               ${isSelected ? 'checked' : ''}>
                    </td>
                    <td><span class="badge bg-secondary">#${sequenceNumber}</span></td>
                    <td><span class="badge bg-dark">${item.id}</span></td>
                    <td>
                        <strong>${escapeHtml(item.item_name || 'Unnamed')}</strong>
                        ${item.description ? `<br><small class="text-muted">${escapeHtml(item.description.substring(0, 50))}${item.description.length > 50 ? '...' : ''}</small>` : ''}
                    </td>
                    <td>
                        <code>${escapeHtml(item.serial_number || 'N/A')}</code>
                        ${item.qr_code ? `<br><small><a href="${BASE_URL}${escapeHtml(item.qr_code)}" target="_blank"><i class="fas fa-qrcode text-primary"></i> QR</a></small>` : ''}
                    </td>
                    <td>${escapeHtml(item.category || 'N/A')}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        ${escapeHtml(item.stock_location || 'Not Set')}
                        ${item.current_location ? `<br><small class="text-muted">Current: ${escapeHtml(item.current_location)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-info">${item.quantity || 1}</span>
                        ${item.condition ? `<br><small class="text-muted">${escapeHtml(item.condition)}</small>` : ''}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="items.php?action=view&id=${item.id}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="items.php?action=edit&id=${item.id}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(${item.id}, '${escapeHtml(item.item_name || 'this item')}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // Render pagination
    function renderPagination() {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        const startItem = ((currentPage - 1) * itemsPerPage) + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        let paginationHtml = `
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing ${startItem} - ${endItem} of ${totalItems} items
                </div>
                <ul class="pagination">
        `;

        // Previous button
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;" ${currentPage === 1 ? 'tabindex="-1"' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // First page
        if (startPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changePage(1); return false;">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                </li>
            `;
        }

        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>
                </li>
            `;
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;" ${currentPage === totalPages ? 'tabindex="-1"' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        // Items per page selector
        paginationHtml += `
                </ul>
                <select class="items-per-page" onchange="changeItemsPerPage(this.value)">
                    <option value="8" ${itemsPerPage === 8 ? 'selected' : ''}>8 per page</option>
                    <option value="12" ${itemsPerPage === 12 ? 'selected' : ''}>12 per page</option>
                    <option value="16" ${itemsPerPage === 16 ? 'selected' : ''}>16 per page</option>
                    <option value="20" ${itemsPerPage === 20 ? 'selected' : ''}>20 per page</option>
                    <option value="24" ${itemsPerPage === 24 ? 'selected' : ''}>24 per page</option>
                    <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50 per page</option>
                    <option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100 per page</option>
                </select>
            </div>
        `;

        container.innerHTML = paginationHtml;
    }

    // Change page
    window.changePage = function(newPage) {
        if (newPage < 1 || newPage > totalPages || newPage === currentPage) return;
        currentPage = newPage;
        loadItems();

        // Smooth scroll to top of table
        document.querySelector('.card-header').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    };

    // Change items per page
    window.changeItemsPerPage = function(newValue) {
        itemsPerPage = parseInt(newValue);
        currentPage = 1;
        loadItems();
    };

    // Update total count
    function updateTotalCount(total) {
        const totalCountText = document.getElementById('totalCountText');
        if (totalCountText) {
            totalCountText.textContent = `${total} items`;
        }
    }

    // Show error
    function showError(message) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = `
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

    // Apply filters
    function applyFilters() {
        currentFilters = {
            search: $('#globalSearch').val(),
            category: $('#filterCategory').val(),
            status: $('#filterStatus').val(),
            location: $('#filterLocation').val(),
            condition: $('#filterCondition').val()
        };

        // Remove empty filters
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) delete currentFilters[key];
        });

        currentPage = 1;
        loadItems();
        showNotification('info', 'Filters applied', 2000);
    }

    // Reset filters
    function resetFilters() {
        $('#globalSearch').val('');
        $('#filterCategory').val('');
        $('#filterStatus').val('');
        $('#filterLocation').val('');
        $('#filterCondition').val('');

        currentFilters = {};
        currentPage = 1;
        loadItems();
        showNotification('info', 'All filters cleared', 2000);
    }

    // Toggle filters visibility
    function toggleFilters() {
        const filterRow = $('#advancedFilters');
        const button = $('button[onclick="toggleFilters()"]');

        filterRow.slideToggle(200);
        button.html(filterRow.is(':visible') ?
            '<i class="fas fa-filter"></i> Hide Filters' :
            '<i class="fas fa-filter"></i> Advanced Filters');
    }

    // Change page length
    function changePageLength() {
        itemsPerPage = parseInt($('#pageLength').val());
        currentPage = 1;
        loadItems();
    }

    // Refresh table
    function refreshTable() {
        loadItems();
        showNotification('info', 'Table refreshed', 2000);
    }

    // Toggle select item
    function toggleSelectItem(checkbox, itemId) {
        if (checkbox.checked) {
            selectedItems.add(itemId.toString());
        } else {
            selectedItems.delete(itemId.toString());
            $('#selectAll').prop('checked', false);
        }
        updateBulkButtons();
        updateSelectAllState();
        $(`#row-${itemId}`).toggleClass('selected-row', checkbox.checked);
    }

    // Update select all state
    function updateSelectAllState() {
        const totalCheckboxes = $('.item-checkbox').length;
        const checkedCheckboxes = $('.item-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }

    // Update bulk buttons
    function updateBulkButtons() {
        const count = selectedItems.size;
        $('#bulkCheckoutBtn, #bulkDeleteBtn').prop('disabled', count === 0);
    }

    // Update selected rows style
    function updateSelectedRowsStyle() {
        $('.item-checkbox').each(function() {
            const itemId = $(this).val();
            $(`#row-${itemId}`).toggleClass('selected-row', this.checked);
        });
    }

    // Helper functions
    function getStatusLabel(status) {
        const map = {
            'available': 'Available',
            'in_use': 'In Use',
            'maintenance': 'Maintenance',
            'reserved': 'Reserved',
            'lost': 'Lost',
            'damaged': 'Damaged'
        };
        return map[status] || status || 'Available';
    }

    function getStatusClass(status) {
        const map = {
            'available': 'status-available',
            'in_use': 'status-in_use',
            'maintenance': 'status-maintenance',
            'reserved': 'status-reserved',
            'lost': 'status-lost',
            'damaged': 'status-damaged'
        };
        return map[status] || 'status-available';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Notification system
    let activeNotification = null;

    function showNotification(type, message, duration = 5000) {
        if (activeNotification) {
            activeNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show alert-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" onclick="hideNotification()"></button>
        `;

        document.body.appendChild(notification);
        activeNotification = notification;

        if (duration > 0) {
            setTimeout(hideNotification, duration);
        }
    }

    function hideNotification() {
        if (activeNotification) {
            activeNotification.remove();
            activeNotification = null;
        }
    }

    // Export functions
    function exportToExcel() {
        const params = buildFilterParams();
        window.open(`${BASE_URL}api/items/export.php?format=excel&${params}`, '_blank');
    }

    function exportToPDF() {
        const params = buildFilterParams();
        window.open(`${BASE_URL}api/items/export.php?format=pdf&${params}`, '_blank');
    }

    function exportToCSV() {
        const params = buildFilterParams();
        window.open(`${BASE_URL}api/items/export.php?format=csv&${params}`, '_blank');
    }

    function buildFilterParams() {
        const params = new URLSearchParams({
            search: $('#globalSearch').val(),
            category: $('#filterCategory').val(),
            status: $('#filterStatus').val(),
            location: $('#filterLocation').val(),
            condition: $('#filterCondition').val()
        });
        return params.toString();
    }

    function printTable() {
        window.print();
    }

    async function showQuickStats() {
        // ... keep your existing stats function ...
        showNotification('info', 'Statistics feature coming soon', 2000);
    }

    function showQRScanner() {
        window.location.href = `${BASE_URL}scan.php`;
    }

    // Item actions
    function confirmDelete(id, name) {
        if (confirm(`Delete "${name}"?`)) {
            deleteItem(id);
        }
    }

    async function deleteItem(id) {
        try {
            showNotification('info', 'Deleting item...');

            const response = await fetch(`${BASE_URL}api/items/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id
                })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', 'Item deleted successfully');
                selectedItems.delete(id.toString());
                loadItems();
            } else {
                showNotification('error', data.message || 'Failed to delete');
            }
        } catch (error) {
            showNotification('error', 'Network error');
        }
    }

    async function checkOutMultiple() {
        if (selectedItems.size === 0) return;
        if (!confirm(`Check out ${selectedItems.size} selected item(s)?`)) return;

        try {
            showNotification('info', `Checking out ${selectedItems.size} item(s)...`);

            const response = await fetch(`${BASE_URL}api/items/bulk_update.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ids: Array.from(selectedItems),
                    status: 'in_use'
                })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', `Successfully checked out ${selectedItems.size} item(s)`);
                selectedItems.clear();
                $('#selectAll').prop('checked', false);
                loadItems();
            } else {
                showNotification('error', data.message || 'Failed to check out items');
            }
        } catch (error) {
            showNotification('error', 'Network error');
        }
    }

    async function deleteMultiple() {
        if (selectedItems.size === 0) return;
        if (!confirm(`Delete ${selectedItems.size} selected item(s)?`)) return;

        try {
            showNotification('info', `Deleting ${selectedItems.size} item(s)...`);

            const response = await fetch(`${BASE_URL}api/items/bulk_delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ids: Array.from(selectedItems)
                })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', `Successfully deleted ${selectedItems.size} item(s)`);
                selectedItems.clear();
                $('#selectAll').prop('checked', false);
                loadItems();
            } else {
                showNotification('error', data.message || 'Failed to delete items');
            }
        } catch (error) {
            showNotification('error', 'Network error');
        }
    }
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>