<script>
    // ========== GLOBAL VARIABLES ==========
    let originalEditModalHtml = '';
    let selectedItems = [];
    let currentFilters = {};
    let currentPage = 1;
    let totalPages = 1;
    let totalItems = 0;
    let itemsPerPage = 5;

    // Add this near the top of your script, after your global variables
    const categoryMap = {
        '0': 'Uncategorized',
        '1': 'CAMERA - ROBOTIC',
        '2': 'Audio Equipment',
        '3': 'Video Equipment',
        '4': 'Lighting',
        '5': 'Accessories',
        // Add more as you discover them
    };

    const departmentMap = {
        '0': 'Not Assigned',
        '1': 'Audio Department',
        '2': 'Video Department',
        '3': 'Production',
        '4': 'Post-Production',
        '5': 'Maintenance',
        // Add more as you discover them
    };

    // Helper function to get all unique categories/departments from your data
    function discoverMappings() {
        $.get('api/items/list.php?limit=1000', function(data) {
            if (data.success && data.items) {
                const categories = new Set();
                const departments = new Set();

                data.items.forEach(item => {
                    if (item.category) categories.add(item.category);
                    if (item.department) departments.add(item.department);
                });

                console.log('Found categories:', Array.from(categories));
                console.log('Found departments:', Array.from(departments));
            }
        });
    }

    // Run this once to discover your actual values
    discoverMappings();


    // Add BASE_URL - make sure this matches your actual base URL
    const BASE_URL = '<?php echo BASE_URL ?? "/ability_app_main/"; ?>';

    // ========== QUICK SEARCH FUNCTIONS ==========

    let searchTimeout = null;

    // Add this near the top of your file
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error('AJAX Error:', {
            url: settings.url,
            status: jqxhr.status,
            statusText: jqxhr.statusText,
            responseText: jqxhr.responseText,
            error: thrownError
        });

        // Don't show toastr for every error - only for user-triggered actions
        if (settings.url && settings.url.includes('list.php')) {
            // Silently log DataTable errors
            return;
        }

        toastr.error(`Error loading data from ${settings.url}`);
    });

    // Load quick stats
    function loadQuickStats() {
        console.log('Loading quick stats...'); // Debug log

        $.ajax({
            url: 'api/get_dashboard_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Stats response:', response); // Debug log

                if (response.success && response.stats) {
                    console.log('Stats data:', response.stats); // Debug log
                    updateQuickStats(response.stats);
                } else {
                    console.log('No stats in response:', response);
                    // Set default values
                    updateQuickStats({
                        total: 0,
                        available: 0,
                        in_use: 0,
                        maintenance: 0,
                        categories: []
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Could not load stats:', error);
                console.error('Response:', xhr.responseText);

                // Set default values on error
                updateQuickStats({
                    total: 0,
                    available: 0,
                    in_use: 0,
                    maintenance: 0,
                    categories: []
                });
            }
        });
    }

    // Update stats display
    function updateQuickStats(stats) {
        $('#totalItemsStat').text(stats.total || 0);
        $('#availableStat').text(stats.available || 0);
        $('#inUseStat').text(stats.in_use || 0);
        $('#maintenanceStat').text(stats.maintenance || 0);

        // Update categories
        if (stats.categories && stats.categories.length > 0) {
            let categoriesHtml = '';
            stats.categories.forEach(cat => {
                categoriesHtml += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>${escapeHtml(cat.category)}</span>
                        <span class="badge bg-secondary rounded-pill">${cat.count}</span>
                    </div>
                `;
            });
            $('#popularCategories').html(categoriesHtml);
        } else {
            $('#popularCategories').html('<p class="text-muted small mb-0">No categories found</p>');
        }
    }

    // Initialize quick search when modal opens
    $('#quickSearchModal').on('show.bs.modal', function() {
        clearQuickSearch();
        loadQuickStats(); // Load stats when modal opens
        setTimeout(() => $('#quickSearchInput').focus(), 300);
    });

    // Search input handler with debounce
    $('#quickSearchInput').on('input', function() {
        const term = $(this).val().trim();

        if (searchTimeout) clearTimeout(searchTimeout);

        if (term.length < 2) {
            $('#quickSearchResults').hide();
            $('#quickStatsSection').show();
            return;
        }

        searchTimeout = setTimeout(performQuickSearch, 300);
    });

    // Search button click
    $('#quickSearchBtn').on('click', performQuickSearch);

    // Enter key handler
    $('#quickSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            performQuickSearch();
        }
    });

    // Perform search
    function performQuickSearch() {
        const searchTerm = $('#quickSearchInput').val().trim();

        if (searchTerm.length < 2) return;

        // Show results section with loading
        $('#quickStatsSection').hide();
        $('#quickSearchResults').show();
        $('#searchResultsContainer').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="text-muted">Searching...</p>
            </div>
        `);

        // Make AJAX call
        $.ajax({
            url: 'api/quick_search.php',
            method: 'GET',
            data: {
                q: searchTerm
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.items && response.items.length > 0) {
                        displaySearchResults(response.items);
                        $('#searchResultCount').text(response.items.length);
                    } else {
                        $('#searchResultCount').text('0');
                        $('#searchResultsContainer').html(`
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h6>No items found</h6>
                                <p class="small">Try different keywords</p>
                            </div>
                        `);
                    }

                    // Update stats if provided
                    if (response.stats) {
                        updateQuickStats(response.stats);
                    }
                }
            },
            error: function() {
                $('#searchResultsContainer').html(`
                    <div class="text-center py-5 text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p>Search failed. Please try again.</p>
                    </div>
                `);
            }
        });
    }

    // Display search results
    function displaySearchResults(items) {
        let html = '<div class="list-group">';

        items.forEach(item => {
            const statusClass = `status-${item.status ? item.status.toLowerCase() : 'unknown'}`;

            html += `
                <div class="list-group-item list-group-item-action" onclick="quickViewItem(${item.id})" style="cursor: pointer;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                ${item.image ? 
                                    `<img src="${item.image}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">` : 
                                    `<div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-box text-muted"></i>
                                    </div>`
                                }
                                <div>
                                    <strong class="d-block">${escapeHtml(item.item_name)}</strong>
                                    <small class="text-muted">
                                        <i class="fas fa-barcode me-1"></i>${escapeHtml(item.serial_number || 'N/A')}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-end align-items-center">
                                <div class="text-end me-3">
                                    <span class="status-badge ${statusClass}">${escapeHtml(item.status || 'Unknown')}</span>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(item.stock_location || 'N/A')}
                                    </small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>
                    ${item.description ? `<small class="text-muted d-block mt-2">${escapeHtml(item.description.substring(0, 100))}${item.description.length > 100 ? '...' : ''}</small>` : ''}
                </div>
            `;
        });

        html += '</div>';
        $('#searchResultsContainer').html(html);
    }

    // Quick view item from search
    function quickViewItem(itemId) {
        $('#quickSearchModal').modal('hide');

        setTimeout(() => {
            const quickBtn = $(`.quick-view-btn[data-item-id="${itemId}"]`);
            if (quickBtn.length) {
                quickBtn.click();
            } else {
                openViewItemModal(itemId);
            }
        }, 300);
    }

    // Clear search
    function clearQuickSearch() {
        $('#quickSearchInput').val('');
        $('#quickSearchResults').hide();
        $('#quickStatsSection').show();
        loadQuickStats(); // Refresh stats when clearing
        if (searchTimeout) clearTimeout(searchTimeout);
    }

    // Escape HTML helper (SINGLE DEFINITION)
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========== HELPER FUNCTIONS ==========

    function formatDateTime(dt) {
        if (!dt) return 'N/A';
        try {
            const date = new Date(dt);
            if (isNaN(date.getTime())) return dt;
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dt;
        }
    }

    // ========== FETCH DATA FUNCTIONS ==========

    function fetchItemData(itemId) {
        return new Promise(function(resolve, reject) {
            console.log('========== FETCH ITEM DATA START ==========');
            console.log('Fetching item data for ID:', itemId);
            console.log('Item ID type:', typeof itemId);
            console.log('Item ID value:', itemId);

            // Validate itemId
            if (!itemId || isNaN(parseInt(itemId))) {
                console.error('Invalid item ID:', itemId);
                reject(new Error('Invalid item ID'));
                return;
            }

            // Make sure itemId is a number
            const numericId = parseInt(itemId);
            console.log('Numeric ID:', numericId);

            $.ajax({
                url: 'api/get_item.php',
                method: 'GET',
                data: {
                    id: numericId
                },
                dataType: 'json',
                timeout: 10000,
                beforeSend: function() {
                    console.log('Sending AJAX request to api/get_item.php with ID:', numericId);
                },
                success: function(response) {
                    console.log('✅ AJAX success - Raw response:', response);
                    console.log('Response type:', typeof response);
                    console.log('Response success flag:', response?.success);
                    console.log('Response data:', response?.data);

                    if (response && response.data) {
                        console.log('Description in response:', response.data.description);
                        console.log('Description type:', typeof response.data.description);
                    }

                    if (response && response.success === true && response.data) {
                        const data = response.data;
                        console.log('Processing data object:', data);

                        // Log all keys in data
                        console.log('Data keys:', Object.keys(data));

                        // Check specifically for description
                        console.log('description exists:', data.hasOwnProperty('description'));
                        console.log('description value before cleanup:', data.description);
                        console.log('description type before cleanup:', typeof data.description);

                        // Clean up undefined/null values
                        Object.keys(data).forEach(key => {
                            const originalValue = data[key];
                            if (data[key] === 'undefined' || data[key] === undefined || data[key] === null) {
                                data[key] = '';
                                console.log(`Cleaned up ${key}: ${originalValue} -> ''`);
                            }
                        });

                        // Ensure description is a string
                        if (!data.hasOwnProperty('description') || data.description === undefined || data.description === null) {
                            console.log('description was missing or null, setting to empty string');
                            data.description = '';
                        }

                        console.log('Final processed description:', data.description);
                        console.log('Final processed description type:', typeof data.description);
                        console.log('========== FETCH ITEM DATA END (SUCCESS) ==========');

                        resolve(data);
                    } else {
                        const errorMsg = response?.message || 'Failed to load item';
                        console.error('❌ API returned error:', errorMsg);
                        console.log('Full error response:', response);
                        console.log('========== FETCH ITEM DATA END (ERROR) ==========');
                        reject(new Error(errorMsg));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ AJAX error - Status:', status);
                    console.error('Error:', error);
                    console.error('XHR Response Text:', xhr.responseText);
                    console.error('XHR Status:', xhr.status);
                    console.error('XHR Status Text:', xhr.statusText);

                    let errorMessage = 'Network error: ' + error;
                    try {
                        // Try to parse error response if it's JSON
                        if (xhr.responseText) {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                            console.error('Parsed error response:', errorResponse);
                        }
                    } catch (e) {
                        console.error('Could not parse error response as JSON');
                    }

                    console.log('========== FETCH ITEM DATA END (NETWORK ERROR) ==========');
                    reject(new Error(errorMessage));
                }
            });
        });
    }

    // ========== DROPDOWN LOADING FUNCTIONS ==========

    function loadCategoriesDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_categories.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editCategory');
                    select.empty().append('<option value="">Select Category</option>');
                    if (response.success && response.categories) {
                        response.categories.forEach(c => select.append(`<option value="${c.id}">${c.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    function loadDepartmentsDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_departments.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editDepartment');
                    select.empty().append('<option value="">Select Department</option>');
                    if (response.success && response.departments) {
                        response.departments.forEach(d => select.append(`<option value="${d.id}">${d.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    function loadAccessoriesDropdown() {
        return new Promise(resolve => {
            $.ajax({
                url: 'api/get_accessories.php',
                method: 'GET',
                success: function(response) {
                    const select = $('#editAccessories');
                    select.empty().append('<option value="">-- No Accessories --</option>');
                    if (response.success && response.accessories) {
                        response.accessories.forEach(a => select.append(`<option value="${a.id}">${a.name}</option>`));
                    }
                    resolve();
                },
                error: () => resolve()
            });
        });
    }

    // ========== BRAND DROPDOWN LOADING FUNCTION ==========
    function loadBrandsDropdown() {
        return new Promise(resolve => {
            const select = $('#editBrand');

            // If already populated, just resolve
            if (select.find('option').length > 1) {
                console.log('Brand dropdown already populated');
                resolve();
                return;
            }

            // Check if brands are already in the HTML from server-side
            if (select.find('option[value="1"]').length > 0) {
                console.log('Brand dropdown already has options from HTML');
                resolve();
                return;
            }

            // Hardcoded list of common camera/video brands as a quick fix
            console.log('Loading brands dropdown with hardcoded values');

            // Clear and populate with hardcoded brands
            select.empty().append('<option value="">Select Brand</option>');
            select.append('<option value="0">-- No Brand / Unbranded --</option>');

            // Common camera/video equipment brands
            const brands = [{
                    id: 1,
                    name: 'Sony'
                },
                {
                    id: 2,
                    name: 'Canon'
                },
                {
                    id: 3,
                    name: 'Nikon'
                },
                {
                    id: 4,
                    name: 'Panasonic'
                },
                {
                    id: 5,
                    name: 'Blackmagic Design'
                },
                {
                    id: 6,
                    name: 'ARRI'
                },
                {
                    id: 7,
                    name: 'RED'
                },
                {
                    id: 8,
                    name: 'Fujifilm'
                },
                {
                    id: 9,
                    name: 'Leica'
                },
                {
                    id: 10,
                    name: 'Zeiss'
                },
                {
                    id: 11,
                    name: 'Sigma'
                },
                {
                    id: 12,
                    name: 'Tamron'
                },
                {
                    id: 13,
                    name: 'GoPro'
                },
                {
                    id: 14,
                    name: 'DJI'
                },
                {
                    id: 15,
                    name: 'Manfrotto'
                },
                {
                    id: 16,
                    name: 'Sennheiser'
                },
                {
                    id: 17,
                    name: 'Shure'
                },
                {
                    id: 18,
                    name: 'Rode'
                },
                {
                    id: 19,
                    name: 'Atomos'
                },
                {
                    id: 20,
                    name: 'SmallHD'
                }
            ];

            brands.forEach(b => {
                select.append(`<option value="${b.id}">${b.name}</option>`);
            });

            resolve();
        });
    }
    // ========== ACCESSORY DISPLAY FUNCTIONS ==========

    function updateEditSelectedAccessories() {
        const selected = $('#editAccessories option:selected');
        const container = $('#editSelectedAccessories');

        if (selected.length === 0 || !selected.val()) {
            container.html('<p class="text-muted small mb-0">None selected</p>');
            return;
        }

        let html = '';
        selected.each(function() {
            if ($(this).val()) {
                html += `<span class="badge bg-info me-1 mb-1">${$(this).text()}</span>`;
            }
        });
        container.html(html || '<p class="text-muted small mb-0">None selected</p>');
    }

    function updateSelectedAccessories() {
        const selectedContainer = $('#selectedAccessories');
        const selected = $('#accessories').find('option:selected');

        if (selected.length === 0 || (selected.length === 1 && !selected.val())) {
            selectedContainer.html('<p class="text-muted mb-0">No accessories selected</p>');
            return;
        }

        let html = '<div class="d-flex flex-wrap gap-1">';
        selected.each(function() {
            if ($(this).val()) {
                const accessoryName = $(this).text().split(' (')[0];
                html += `
                    <span class="badge bg-info cursor-pointer me-1 mb-1 accessory-badge" 
                          data-value="${$(this).val()}">
                        ${accessoryName}
                        <i class="fas fa-times ms-1"></i>
                    </span>
                `;
            }
        });
        html += '</div>';
        selectedContainer.html(html);
    }

    // ========== QR CODE FUNCTIONS ==========

    function updateEditQRCode(qrCode) {
        const container = $('#editQRCode');
        if (qrCode && qrCode !== '') {
            container.html(`<img src="${qrCode}" alt="QR Code" class="img-fluid" style="max-width: 150px;">`);
        } else {
            container.html('<p class="text-muted mb-0">No QR Code</p>');
        }
    }

    function updateViewQRCode(qrCode, itemName, serial) {
        const qrContainer = $('#viewQRCode');
        qrContainer.empty();

        if (qrCode && qrCode !== '' && qrCode !== 'pending') {
            qrContainer.html(`
                <img src="${qrCode}" alt="QR Code" 
                     style="width: 150px; height: 150px;" 
                     class="img-fluid border rounded">
                <div class="mt-2 small">${itemName || 'QR Code'}</div>
            `);
        } else if (qrCode === 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 small">Generating QR Code...</div>
                </div>
            `);
        } else {
            qrContainer.html(`
                <div class="text-center">
                    <i class="fas fa-qrcode fa-3x text-muted mb-2"></i>
                    <div class="small text-muted">No QR Code</div>
                </div>
            `);
        }
    }

    function downloadSingleQRCode(qrUrl, itemName, serial) {
        if (!qrUrl) {
            toastr.error('No QR code available to download');
            return;
        }

        const link = document.createElement('a');
        link.href = qrUrl;

        const safeName = (itemName || 'item')
            .replace(/[<>:"/\\|?*]/g, '_')
            .replace(/\s+/g, '_')
            .substring(0, 50);

        const safeSerial = (serial || 'item').replace(/[^a-z0-9]/gi, '_');
        link.download = `QR_${safeName}_${safeSerial}.png`;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toastr.success('QR Code downloaded!');
    }

    // ========== QR CODE GENERATION FUNCTIONS ==========

    function generateQRCodeForItem(itemId, itemName) {
        if (!itemId) {
            toastr.error('Invalid item ID');
            return;
        }

        if (confirm(`Generate QR Code for "${itemName}"?`)) {
            $.ajax({
                url: 'api/generate_qr.php',
                method: 'POST',
                data: {
                    item_id: itemId
                },
                dataType: 'json',
                beforeSend: function() {
                    toastr.info('Generating QR code...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('QR Code generated successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message || 'Failed to generate QR code');
                    }
                },
                error: function() {
                    toastr.error('Error generating QR code');
                }
            });
        }
    }

    function generateQRCodeFromQuickView(itemId, itemName) {
        generateQRCodeForItem(itemId, itemName);
    }

    function regenerateQRCode(itemId, itemName) {
        if (!itemId) return;

        if (!confirm(`Regenerate QR code for "${itemName}"? This will replace the existing QR code.`)) {
            return;
        }

        $('#editRegenerateQRBtn').html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');
        $('#editRegenerateQRBtn').prop('disabled', true);

        $.ajax({
            url: 'api/generate_qr.php',
            method: 'POST',
            data: {
                id: itemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('QR code regenerated successfully');
                    updateEditQRCode(response.qr_code);
                } else {
                    toastr.error(response.message || 'Failed to regenerate QR code');
                }
            },
            error: function() {
                toastr.error('Error regenerating QR code');
            },
            complete: function() {
                $('#editRegenerateQRBtn').html('<i class="fas fa-sync-alt me-1"></i> Regenerate QR Code');
                $('#editRegenerateQRBtn').prop('disabled', false);
            }
        });
    }

    // ========== QR CODE ZIP FUNCTIONS ==========

    function generateAndDownloadQRZipWithProgress() {
        // Disable the button to prevent multiple clicks
        const button = document.querySelector('button[onclick*="generateAndDownloadQRZipWithProgress"]');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            button.disabled = true;
        }

        toastr.info('Starting QR code generation...');

        // Create a simple progress modal
        const modalHtml = `
            <div class="modal fade" id="qrProcessingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Generating QR Codes</h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Generating QR codes for all items...</p>
                                <p class="text-muted small">This may take a few moments.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        // Remove existing modal if any
        $('#qrProcessingModal').remove();
        $('body').append(modalHtml);

        const modal = new bootstrap.Modal(document.getElementById('qrProcessingModal'));
        modal.show();

        // Make the AJAX call
        $.ajax({
            url: 'api/quick_qr_zip.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);

                // Close modal first
                modal.hide();
                setTimeout(() => {
                    $('#qrProcessingModal').remove();
                }, 300);

                if (response.success) {
                    toastr.success(response.message);

                    // Trigger download immediately
                    if (response.download_url) {
                        const link = document.createElement('a');
                        link.href = response.download_url;
                        link.download = response.filename || 'qr_codes.zip';
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();

                        // Clean up after a short delay
                        setTimeout(() => {
                            document.body.removeChild(link);
                            toastr.success('Download started! Check your downloads folder.');
                        }, 100);
                    }
                } else {
                    toastr.error(response.message || 'Failed to generate QR codes');
                }

                // Re-enable button
                if (button) {
                    button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                    button.disabled = false;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.log('Response:', xhr.responseText);

                modal.hide();
                $('#qrProcessingModal').remove();

                let errorMessage = 'Failed to generate QR codes';

                try {
                    // Try to parse as JSON
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        errorMessage = jsonResponse.message;
                    }
                } catch (e) {
                    // If not JSON, show raw error
                    errorMessage = 'Server error: ' + error;
                }

                toastr.error(errorMessage);

                // Re-enable button
                if (button) {
                    button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                    button.disabled = false;
                }
            }
        });
    }

    function generateAndDownloadQRZip() {
        // Create confirmation modal HTML
        const confirmModalHtml = `
            <div class="modal fade" id="qrConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Generate QR Codes</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                                <p class="lead">This will generate QR codes for all items and create a ZIP file.</p>
                                <p class="text-muted">This may take a few moments.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-primary" id="confirmGenerateBtn">
                                <i class="fas fa-play me-1"></i> Continue
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;

        // Remove existing modal if any
        $('#qrConfirmModal').remove();
        $('body').append(confirmModalHtml);

        const confirmModal = new bootstrap.Modal(document.getElementById('qrConfirmModal'));
        confirmModal.show();

        // Handle confirmation button click
        $('#confirmGenerateBtn').off('click').on('click', function() {
            confirmModal.hide();
            $('#qrConfirmModal').remove();
            proceedWithQRGeneration();
        });

        // Handle modal close
        $('#qrConfirmModal').on('hidden.bs.modal', function() {
            $('#qrConfirmModal').remove();
            toastr.info('Operation cancelled.');
        });

        function proceedWithQRGeneration() {
            // Disable the button to prevent multiple clicks
            const button = document.querySelector('button[onclick*="generateAndDownloadQRZip"]');
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
                button.disabled = true;
            }

            // Create progress modal
            const modalHtml = `
                <div class="modal fade" id="qrZipProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Generating QR Codes & ZIP</h5>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <span id="qrZipProgressText">Initializing...</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div id="qrZipProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <div>Status: <span id="qrZipStatus">Preparing...</span></div>
                                    <div>Progress: <span id="qrZipProgress">0%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            // Remove existing modal if any
            $('#qrZipProgressModal').remove();
            $('body').append(modalHtml);

            const modal = new bootstrap.Modal(document.getElementById('qrZipProgressModal'));
            modal.show();

            // Update progress
            $('#qrZipProgressText').text('Starting QR code generation...');
            $('#qrZipStatus').text('Connecting to server...');

            // Start the process
            $.ajax({
                url: 'api/generate_all_qr_codes.php',
                method: 'POST',
                dataType: 'json',
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();

                    // Track progress
                    xhr.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $('#qrZipProgressBar').css('width', percentComplete + '%')
                                .text(Math.round(percentComplete) + '%');
                            $('#qrZipProgress').text(Math.round(percentComplete) + '%');

                            if (percentComplete < 100) {
                                $('#qrZipProgressText').text('Processing...');
                                $('#qrZipStatus').text('Uploading: ' + Math.round(percentComplete) + '%');
                            }
                        }
                    });

                    return xhr;
                },
                beforeSend: function() {
                    $('#qrZipProgressText').text('Sending request to server...');
                },
                success: function(response) {
                    console.log('Server response:', response);

                    if (response.success) {
                        $('#qrZipProgressText').text('Processing complete!');
                        $('#qrZipProgressBar').css('width', '100%').text('100%').removeClass('progress-bar-animated');
                        $('#qrZipStatus').text('Creating download...');
                        $('#qrZipProgress').text('100%');

                        setTimeout(() => {
                            modal.hide();
                            $('#qrZipProgressModal').remove();

                            toastr.success(response.message);

                            // Trigger download
                            if (response.download_url) {
                                const downloadLink = document.createElement('a');
                                downloadLink.href = response.download_url;
                                downloadLink.download = response.filename || 'qr_codes.zip';
                                downloadLink.target = '_blank';
                                document.body.appendChild(downloadLink);
                                downloadLink.click();
                                document.body.removeChild(downloadLink);

                                toastr.success('Download started! Check your downloads folder.');
                            }

                            // Refresh page after a delay
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        }, 1000);
                    } else {
                        modal.hide();
                        $('#qrZipProgressModal').remove();
                        toastr.error(response.message || 'Failed to generate QR codes');
                    }

                    // Re-enable button
                    if (button) {
                        button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                        button.disabled = false;
                    }
                },
                error: function(xhr, status, error) {
                    modal.hide();
                    $('#qrZipProgressModal').remove();

                    console.error('Error details:', error);
                    console.error('XHR response:', xhr.responseText);

                    let errorMessage = 'Failed to generate QR codes';

                    try {
                        // Try to parse error response
                        if (xhr.responseText) {
                            // Check if it's HTML error
                            if (xhr.responseText.includes('<br') || xhr.responseText.includes('<b>')) {
                                // Extract just the error message
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = xhr.responseText;
                                const text = tempDiv.textContent || tempDiv.innerText || '';

                                // Find the actual error message
                                const lines = text.split('\n').filter(line => line.trim());
                                errorMessage = lines.length > 0 ? lines[0].substring(0, 200) : 'Server error occurred';
                            } else {
                                // Try to parse as JSON
                                const jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse && jsonResponse.message) {
                                    errorMessage = jsonResponse.message;
                                }
                            }
                        }
                    } catch (e) {
                        errorMessage = xhr.statusText || 'Server error';
                    }

                    toastr.error('Error: ' + errorMessage);

                    // Re-enable button
                    if (button) {
                        button.innerHTML = '<i class="fas fa-file-archive me-1"></i> Generate & Download ZIP';
                        button.disabled = false;
                    }
                }
            });
        }
    }

    function downloadAllQRCodes() {
        // Show loading notification
        toastr.info('Fetching all items from database...', 'Processing', {
            timeOut: 0,
            extendedTimeOut: 0
        });

        // Show loading state in the button
        const button = document.querySelector('button[onclick="downloadAllQRCodes()"]');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Fetching items...';
            button.disabled = true;
        }

        // Fetch all items from API - use relative URL instead of BASE_URL
        fetch('api/items/list.php?limit=1000&page=1')
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.items || data.items.length === 0) {
                    toastr.error('No items found in database');
                    resetDownloadButton();
                    return;
                }

                const items = data.items;
                const totalItems = items.length;

                toastr.clear();
                toastr.info(`Found ${totalItems} items. Preparing QR codes...`, 'Processing');

                // Filter items that have QR codes
                const itemsWithQR = items.filter(item => item.qr_code && item.qr_code !== '' && item.qr_code !== 'pending');

                if (itemsWithQR.length === 0) {
                    toastr.warning('No QR codes found in database');
                    resetDownloadButton();
                    return;
                }

                toastr.info(`Found ${itemsWithQR.length} items with QR codes. Starting download...`, 'Processing');

                // Download each QR code with delay
                let downloaded = 0;
                const promises = [];

                itemsWithQR.forEach((item, index) => {
                    promises.push(new Promise((resolve) => {
                        setTimeout(() => {
                            try {
                                // Make sure the QR code URL is absolute
                                let qrUrl = item.qr_code;

                                // If it's a relative path, make it absolute
                                if (qrUrl && !qrUrl.startsWith('http')) {
                                    // Get the base URL from the current location
                                    const baseUrl = window.location.origin + '/ability_app_main/';
                                    qrUrl = baseUrl + qrUrl.replace(/^\//, '');
                                }

                                const safeName = (item.item_name || 'item')
                                    .replace(/[<>:"/\\|?*]/g, '')
                                    .replace(/\s+/g, '_')
                                    .substring(0, 50);

                                const safeSerial = (item.serial_number || 'item').replace(/[^a-z0-9]/gi, '_');
                                const filename = `QR_${safeName}_${safeSerial}.png`;

                                const link = document.createElement('a');
                                link.href = qrUrl;
                                link.download = filename;
                                link.target = '_blank';

                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);

                                downloaded++;
                                console.log(`Downloaded (${downloaded}/${itemsWithQR.length}): ${item.item_name}`);
                                resolve();
                            } catch (error) {
                                console.error(`Error downloading ${item.item_name}:`, error);
                                resolve();
                            }
                        }, index * 300); // 300ms delay between downloads
                    }));
                });

                Promise.all(promises).then(() => {
                    toastr.success(`Successfully downloaded ${downloaded} QR codes!`);
                    resetDownloadButton();
                });
            })
            .catch(error => {
                console.error('Error fetching items:', error);
                toastr.error('Failed to fetch items: ' + error.message);
                resetDownloadButton();
            });

        function resetDownloadButton() {
            if (button) {
                button.innerHTML = '<i class="fas fa-download me-1"></i> Download All QR Codes';
                button.disabled = false;
            }
        }
    }

    // ========== CHART FUNCTIONS (SINGLE DEFINITION) ==========

    function initializeStatusChart() {
        console.log('Initializing status chart with real data...');

        const canvas = document.getElementById('statusChart');
        if (!canvas) {
            console.log('Chart canvas (#statusChart) not found - skipping chart initialization');
            return;
        }

        // Destroy existing chart if it exists
        if (window.statusChartInstance) {
            window.statusChartInstance.destroy();
        }

        // Initialize with default values first
        let statusLabels = ['Available', 'In Use', 'Maintenance', 'Disposed'];
        let statusCounts = [0, 0, 0, 0];
        let statusColors = ['rgba(40, 167, 69, 0.8)', 'rgba(0, 123, 255, 0.8)', 'rgba(255, 193, 7, 0.8)', 'rgba(220, 53, 69, 0.8)'];
        let statusPercentages = [0, 0, 0, 0];

        // Try to get data from PHP if available (via data attributes or separate API call)
        $.ajax({
            url: 'api/get_status_chart_data.php',
            method: 'GET',
            dataType: 'json',
            async: false, // Make it synchronous for initialization
            success: function(response) {
                if (response.success && response.data) {
                    statusLabels = response.data.labels || statusLabels;
                    statusCounts = response.data.counts || statusCounts;
                    statusColors = response.data.colors || statusColors;
                    statusPercentages = response.data.percentages || statusPercentages;
                }
            },
            error: function() {
                console.log('Using default chart data');
            }
        });

        // Create border colors from background colors
        const borderColors = statusColors.map(color => {
            return color.replace('0.8', '1');
        });

        const chartData = {
            labels: statusLabels,
            datasets: [{
                label: 'Equipment Count',
                data: statusCounts,
                backgroundColor: statusColors,
                borderColor: borderColors,
                borderWidth: 2,
                hoverOffset: 15,
                borderRadius: 8
            }]
        };

        // Add percentage data to dataset
        chartData.datasets[0].percentageData = statusPercentages;

        // Create the chart
        window.statusChartInstance = new Chart(canvas, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels && data.labels.length && data.datasets && data.datasets.length) {
                                    return data.labels.map(function(label, i) {
                                        const meta = chart.getDatasetMeta(0);
                                        const style = meta.controller.getStyle(i);
                                        const value = data.datasets[0].data[i] || 0;
                                        const total = data.datasets[0].data.reduce((a, b) => (a || 0) + (b || 0), 0);
                                        const percentage = data.datasets[0].percentageData?.[i] ||
                                            (total > 0 ? ((value / total) * 100).toFixed(1) : 0);

                                        return {
                                            text: `${label}: ${value} (${percentage}%)`,
                                            fillStyle: style.backgroundColor,
                                            strokeStyle: style.borderColor,
                                            lineWidth: style.borderWidth,
                                            hidden: isNaN(data.datasets[0].data[i]) || (meta.data && meta.data[i] && meta.data[i].hidden),
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => (a || 0) + (b || 0), 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} items (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: function(chart) {
                    const data = chart.data;
                    const dataset = data.datasets[0];

                    if (dataset && dataset.data && dataset.data.length > 0) {
                        const isRealData = data.labels &&
                            (data.labels.length > 1 ||
                                (data.labels.length === 1 && data.labels[0] !== 'No Data'));

                        if (isRealData) {
                            const width = chart.width;
                            const height = chart.height;
                            const ctx = chart.ctx;
                            const total = dataset.data.reduce((a, b) => (a || 0) + (b || 0), 0);

                            ctx.restore();
                            ctx.font = "bold 16px 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
                            ctx.textBaseline = "middle";
                            ctx.fillStyle = "#233643";

                            const text = total.toString();
                            const textX = Math.round((width - ctx.measureText(text).width) / 2);
                            const textY = height / 2;

                            ctx.fillText(text, textX, textY - 10);

                            ctx.font = "12px 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
                            const subtitle = "Total Items";
                            const subtitleX = Math.round((width - ctx.measureText(subtitle).width) / 2);
                            ctx.fillText(subtitle, subtitleX, textY + 10);

                            ctx.save();
                        }
                    }
                }
            }]
        });

        console.log('✅ Status chart created with real data');
    }

    function refreshStatusChart(event) {
        // Fix: Add event parameter and handle it safely
        const refreshBtn = event?.target?.closest('button');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            refreshBtn.disabled = true;
        }

        toastr.info('Refreshing status data...');

        $.ajax({
            url: 'api/get_status_chart_data.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    if (window.statusChartInstance && response.data) {
                        // Safely update chart with null checks
                        window.statusChartInstance.data.labels = response.data.labels || window.statusChartInstance.data.labels;
                        window.statusChartInstance.data.datasets[0].data = response.data.counts || window.statusChartInstance.data.datasets[0].data;
                        window.statusChartInstance.data.datasets[0].backgroundColor = response.data.colors || window.statusChartInstance.data.datasets[0].backgroundColor;
                        window.statusChartInstance.data.datasets[0].borderColor = response.data.colors ?
                            response.data.colors.map(color => color.replace('0.8', '1')) :
                            window.statusChartInstance.data.datasets[0].borderColor;
                        window.statusChartInstance.data.datasets[0].percentageData = response.data.percentages || window.statusChartInstance.data.datasets[0].percentageData;
                        window.statusChartInstance.update();
                    }

                    if (response.data && response.data.table_html) {
                        $('#statusTableBody').html(response.data.table_html);
                    }

                    if (response.data && response.data.total) {
                        $('#totalEquipmentCount').text(response.data.total);
                    }

                    toastr.success('Status data refreshed!');
                } else {
                    toastr.error(response?.message || 'Failed to refresh data');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error refreshing chart:', error);
                toastr.error('Error refreshing status data');
            },
            complete: function() {
                if (refreshBtn) {
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                    refreshBtn.disabled = false;
                }
            }
        });
    }


    // ========== CLOCK & CALENDAR FUNCTIONS ==========

    function updateDateTime() {
        const now = new Date();

        // Get timezone name
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const timezoneParts = timezone.split('/');
        const timezoneDisplay = timezoneParts[timezoneParts.length - 1].replace(/_/g, ' ');

        const timezoneEl = document.getElementById('timezone');
        if (timezoneEl) timezoneEl.textContent = timezoneDisplay;

        // Format day of week
        const dayOfWeek = now.toLocaleDateString('en-US', {
            weekday: 'long'
        });
        const dayEl = document.getElementById('digitalDay');
        if (dayEl) dayEl.textContent = dayOfWeek;

        // Format date
        const formattedDate = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        const dateEl = document.getElementById('digitalDate');
        if (dateEl) dateEl.textContent = formattedDate;

        // Format full date for today display
        const todayDate = now.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        const todayEl = document.getElementById('todayDate');
        if (todayEl) todayEl.textContent = todayDate;

        // Format time
        let hours = now.getHours();
        let minutes = now.getMinutes();
        let seconds = now.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        // Convert to 12-hour format
        hours = hours % 12;
        hours = hours ? hours : 12;

        // Add leading zeros
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        const timeEl = document.getElementById('digitalTime');
        if (timeEl) timeEl.textContent = `${hours}:${minutes}:${seconds}`;

        const ampmEl = document.getElementById('amPm');
        if (ampmEl) ampmEl.textContent = ampm;

        // Update month calendar view
        updateMonthCalendar(now);
    }

    function updateMonthCalendar(date) {
        const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
        ];
        const dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        const currentMonth = date.getMonth();
        const currentYear = date.getFullYear();
        const today = date.getDate();

        // Update month/year header
        const monthEl = document.getElementById('currentMonth');
        if (monthEl) monthEl.textContent = `${monthNames[currentMonth]} ${currentYear}`;

        // Get first day of month and number of days
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();

        // Generate calendar grid
        let calendarHTML = '<table class="table table-sm table-borderless mb-0" style="margin: 0 auto; width: 100%;">';

        // Day headers
        calendarHTML += '<tr>';
        dayNames.forEach(day => {
            calendarHTML += `<th class="text-center small text-muted p-1 pb-2" style="width: 14.28%;">${day}</th>`;
        });
        calendarHTML += '</tr><tr>';

        // Empty cells for days before the 1st
        for (let i = 0; i < startingDay; i++) {
            calendarHTML += '<td class="p-1" style="width: 14.28%;"></td>';
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = (day === today);
            const cellClass = isToday ? 'bg-primary text-white rounded-circle' : '';
            const fontWeight = isToday ? 'fw-bold' : '';

            calendarHTML += `<td class="text-center p-1" style="width: 14.28%;">
                <span class="${cellClass} ${fontWeight}" 
                      style="display: inline-block; width: 24px; height: 24px; line-height: 24px; border-radius: 50%;">
                    ${day}
                </span>
            </td>`;

            // Start new row after Saturday
            if ((day + startingDay) % 7 === 0 && day < daysInMonth) {
                calendarHTML += '</tr><tr>';
            }
        }

        // Fill remaining empty cells in the last row
        const remainingCells = 7 - ((daysInMonth + startingDay) % 7);
        if (remainingCells < 7) {
            for (let i = 0; i < remainingCells; i++) {
                calendarHTML += '<td class="p-1" style="width: 14.28%;"></td>';
            }
        }

        calendarHTML += '</tr></table>';

        const calendarEl = document.getElementById('monthCalendar');
        if (calendarEl) calendarEl.innerHTML = calendarHTML;
    }

    // Initialize and start the clock
    function startClock() {
        updateDateTime();
        setInterval(updateDateTime, 1000);
    }

    // ========== EDIT MODAL FUNCTIONS ==========

    function saveOriginalEditModalHtml() {
        originalEditModalHtml = $('#editItemModal .modal-body').html();
        console.log('Original edit modal HTML saved');
    }

    function resetEditModal() {
        delete window.currentEditItemData;
        $('#editItemForm')[0].reset();
        $('#editCurrentImage').hide();
        $('#editImagePreview').hide();
        $('#editImageUploadSection').hide();
        $('#editChangeImage').prop('checked', false);
        $('#editSelectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
    }

    function setupEditFormHandlers(data) {
        $('#editChangeImage').off('change').on('change', function() {
            $('#editImageUploadSection').toggle($(this).is(':checked'));
        });

        $('#editItemImage').off('change').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    $('#editImagePreview').show().find('img').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        $('#editAccessories').off('change').on('change', updateEditSelectedAccessories);

        $('#editResetBtn').off('click').on('click', function() {
            if (confirm('Reset all changes?')) {
                populateEditItemModal(data);
            }
        });

        $('#editDeleteBtn').off('click').on('click', function() {
            if (confirm(`Delete "${data.item_name}"?`)) {
                deleteItem(data.id);
            }
        });

        $('#editRegenerateQRBtn').off('click').on('click', function() {
            regenerateQRCode(data.id, data.item_name);
        });
    }

    function populateEditItemModal(data) {
        console.log('Populating edit modal with:', data);

        $('#editItemModalLabel').html(`<i class="fas fa-edit me-2"></i>Edit: ${data.item_name || 'Item'}`);
        $('#editItemId').val(data.id || '');
        $('#editItemIdDisplay').text(data.id || '-');

        // Basic Information
        $('#editItemName').val(data.item_name || '');
        $('#editSerialNumber').val(data.serial_number || '');
        $('#editStatus').val(data.status || 'available');
        $('#editCondition').val(data.condition || 'good');

        // Brand & Model - LOAD BRANDS FIRST
        loadBrandsDropdown().then(function() {
            // Check if brand is a valid positive number
            if (data.brand && parseInt(data.brand) > 0) {
                $('#editBrand').val(data.brand);
            } else {
                // For brand 0, null, undefined, etc. - set to empty string
                $('#editBrand').val('');
            }
        });

        $('#editModel').val(data.model || '');

        // Location
        $('#editStockLocation').val(data.stock_location || '');
        $('#editStorageLocation').val(data.storage_location || '');

        // Quantity
        $('#editQuantity').val(data.quantity || 1);

        // Description, Specifications, Notes
        $('#editDescription').val(data.description || '');
        $('#editSpecifications').val(data.specifications || '');
        $('#editNotes').val(data.notes || '');

        // Tags
        $('#editTags').val(data.tags || '');

        // System Info
        $('#editCreatedAt').text(formatDateTime(data.created_at));
        $('#editUpdatedAt').text(formatDateTime(data.updated_at));
        $('#editLastScanned').text(data.last_scanned ? formatDateTime(data.last_scanned) : 'Never');

        // Load other dropdowns and set values
        loadCategoriesDropdown().then(function() {
            // Handle category 0 as well
            if (data.category && parseInt(data.category) > 0) {
                $('#editCategory').val(data.category);
            } else {
                $('#editCategory').val('');
            }
        });

        loadDepartmentsDropdown().then(function() {
            if (data.department) $('#editDepartment').val(data.department);
        });

        loadAccessoriesDropdown().then(function() {
            if (data.accessory_ids && data.accessory_ids.length) {
                // Filter out any 0 or invalid IDs
                const validIds = data.accessory_ids.filter(id => parseInt(id) > 0);
                $('#editAccessories').val(validIds);
            }
            updateEditSelectedAccessories();
        });

        // Image
        if (data.image && data.image !== '') {
            $('#editCurrentImage img').attr('src', data.image);
        } else {
            $('#editCurrentImage').html('<p class="text-muted small mb-0">No image</p>');
        }

        // QR Code
        updateEditQRCode(data.qr_code);

        // Setup event handlers
        setupEditFormHandlers(data);
    }

    // ========== BRAND DROPDOWN LOADING FUNCTION ==========
    function loadBrandsDropdown() {
        return new Promise(resolve => {
            const select = $('#editBrand');

            // If already populated, just resolve
            if (select.find('option').length > 1) {
                console.log('Brand dropdown already populated');
                resolve();
                return;
            }

            // Check if brands are already in the HTML from server-side
            if (select.find('option[value="1"]').length > 0) {
                console.log('Brand dropdown already has options from HTML');
                resolve();
                return;
            }

            // Hardcoded list of common camera/video brands as a quick fix
            console.log('Loading brands dropdown with hardcoded values');

            // Clear and populate with hardcoded brands
            select.empty().append('<option value="">Select Brand</option>');
            select.append('<option value="0">-- No Brand / Unbranded --</option>');

            // Common camera/video equipment brands
            const brands = [{
                    id: 1,
                    name: 'Sony'
                },
                {
                    id: 2,
                    name: 'Canon'
                },
                {
                    id: 3,
                    name: 'Nikon'
                },
                {
                    id: 4,
                    name: 'Panasonic'
                },
                {
                    id: 5,
                    name: 'Blackmagic Design'
                },
                {
                    id: 6,
                    name: 'ARRI'
                },
                {
                    id: 7,
                    name: 'RED'
                },
                {
                    id: 8,
                    name: 'Fujifilm'
                },
                {
                    id: 9,
                    name: 'Leica'
                },
                {
                    id: 10,
                    name: 'Zeiss'
                },
                {
                    id: 11,
                    name: 'Sigma'
                },
                {
                    id: 12,
                    name: 'Tamron'
                },
                {
                    id: 13,
                    name: 'GoPro'
                },
                {
                    id: 14,
                    name: 'DJI'
                },
                {
                    id: 15,
                    name: 'Manfrotto'
                },
                {
                    id: 16,
                    name: 'Sennheiser'
                },
                {
                    id: 17,
                    name: 'Shure'
                },
                {
                    id: 18,
                    name: 'Rode'
                },
                {
                    id: 19,
                    name: 'Atomos'
                },
                {
                    id: 20,
                    name: 'SmallHD'
                }
            ];

            brands.forEach(b => {
                select.append(`<option value="${b.id}">${b.name}</option>`);
            });

            resolve();
        });
    }

    function openEditItemModal(itemId) {
        console.log('openEditItemModal called with ID:', itemId);

        if (!itemId) {
            toastr.error('Invalid item ID');
            return;
        }

        const modalBody = $('#editItemModal .modal-body');

        // Show loading
        modalBody.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-warning mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading item details...</p>
            </div>
        `);

        // Show modal
        const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));
        editModal.show();

        // Fetch item data
        fetchItemData(itemId).then(function(data) {
            console.log('Edit data received:', data);

            // Check if we have the data
            if (!data) {
                toastr.error('No data received');
                return;
            }

            modalBody.html(originalEditModalHtml);
            populateEditItemModal(data);

        }).catch(function(error) {
            console.error('Failed to load item:', error);
            modalBody.html(`
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                    <h5>Error Loading Item</h5>
                    <p class="text-muted">${error.message}</p>
                    <button class="btn btn-primary" onclick="openEditItemModal(${itemId})">
                        <i class="fas fa-sync-alt me-1"></i> Retry
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            `);
        });
    }

    // ========== VIEW MODAL FUNCTIONS ==========

    function updateViewStatusBadges(data) {
        // Condition badge
        const conditionBadge = $('#viewCondition');
        conditionBadge.removeClass().addClass('badge');
        const condition = (data.condition || 'good').toLowerCase();
        switch (condition) {
            case 'new':
            case 'excellent':
                conditionBadge.addClass('bg-success').text('Excellent');
                break;
            case 'good':
                conditionBadge.addClass('bg-primary').text('Good');
                break;
            case 'fair':
                conditionBadge.addClass('bg-info').text('Fair');
                break;
            case 'poor':
                conditionBadge.addClass('bg-warning').text('Poor');
                break;
            case 'damaged':
            case 'broken':
                conditionBadge.addClass('bg-danger').text('Damaged');
                break;
            default:
                conditionBadge.addClass('bg-secondary').text(data.condition || 'Unknown');
        }

        // Status badge
        const statusBadge = $('#viewItemStatusBadge');
        statusBadge.removeClass().addClass('badge');
        const status = (data.status || 'available').toLowerCase();
        switch (status) {
            case 'available':
                statusBadge.addClass('bg-success').text('Available');
                break;
            case 'in_use':
                statusBadge.addClass('bg-primary').text('In Use');
                break;
            case 'maintenance':
                statusBadge.addClass('bg-warning').text('Maintenance');
                break;
            case 'reserved':
                statusBadge.addClass('bg-info').text('Reserved');
                break;
            case 'disposed':
                statusBadge.addClass('bg-danger').text('Disposed');
                break;
            case 'lost':
                statusBadge.addClass('bg-dark').text('Lost');
                break;
            default:
                statusBadge.addClass('bg-secondary').text(data.status || 'Unknown');
        }
    }

    function populateViewItemModal(data) {
        console.log('========== POPULATE VIEW MODAL START ==========');
        console.log('Populating view modal with:', data);
        console.log('Description in view modal:', data?.description);
        console.log('Description type:', typeof data?.description);

        // Check if the viewDescription element exists
        const viewDescElement = $('#viewDescription');
        console.log('viewDescription element exists:', viewDescElement.length > 0);
        console.log('viewDescription element HTML:', viewDescElement.length ? viewDescElement[0].outerHTML : 'Element not found');

        $('#viewItemModalLabel').html(`<i class="fas fa-eye me-2"></i>View: ${data.item_name || 'Item'}`);
        $('#viewItemId').text(data.id || '');

        // Item Image
        if (data.image && data.image !== '' && data.image !== null) {
            $('#viewItemImage img').attr('src', data.image);
            $('#viewItemImage').show();
            $('#viewNoImage').hide();
        } else {
            $('#viewItemImage').hide();
            $('#viewNoImage').show();
        }

        // QR Code
        updateViewQRCode(data.qr_code, data.item_name, data.serial_number);

        // ========== BASIC INFORMATION ==========
        $('#viewItemName').text(data.item_name || 'N/A');
        $('#viewSerialNumber').text(data.serial_number || 'N/A');
        $('#viewCategory').text(data.category || 'N/A');
        $('#viewBrand').text(data.brand || 'N/A');
        $('#viewModel').text(data.model || 'N/A');
        $('#viewBrandModel').text(data.brand_model || 'N/A');

        // ========== STATUS & CONDITION ==========
        // Condition badge
        const conditionBadge = $('#viewCondition');
        conditionBadge.removeClass().addClass('badge');
        const condition = (data.condition || 'good').toLowerCase();
        switch (condition) {
            case 'new':
            case 'excellent':
                conditionBadge.addClass('bg-success').text('Excellent');
                break;
            case 'good':
                conditionBadge.addClass('bg-primary').text('Good');
                break;
            case 'fair':
                conditionBadge.addClass('bg-info').text('Fair');
                break;
            case 'poor':
                conditionBadge.addClass('bg-warning').text('Poor');
                break;
            case 'damaged':
            case 'broken':
                conditionBadge.addClass('bg-danger').text('Damaged');
                break;
            default:
                conditionBadge.addClass('bg-secondary').text(data.condition || 'Unknown');
        }

        // Status badge
        const statusBadge = $('#viewItemStatusBadge');
        statusBadge.removeClass().addClass('badge');
        const status = (data.status || 'available').toLowerCase();
        switch (status) {
            case 'available':
                statusBadge.addClass('bg-success').text('Available');
                break;
            case 'in_use':
                statusBadge.addClass('bg-primary').text('In Use');
                break;
            case 'maintenance':
                statusBadge.addClass('bg-warning').text('Maintenance');
                break;
            case 'reserved':
                statusBadge.addClass('bg-info').text('Reserved');
                break;
            case 'disposed':
                statusBadge.addClass('bg-danger').text('Disposed');
                break;
            case 'lost':
                statusBadge.addClass('bg-dark').text('Lost');
                break;
            default:
                statusBadge.addClass('bg-secondary').text(data.status || 'Unknown');
        }

        // ========== LOCATION INFORMATION ==========
        $('#viewStockLocation').text(data.stock_location || 'Not Set');
        $('#viewStorageLocation').text(data.storage_location || 'Not Set');
        $('#viewCurrentLocation').text(data.current_location || 'Not Set');

        // ========== DEPARTMENT ==========
        $('#viewDepartment').text(data.department || 'Not Set');

        // ========== QUANTITY ==========
        $('#viewQuantity').text(data.quantity || 1);

        // ========== DATES ==========
        $('#viewCreatedAt').text(formatDateTime(data.created_at) || 'N/A');
        $('#viewUpdatedAt').text(formatDateTime(data.updated_at) || 'N/A');
        $('#viewLastScanned').text(data.last_scanned ? formatDateTime(data.last_scanned) : 'Never');

        // ========== DESCRIPTION & NOTES ==========
        console.log('Setting description to:', data.description || 'No description available');

        // Try setting the text
        viewDescElement.text(data.description || 'No description available');

        // Verify it was set
        setTimeout(() => {
            console.log('Description text after setting:', $('#viewDescription').text());
            console.log('Description HTML after setting:', $('#viewDescription').html());
        }, 100);

        $('#viewSpecifications').text(data.specifications || 'No specifications available');
        $('#viewNotes').text(data.notes || 'No notes available');

        // ========== TAGS ==========
        if (data.tags && data.tags.trim() !== '') {
            const tags = data.tags.split(',').map(tag => tag.trim());
            let tagsHtml = '';
            tags.forEach(tag => {
                tagsHtml += `<span class="badge bg-secondary me-1 mb-1">${escapeHtml(tag)}</span>`;
            });
            $('#viewTags').html(tagsHtml);
            $('#viewTagsSection').show();
        } else {
            $('#viewTagsSection').hide();
        }

        // ========== ACCESSORIES ==========
        // ========== ACCESSORIES ==========
        console.log('Processing accessories for item:', data.id);
        console.log('Accessories data:', data.accessories);

        let accessoriesHtml = '<div class="d-flex flex-wrap gap-1">';
        let hasAccessories = false;

        // Check accessories array FIRST (this is where your data is)
        if (data.accessories && Array.isArray(data.accessories)) {
            if (data.accessories.length > 0) {
                console.log(`Found ${data.accessories.length} accessories in array`);
                data.accessories.forEach(acc => {
                    if (acc) {
                        accessoriesHtml += `<span class="badge bg-info p-2 m-1">${escapeHtml(acc)}</span>`;
                        hasAccessories = true;
                    }
                });
            } else {
                console.log('Accessories array is empty');
            }
        }
        // Check accessories_list as fallback
        else if (data.accessories_list && Array.isArray(data.accessories_list)) {
            if (data.accessories_list.length > 0) {
                console.log(`Found ${data.accessories_list.length} accessories in accessories_list`);
                data.accessories_list.forEach(acc => {
                    if (acc) {
                        const name = acc.name || acc;
                        accessoriesHtml += `<span class="badge bg-info p-2 m-1">${escapeHtml(name)}</span>`;
                        hasAccessories = true;
                    }
                });
            }
        }
        // Check if accessories is a string
        else if (data.accessories && typeof data.accessories === 'string' && data.accessories.trim() !== '') {
            console.log('Parsing accessories string:', data.accessories);
            const accList = data.accessories.split(',').map(a => a.trim()).filter(a => a);
            if (accList.length > 0) {
                accList.forEach(acc => {
                    accessoriesHtml += `<span class="badge bg-info p-2 m-1">${escapeHtml(acc)}</span>`;
                    hasAccessories = true;
                });
            }
        }

        accessoriesHtml += '</div>';

        console.log('Has accessories?', hasAccessories);

        if (hasAccessories) {
            $('#viewAccessoriesList').html(accessoriesHtml);
            $('#viewAccessoriesSection').show();
        } else {
            $('#viewAccessoriesSection').hide();
            $('#viewAccessoriesList').html('<p class="text-muted mb-0">No accessories</p>');
        }

        // ========== QR CODE INFO ==========
        if (data.qr_code && data.qr_code !== '' && data.qr_code !== 'pending') {
            $('#viewQRCode').html(`
                <img src="${data.qr_code}" alt="QR Code" style="width: 150px; height: 150px;" class="img-fluid border rounded">
                <div class="mt-2 small text-muted">${data.item_name || 'QR Code'}</div>
            `);
        } else {
            $('#viewQRCode').html(`
                <div class="text-center py-3">
                    <i class="fas fa-qrcode fa-2x text-muted mb-2"></i>
                    <p class="text-muted small mb-0">No QR Code</p>
                </div>
            `);
        }

        window.currentViewItemData = data;
        console.log('========== POPULATE VIEW MODAL END ==========');
    }


    function showViewItemError(itemId, error) {
        $('#viewItemName').text('Error Loading Item');
        $('#viewSerialNumber').text('N/A');
        $('#viewCategory').text('N/A');
        $('#viewBrand').text('N/A');
        $('#viewModel').text('N/A');
        $('#viewQuantity').text('-');
        $('#viewStockLocation').text('N/A');
        $('#viewStorageLocation').text('N/A');
        $('#viewDepartment').text('N/A');
        $('#viewCreatedAt').text('N/A');
        $('#viewUpdatedAt').text('N/A');
        $('#viewDescription').text('Failed to load item details. Please try again.');
        $('#viewCondition').text('Unknown');
        $('#viewItemStatusBadge').text('Unknown');
        $('#viewQRCode').html(`
            <div class="text-center py-3">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-2"></i>
                <div class="text-danger">Failed to load QR code</div>
                <button class="btn btn-sm btn-primary mt-2" onclick="openViewItemModal(${itemId})">
                    <i class="fas fa-sync-alt me-1"></i> Retry
                </button>
            </div>
        `);
        toastr.error('Failed to load item details for viewing');
    }

    function openViewItemModal(itemId) {
        console.log('Opening view modal for item ID:', itemId);

        const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
        if (quickModal) quickModal.hide();

        const viewModal = new bootstrap.Modal(document.getElementById('viewItemModal'));

        $('#viewItemImage').hide();
        $('#viewNoImage').hide();
        $('#viewQRCode').html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Loading QR code...</div>
            </div>
        `);

        $('#viewItemName').text('Loading...');
        $('#viewSerialNumber').text('Loading...');
        $('#viewCategory').text('Loading...');
        $('#viewBrand').text('Loading...');
        $('#viewModel').text('Loading...');
        $('#viewQuantity').text('-');
        $('#viewStockLocation').text('Loading...');
        $('#viewStorageLocation').text('Loading...');
        $('#viewDepartment').text('Loading...');
        $('#viewCreatedAt').text('Loading...');
        $('#viewUpdatedAt').text('Loading...');
        $('#viewDescription').text('Loading...');
        $('#viewCondition').text('Loading...');
        $('#viewItemStatusBadge').text('Loading...');

        viewModal.show();

        fetchItemData(itemId).then(function(data) {
            console.log('Item data received:', data);
            populateViewItemModal(data);
        }).catch(function(error) {
            console.error('Failed to load item:', error);
            showViewItemError(itemId, error);
        });
    }

    // ========== QUICK ACTIONS MODAL FUNCTIONS ==========

    function populateQuickViewModal(data) {
        $('#qvItemId').text(data.id || '');
        $('#qvItemName').text(data.item_name || '');
        $('#qvItemCategory').text(data.category || 'N/A');
        $('#qvItemSerial').text(data.serial_number || 'N/A');
        $('#qvItemQuantity').text(data.quantity || 1);
        $('#qvItemLocation').text(data.stock_location || 'N/A');
        $('#qvItemDepartment').text(data.department || 'N/A');
        $('#qvItemBrand').text(data.brand || 'N/A');
        $('#qvItemModel').text(data.model || 'N/A');
        $('#qvItemStorageLocation').text(data.storage_location || 'N/A');
        $('#qvItemAccessories').text(data.accessories || 'None');
        $('#qvItemCreatedAt').text(formatDateTime(data.created_at) || 'N/A');
        $('#qvItemUpdatedAt').text(formatDateTime(data.updated_at) || 'N/A');

        $('#qrItemName').text(data.item_name || '');

        // Status badge
        const statusBadge = $('#qvItemStatusBadge');
        statusBadge.removeClass().addClass('badge');
        switch (data.status) {
            case 'available':
                statusBadge.addClass('bg-success').text('Available');
                break;
            case 'in_use':
                statusBadge.addClass('bg-primary').text('In Use');
                break;
            case 'maintenance':
                statusBadge.addClass('bg-warning').text('Maintenance');
                break;
            case 'reserved':
                statusBadge.addClass('bg-info').text('Reserved');
                break;
            case 'disposed':
                statusBadge.addClass('bg-danger').text('Disposed');
                break;
            case 'lost':
                statusBadge.addClass('bg-dark').text('Lost');
                break;
            default:
                statusBadge.addClass('bg-secondary').text(data.status || 'Unknown');
        }

        // Condition badge
        const conditionBadge = $('#qvItemConditionBadge');
        conditionBadge.removeClass().addClass('badge');
        switch (data.condition) {
            case 'new':
            case 'excellent':
                conditionBadge.addClass('bg-success').text('Excellent');
                break;
            case 'good':
                conditionBadge.addClass('bg-primary').text('Good');
                break;
            case 'fair':
                conditionBadge.addClass('bg-info').text('Fair');
                break;
            case 'poor':
                conditionBadge.addClass('bg-warning').text('Poor');
                break;
            case 'damaged':
            case 'broken':
                conditionBadge.addClass('bg-danger').text('Damaged');
                break;
            default:
                conditionBadge.addClass('bg-secondary').text(data.condition || 'Unknown');
        }

        // QR Code
        const qrContainer = $('#qvQRCode');
        qrContainer.empty();

        if (data.qr_code && data.qr_code !== '' && data.qr_code !== 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <img src="${data.qr_code}" alt="QR Code" style="width: 100px; height: 100px;" class="img-fluid border rounded p-1">
                    <div class="mt-2">
                        <button class="btn btn-sm btn-success mb-1 download-qr-btn">
                            <i class="fas fa-download me-1"></i> Download QR
                        </button>
                        <button class="btn btn-sm btn-info view-qr-btn">
                            <i class="fas fa-expand me-1"></i> View Full
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">${data.item_name || 'QR Code'}</small>
                </div>
            `);

            $('.download-qr-btn').off('click').on('click', function() {
                downloadSingleQRCode(data.qr_code, data.item_name, data.serial_number);
            });

            $('.view-qr-btn').off('click').on('click', function() {
                window.open(data.qr_code, '_blank');
            });

        } else if (data.qr_code === 'pending') {
            qrContainer.html(`
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div class="small mt-2">Generating QR Code...</div>
                    <button class="btn btn-sm btn-outline-primary mt-2 generate-qr-btn">
                        <i class="fas fa-bolt me-1"></i> Generate Now
                    </button>
                </div>
            `);

            $('.generate-qr-btn').off('click').on('click', function() {
                generateQRCodeForItem(data.id, data.item_name);
            });

        } else {
            qrContainer.html(`
                <div class="text-center">
                    <i class="fas fa-qrcode fa-3x text-muted mb-2"></i>
                    <div class="small text-muted mb-2">No QR Code</div>
                    <button class="btn btn-sm btn-primary generate-qr-btn">
                        <i class="fas fa-plus-circle me-1"></i> Generate QR
                    </button>
                </div>
            `);

            $('.generate-qr-btn').off('click').on('click', function() {
                generateQRCodeForItem(data.id, data.item_name);
            });
        }

        // Action buttons
        $('#qvCopySerialBtn').off('click').on('click', function() {
            navigator.clipboard.writeText(data.serial_number || '');
            toastr.success('Serial number copied to clipboard');
        });

        $('#qvPrintBtn').off('click').on('click', function() {
            window.open('items/print.php?id=' + data.id, '_blank');
        });

        $('#qvViewBtn').off('click').on('click', function(e) {
            e.preventDefault();
            const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
            if (quickModal) quickModal.hide();
            setTimeout(() => openViewItemModal(data.id), 300);
        });

        $('#qvEditBtn').off('click').on('click', function(e) {
            e.preventDefault();
            const quickModal = bootstrap.Modal.getInstance(document.getElementById('quickActionsModal'));
            if (quickModal) quickModal.hide();
            setTimeout(() => openEditItemModal(data.id), 300);
        });

        $('[data-status]').off('click').on('click', function() {
            const newStatus = $(this).data('status');
            updateItemStatus(data.id, newStatus);
        });

        window.currentItemData = data;
    }

    function updateItemStatus(itemId, newStatus) {
        if (!itemId || !newStatus) return;

        $.ajax({
            url: 'api/update_status.php',
            method: 'POST',
            data: {
                item_id: itemId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Status updated successfully');
                    $('.quick-view-btn[data-item-id="' + itemId + '"]').click();
                } else {
                    toastr.error(response.message || 'Failed to update status');
                }
            },
            error: function() {
                toastr.error('Error updating status');
            }
        });
    }

    // ========== CRUD OPERATIONS ==========

    // Handle edit form submission
    $(document).on('submit', '#editItemForm', function(e) {
        e.preventDefault();
        console.log('Edit form submitted');
        submitEditItemForm();
    });

    function submitEditItemForm() {
        // Validate required fields
        const itemName = $('#editItemName').val().trim();
        const serialNumber = $('#editSerialNumber').val().trim();
        const category = $('#editCategory').val();

        if (!itemName || !serialNumber || !category) {
            toastr.error('Please fill all required fields (Item Name, Serial Number, Category)');
            return;
        }

        // Get the item ID
        const itemId = $('#editItemId').val();

        console.log('Submitting edit for item ID:', itemId);
        console.log('Item Name:', itemName);
        console.log('Category:', category);

        const formData = new FormData();
        formData.append('id', itemId);
        formData.append('item_name', itemName);
        formData.append('serial_number', serialNumber);
        formData.append('category', category);
        formData.append('status', $('#editStatus').val());
        formData.append('condition', $('#editCondition').val());
        formData.append('stock_location', $('#editStockLocation').val());
        formData.append('storage_location', $('#editStorageLocation').val());
        formData.append('department', $('#editDepartment').val());
        formData.append('brand', $('#editBrand').val());
        formData.append('model', $('#editModel').val());
        formData.append('quantity', $('#editQuantity').val());
        formData.append('description', $('#editDescription').val());
        formData.append('specifications', $('#editSpecifications').val());
        formData.append('notes', $('#editNotes').val());
        formData.append('tags', $('#editTags').val());

        // Get selected accessories
        const accessories = $('#editAccessories').val();
        if (accessories && accessories.length > 0) {
            formData.append('accessories', JSON.stringify(accessories));
        }

        // Handle image
        const changeImage = $('#editChangeImage').is(':checked');
        if (changeImage) {
            const imageFile = $('#editItemImage')[0].files[0];
            if (imageFile) {
                formData.append('image', imageFile);
                console.log('Image file to upload:', imageFile.name);
            } else {
                formData.append('remove_image', '1');
            }
        }

        // Show loading state
        const submitBtn = $('#editSubmitBtn');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        submitBtn.prop('disabled', true);

        // Submit via AJAX
        $.ajax({
            url: 'api/update_item.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Update response:', response);

                if (response.success) {
                    toastr.success('Item updated successfully');

                    // Close modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    if (editModal) editModal.hide();

                    // Reload the items list
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Failed to update item');
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                console.error('Response:', xhr.responseText);

                let errorMsg = 'Error updating item';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.responseText || errorMsg;
                }

                toastr.error(errorMsg);
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    }

    function deleteItem(itemId) {
        if (!itemId) return;

        $('#editDeleteBtn').html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');
        $('#editDeleteBtn').prop('disabled', true);

        $.ajax({
            url: 'api/delete_item.php',
            method: 'POST',
            data: {
                id: itemId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Item deleted successfully');
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    if (editModal) editModal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message || 'Failed to delete item');
                }
            },
            error: function() {
                toastr.error('Error deleting item');
            },
            complete: function() {
                $('#editDeleteBtn').html('<i class="fas fa-trash me-1"></i> Delete Item');
                $('#editDeleteBtn').prop('disabled', false);
            }
        });
    }

    // ========== DATATABLE FUNCTIONS ==========

    function initializeDataTable() {
        if ($('#recentItemsTable').length === 0) return;

        if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
            $('#recentItemsTable').DataTable().destroy();
        }

        try {
            const dataTable = $('#recentItemsTable').DataTable({
                paging: true,
                pageLength: 5,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                serverSide: true, // ENABLE SERVER-SIDE PROCESSING
                processing: true, // SHOW PROCESSING INDICATOR
                order: [
                    [1, 'desc']
                ], // Order by Created At

                // Define columns (same as before)
                columns: [
                    // Column 0: ID (Checkbox)
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<span class="badge bg-" style="background: #486a83">${escapeHtml(data)}</span>`;
                            }
                            return data;
                        }
                    },
                    // Column 1: Created At
                    {
                        data: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (!data) return '<span class="text-muted">N/A</span>';
                                const date = new Date(data);
                                return `<span class="badge bg-secondary" title="${data}">${date.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</span><br><small class="text-muted">${date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</small>`;
                            }
                            return data;
                        }
                    },
                    // Column 2: Item Name
                    {
                        data: 'item_name',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let html = `<div class="fw-bold">${escapeHtml(data || '')}</div>`;

                                // Handle description - clean up "undefined"
                                let description = row.description;
                                if (description && description !== 'undefined' && description !== 'null') {
                                    const desc = description.substring(0, 50);
                                    html += `<small class="text-muted d-block">${escapeHtml(desc)}${description.length > 50 ? '...' : ''}</small>`;
                                }

                                return html;
                            }
                            return data;
                        }
                    },
                    // Column 3: Serial Number
                    {
                        data: 'serial_number',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<code>${escapeHtml(data || 'N/A')}</code>`;
                            }
                            return data;
                        }
                    },
                    // Column 4: Category
                    {
                        data: 'category_name',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<span class="badge bg-secondary">${escapeHtml(data || 'Uncategorized')}</span>`;
                            }
                            // For sorting, use the ID
                            return row.category_id || row.category || '';
                        }
                    },

                    // Column 8: Department - with mapping
                    {
                        data: 'department_name',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data ? escapeHtml(data) : '<span class="text-muted">Not Set</span>';
                            }
                            // For sorting, use the ID
                            return row.department_id || row.department || '';
                        }
                    },
                    // Column 5: Accessories
                    {
                        data: 'accessories',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (!data || data.length === 0) {
                                    return '<span class="text-muted">None</span>';
                                }

                                let html = '<div class="d-flex flex-wrap gap-1">';
                                data.forEach(acc => {
                                    html += `<span class="badge bg-info me-1 mb-1">${escapeHtml(acc)}</span>`;
                                });
                                html += '</div>';
                                return html;
                            }
                            return data;
                        }
                    },
                    // Column 6: Brand
                    {
                        data: 'brand',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data ? escapeHtml(data) : '<span class="text-muted">N/A</span>';
                            }
                            return data;
                        }
                    },
                    // Column 7: Model
                    {
                        data: 'model',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data ? escapeHtml(data) : '<span class="text-muted">N/A</span>';
                            }
                            return data;
                        }
                    },
                    // Column 9: Location
                    {
                        data: 'stock_location',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data ? escapeHtml(data) : '<span class="text-muted">N/A</span>';
                            }
                            return data;
                        }
                    },
                    // Column 10: Condition
                    {
                        data: 'condition',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                const condition = (data || 'good').toLowerCase();
                                let conditionClass = 'bg-secondary';
                                switch (condition) {
                                    case 'new':
                                        conditionClass = 'bg-success';
                                        break;
                                    case 'excellent':
                                        conditionClass = 'bg-success';
                                        break;
                                    case 'good':
                                        conditionClass = 'bg-primary';
                                        break;
                                    case 'fair':
                                        conditionClass = 'bg-warning';
                                        break;
                                    case 'poor':
                                        conditionClass = 'bg-danger';
                                        break;
                                }
                                return `<span class="badge ${conditionClass}">${escapeHtml(data || 'Good')}</span>`;
                            }
                            return data;
                        }
                    },
                    // Column 11: Status
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                const status = (data || 'available').toLowerCase();
                                let statusClass = 'bg-secondary';
                                switch (status) {
                                    case 'available':
                                        statusClass = 'bg-success';
                                        break;
                                    case 'in_use':
                                        statusClass = 'bg-primary';
                                        break;
                                    case 'maintenance':
                                        statusClass = 'bg-warning';
                                        break;
                                    case 'reserved':
                                        statusClass = 'bg-info';
                                        break;
                                    case 'disposed':
                                        statusClass = 'bg-danger';
                                        break;
                                    case 'lost':
                                        statusClass = 'bg-dark';
                                        break;
                                }
                                return `<span class="badge ${statusClass}">${escapeHtml(data || 'Available')}</span>`;
                            }
                            return data;
                        }
                    },
                    // Column 12: Actions
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `
                                <button type="button" class="btn btn-sm btn-primary quick-view-btn mb-1"
                                    data-item-id="${row.id}"
                                    data-item-name="${escapeHtml(row.item_name || '')}"
                                    data-item-serial="${escapeHtml(row.serial_number || '')}"
                                    data-item-category="${escapeHtml(row.category || 'N/A')}"
                                    data-item-quantity="${row.quantity || 1}"
                                    data-item-status="${escapeHtml(row.status || 'available')}"
                                    data-item-condition="${escapeHtml(row.condition || 'good')}"
                                    data-item-location="${escapeHtml(row.stock_location || 'N/A')}"
                                    data-item-description="${escapeHtml(row.description || '')}"
                                    data-item-brand="${escapeHtml(row.brand || 'N/A')}"
                                    data-item-model="${escapeHtml(row.model || 'N/A')}"
                                    data-item-department="${escapeHtml(row.department || 'N/A')}"
                                    data-view-url="items/view.php?id=${row.id}"
                                    data-edit-url="items/edit.php?id=${row.id}"
                                    data-qr-code="${escapeHtml(row.qr_code || '')}"
                                    title="Quick Actions">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </button>
                            `;
                            }
                            return '';
                        }
                    }
                ],
                language: {
                    emptyTable: "No equipment found in the database",
                    zeroRecords: "No matching records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ items",
                    infoEmpty: "Showing 0 to 0 of 0 items",
                    infoFiltered: "(filtered from _MAX_ total items)",
                    lengthMenu: "Show _MENU_ entries per page",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    processing: "Loading..."
                },
                columnDefs: [{
                        targets: [0],
                        orderable: false,
                        searchable: false
                    }, // ID/Checkbox column
                    {
                        targets: [12],
                        orderable: false,
                        searchable: false
                    } // Actions column
                ],
                // Server-side processing configuration
                ajax: {
                    url: 'api/items/list.php',
                    type: 'GET',
                    dataType: 'json',
                    data: function(d) {
                        // Convert DataTables parameters to your API format
                        return {
                            page: Math.floor(d.start / d.length) + 1,
                            limit: d.length,
                            search: d.search.value,
                            sort: d.columns[d.order[0].column].data,
                            order: d.order[0].dir
                        };
                    },
                    dataSrc: function(json) {
                        console.log('DataTable server response:', json);

                        if (json.success && json.pagination) {
                            // CRITICAL: Tell DataTables about total records
                            json.recordsTotal = json.pagination.totalItems;
                            json.recordsFiltered = json.pagination.totalItems;
                        }

                        return json.success && json.items ? json.items : [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX error:', error);
                        console.error('Response:', xhr.responseText);
                        return [];
                    }
                }
            });

            console.log('✅ DataTable initialized successfully with server-side processing');
            return dataTable;

        } catch (error) {
            console.error('❌ DataTable initialization error:', error);
            $('#recentItemsTable').addClass('table table-bordered table-hover table-striped');
            return null;
        }
    }

    // Fallback function to load items manually if DataTable fails
    function loadItemsManually() {
        $.ajax({
            url: 'api/items/list.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                const items = response.success && response.items ? response.items : [];
                const tbody = $('#recentItemsTable tbody');
                tbody.empty();

                if (items.length === 0) {
                    tbody.html('<tr><td colspan="13" class="text-center">No equipment found</td></tr>');
                    return;
                }

                items.forEach(item => {
                    const row = createTableRow(item);
                    tbody.append(row);
                });

                // Re-attach event handlers
                attachTableEventHandlers();
            },
            error: function() {
                $('#recentItemsTable tbody').html('<tr><td colspan="13" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    // Helper function to create table row HTML
    function createTableRow(item) {
        const statusBadge = getStatusBadge(item.status);
        const conditionBadge = getConditionBadge(item.condition);
        const createdDate = item.created_at ? new Date(item.created_at) : null;

        return `
        <tr>
            <td><input type="checkbox" class="item-checkbox" value="${item.id}"></td>
            <td>${createdDate ? 
                `<span class="badge bg-secondary" title="${item.created_at}">${createdDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</span><br><small class="text-muted">${createdDate.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</small>` : 
                '<span class="text-muted">N/A</span>'
            }</td>
            <td>
                <div class="fw-bold">${escapeHtml(item.item_name || '')}</div>
                ${item.description ? `<small class="text-muted d-block">${escapeHtml(item.description.substring(0, 50))}...</small>` : ''}
            </td>
            <td><code>${escapeHtml(item.serial_number || 'N/A')}</code></td>
            <td><span class="badge bg-secondary">${escapeHtml(item.category || 'Uncategorized')}</span></td>
            <td>${renderAccessories(item.accessories)}</td>
            <td>${escapeHtml(item.brand || 'N/A')}</td>
            <td>${escapeHtml(item.model || 'N/A')}</td>
            <td>${escapeHtml(item.department || 'N/A')}</td>
            <td>${escapeHtml(item.stock_location || 'N/A')}</td>
            <td>${conditionBadge}</td>
            <td>${statusBadge}</td>
            <td>
                <button type="button" class="btn btn-sm btn-primary quick-view-btn mb-1"
                    data-item-id="${item.id}"
                    data-item-name="${escapeHtml(item.item_name || '')}"
                    data-item-serial="${escapeHtml(item.serial_number || '')}"
                    data-item-category="${escapeHtml(item.category || 'N/A')}"
                    data-item-quantity="${item.quantity || 1}"
                    data-item-status="${escapeHtml(item.status || 'available')}"
                    data-item-condition="${escapeHtml(item.condition || 'good')}"
                    data-item-location="${escapeHtml(item.stock_location || 'N/A')}"
                    data-item-description="${escapeHtml(item.description || '')}"
                    data-item-brand="${escapeHtml(item.brand || 'N/A')}"
                    data-item-model="${escapeHtml(item.model || 'N/A')}"
                    data-item-department="${escapeHtml(item.department || 'N/A')}"
                    data-view-url="items/view.php?id=${item.id}"
                    data-edit-url="items/edit.php?id=${item.id}"
                    data-qr-code="${escapeHtml(item.qr_code || '')}"
                    title="Quick Actions">
                    <i class="fas fa-bolt"></i> Quick Actions
                </button>
            </td>
        </tr>
    `;
    }

    function renderAccessories(accessories) {
        if (!accessories || (Array.isArray(accessories) && accessories.length === 0)) {
            return '<span class="text-muted">None</span>';
        }
        if (Array.isArray(accessories)) {
            let html = '';
            accessories.forEach(acc => {
                html += `<span class="badge bg-info me-1 mb-1">${escapeHtml(acc.name || acc)}</span> `;
            });
            return html;
        }
        return escapeHtml(accessories);
    }

    function getConditionBadge(condition) {
        const cond = (condition || 'good').toLowerCase();
        let conditionClass = 'bg-secondary';
        switch (cond) {
            case 'new':
                conditionClass = 'bg-success';
                break;
            case 'good':
                conditionClass = 'bg-primary';
                break;
            case 'fair':
                conditionClass = 'bg-warning';
                break;
            case 'poor':
                conditionClass = 'bg-danger';
                break;
        }
        return `<span class="badge ${conditionClass}">${escapeHtml(condition || 'Good')}</span>`;
    }

    function getStatusBadge(status) {
        const stat = (status || 'available').toLowerCase();
        let statusClass = 'bg-secondary';
        switch (stat) {
            case 'available':
                statusClass = 'bg-success';
                break;
            case 'in_use':
                statusClass = 'bg-primary';
                break;
            case 'maintenance':
                statusClass = 'bg-warning';
                break;
            case 'reserved':
                statusClass = 'bg-info';
                break;
            case 'disposed':
                statusClass = 'bg-danger';
                break;
            case 'lost':
                statusClass = 'bg-dark';
                break;
        }
        return `<span class="badge ${statusClass}">${escapeHtml(status || 'Available')}</span>`;
    }

    function attachTableEventHandlers() {
        // Quick view buttons
        $('.quick-view-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            if (itemId) {
                fetchItemData(itemId).then(function(data) {
                    if (data) {
                        populateQuickViewModal(data);
                        const quickActionsModal = document.getElementById('quickActionsModal');
                        const modal = new bootstrap.Modal(quickActionsModal);
                        modal.show();
                        toastr.success('Item loaded successfully');
                    }
                }).catch(function(error) {
                    toastr.error('Error loading item details');
                });
            }
        });

        // Edit buttons
        $('.edit-item-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            if (itemId) {
                openEditItemModal(itemId);
            }
        });

        // Select all checkbox
        $('#selectAllItems').off('change').on('change', function() {
            $('.item-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedItems();
        });

        // Individual checkboxes
        $('.item-checkbox').off('change').on('change', function() {
            updateSelectedItems();
        });
    }

    function refreshDataTable() {
        if ($.fn.DataTable.isDataTable('#recentItemsTable')) {
            const table = $('#recentItemsTable').DataTable();
            if (table.settings()[0].oFeatures.bServerSide) {
                table.ajax.reload(null, false);
            } else {
                table.ajax.reload();
            }
        } else {
            setTimeout(() => location.reload(), 500);
        }
    }

    // ========== FORM HANDLER FUNCTIONS ==========

    function handleImagePreview(e) {
        const file = e.target.files[0];
        const preview = $('#imagePreview');
        const previewImg = preview.find('img');

        if (!file) {
            preview.hide();
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            toastr.error('File size must be less than 5MB');
            $(e.target).val('');
            preview.hide();
            return;
        }

        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            toastr.error('Please select a valid image file (JPG, PNG, GIF, WebP)');
            $(e.target).val('');
            preview.hide();
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            previewImg.attr('src', event.target.result);
            preview.show();
        };
        reader.readAsDataURL(file);
    }

    function generateSerialNumber() {
        const name = $('#item_name').val().trim();
        const prefix = name ? name.substring(0, 3).toUpperCase().replace(/\s/g, '') : 'EQP';
        const timestamp = Date.now().toString().substr(-8);
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        $('#serial_number').val(`${prefix}-${timestamp}-${random}`);
    }

    function handleAddItemSubmit(e) {
        e.preventDefault();

        const itemName = $('#item_name').val().trim();
        const serialNumber = $('#serial_number').val().trim();
        const category = $('#category').val();

        if (!itemName || !serialNumber || !category) {
            toastr.error('Please fill all required fields (Item Name, Serial Number, Category)');
            return;
        }

        const formData = new FormData(this);
        const selectedAccessories = [];
        $('#accessories option:selected').each(function() {
            if ($(this).val()) selectedAccessories.push($(this).val());
        });
        formData.append('accessories_array', JSON.stringify(selectedAccessories));

        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: 'api/items/create.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Item added successfully!');
                    $('#addItemForm')[0].reset();
                    $('#imagePreview').hide();
                    $('#selectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
                    $('#accessories').val('').trigger('change');
                    setTimeout(() => {
                        $('#addItemModal').modal('hide');
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Failed to add item');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'An error occurred while adding the item';
                if (xhr.responseText) {
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        errorMessage = jsonResponse.message || errorMessage;
                    } catch (e) {
                        errorMessage = 'Server error: ' + xhr.responseText.substring(0, 100);
                    }
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    }

    function handleDeleteItemClick(e) {
        e.preventDefault();
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');

        if (confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
            $.ajax({
                url: 'api/items/delete.php',
                method: 'POST',
                data: {
                    id: itemId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Error deleting item');
                }
            });
        }
    }

    function handleExport() {
        toastr.info('Preparing export...');
        $.ajax({
            url: 'api/export_items.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.download_url) {
                    const link = document.createElement('a');
                    link.href = response.download_url;
                    link.download = response.filename || 'items_export.csv';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    toastr.success('Export downloaded successfully!');
                } else {
                    toastr.error(response.message || 'Failed to export items');
                }
            },
            error: function() {
                toastr.error('Error exporting items');
            }
        });
    }

    function handleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', isChecked).trigger('change');
    }

    function handleItemCheckbox() {
        const itemId = $(this).val();
        if ($(this).is(':checked')) {
            if (!selectedItems.includes(itemId)) {
                selectedItems.push(itemId);
            }
        } else {
            selectedItems = selectedItems.filter(id => id !== itemId);
        }
        $('#bulkActions').toggle(selectedItems.length > 0);
        $('#selectedCount').text(selectedItems.length);
    }

    function handleBulkDelete() {
        if (selectedItems.length === 0) {
            toastr.warning('No items selected');
            return;
        }

        if (confirm(`Are you sure you want to delete ${selectedItems.length} selected item(s)?`)) {
            $.ajax({
                url: 'api/bulk_delete.php',
                method: 'POST',
                data: {
                    items: selectedItems
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Error deleting items');
                }
            });
        }
    }

    // ========== EXCEL UPLOAD FUNCTIONS ==========
    function initializeExcelUpload() {
        $('#excelUploadForm').on('submit', function(e) {
            e.preventDefault();

            const fileInput = $('#excelFile')[0];
            if (!fileInput.files.length) {
                toastr.warning('Please select an Excel file');
                return;
            }

            const formData = new FormData(this);

            $.ajax({
                url: 'api/upload_excel.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#uploadBtn').html('<i class="fas fa-spinner fa-spin me-1"></i> Uploading...');
                    $('#uploadBtn').prop('disabled', true);
                    toastr.info('Uploading file...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Items uploaded successfully!');
                        $('#excelUploadModal').modal('hide');
                        refreshDataTable(); // Refresh the table
                    } else {
                        toastr.error(response.message || 'Upload failed');
                    }
                },
                error: function(xhr) {
                    console.error('Upload error:', xhr.responseText);
                    toastr.error('Error uploading file');
                },
                complete: function() {
                    $('#uploadBtn').html('<i class="fas fa-upload me-1"></i> Upload');
                    $('#uploadBtn').prop('disabled', false);
                    $('#excelFile').val('');
                }
            });
        });
    }

    // ========== DOCUMENT READY ==========
    $(document).ready(function() {
        saveOriginalEditModalHtml();

        // Start the clock
        startClock();

        // Initialize Excel upload
        initializeExcelUpload();

        // Edit button click handler
        $(document).on('click', '.edit-item-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            if (itemId) openEditItemModal(itemId);
            else toastr.error('Invalid item ID');
        });

        // Quick view button click handler
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');

            if (!itemId) {
                toastr.error('Invalid item ID');
                return;
            }

            toastr.info(`Loading ${itemName}...`, '', {
                timeOut: 3000
            });

            fetchItemData(itemId).then(function(data) {
                if (data) {
                    populateQuickViewModal(data);
                    const quickActionsModal = document.getElementById('quickActionsModal');
                    const modal = new bootstrap.Modal(quickActionsModal);
                    modal.show();
                    toastr.success('Item loaded successfully');
                } else {
                    toastr.error('Failed to load item details');
                }
            }).catch(function(error) {
                toastr.error('Error loading item details');
            });
        });

        // Initialize DataTable
        setTimeout(initializeDataTable, 300);

        // Refresh button
        $('#refreshItemsBtn').click(function(e) {
            e.preventDefault();
            const button = $(this);
            const originalHtml = button.html();
            button.html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
            button.prop('disabled', true);
            toastr.info('Refreshing data...');
            refreshDataTable();
            setTimeout(() => {
                button.html(originalHtml);
                button.prop('disabled', false);
                toastr.success('Data refreshed!');
            }, 1500);
        });

        // Image preview
        $('#item_image').on('change', handleImagePreview);

        // Accessories change
        $('#accessories').on('change', updateSelectedAccessories);

        // Generate serial number
        $('#generateSerialBtn').click(generateSerialNumber);

        // Add item form submission
        $('#addItemForm').on('submit', handleAddItemSubmit);

        // Delete item button
        $(document).on('click', '.delete-item-btn', handleDeleteItemClick);

        // Export button
        $('#exportItemsBtn').click(handleExport);

        // Bulk actions
        $('#selectAllItems').on('change', handleSelectAll);
        $(document).on('change', '.item-checkbox', handleItemCheckbox);
        $('#bulkDeleteBtn').click(handleBulkDelete);

        // Initialize chart - only if canvas exists
        if (document.getElementById('statusChart')) {
            setTimeout(initializeStatusChart, 500);
        }

        // Modal event handlers
        $('#quickActionsModal').on('hidden.bs.modal', () => delete window.currentItemData);
        $('#viewItemModal').on('hidden.bs.modal', () => delete window.currentViewItemData);
        $('#editItemModal').on('hidden.bs.modal', resetEditModal);

        // Remove accessory badge click handler (delegated)
        $(document).on('click', '.accessory-badge', function(e) {
            e.stopPropagation();
            const value = $(this).data('value');
            $('#accessories option[value="' + value + '"]').prop('selected', false);
            $(this).remove();
            if ($('#selectedAccessories .accessory-badge').length === 0) {
                $('#selectedAccessories').html('<p class="text-muted mb-0">No accessories selected</p>');
            }
        });
    });
</script>