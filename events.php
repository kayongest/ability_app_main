<?php
// events.php - Enhanced version with new fields and improved structure
$pageTitle = "Event Manager - aBility";
$showBreadcrumb = true;
$breadcrumbItems = [
    'Dashboard' => 'dashboard.php',
    'Event Manager' => ''
];

require_once 'views/partials/header.php';
?>

<style>
    /* ===== VARIABLES ===== */
    :root {
        --primary: #1f5e4f;
        --primary-dark: #154d40;
        --primary-light: #2a7f6e;
        --secondary: #ecf3f7;
        --danger: #dc3545;
        --danger-light: #ffeeed;
        --warning: #ffc107;
        --success: #28a745;
        --text-dark: #0b2b3c;
        --text-light: #4a6572;
        --border: #d5e3e9;
        --shadow: 0 10px 25px rgba(0, 20, 40, 0.06);
        --radius-lg: 32px;
        --radius-md: 24px;
        --radius-sm: 16px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* ===== LAYOUT ===== */
    .events-container {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    /* ===== SIMPLE COLUMN CARDS STYLES ===== */
    /* Float four columns side by side */
    .column {
        float: left;
        width: 25%;
        padding: 0 10px;
        margin-bottom: 20px;
    }

    /* Remove extra left and right margins, due to padding */
    .row {
        margin: 0 -5px;
    }

    /* Clear floats after the columns */
    .row:after {
        content: "";
        display: table;
        clear: both;
    }

    /* Responsive columns */
    @media screen and (max-width: 1200px) {
        .column {
            width: 33.33%;
        }
    }

    @media screen and (max-width: 900px) {
        .column {
            width: 50%;
        }
    }

    @media screen and (max-width: 600px) {
        .column {
            width: 100%;
            display: block;
        }
    }

    /* Style the counter cards */
    .event-card {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        transition: 0.3s;
        border-radius: 10px;
        background: white;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .event-card:hover {
        box-shadow: 0 8px 16px 0 rgba(31, 94, 79, 0.3);
        transform: translateY(-5px);
    }

    .event-card-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        background: linear-gradient(135deg, #1f5e4f, #2a7f6e);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        position: relative;
    }

    .event-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .event-card-image .no-image {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .event-card-image .no-image i {
        font-size: 3rem;
        opacity: 0.7;
    }

    .event-status-badge-simple {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 5px;
        z-index: 2;
    }

    .event-card-content {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .event-card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0b2b3c;
        margin-bottom: 10px;
        line-height: 1.4;
    }

    .event-card-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
    }

    .event-card-detail {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #4a6572;
    }

    .event-card-detail i {
        width: 16px;
        color: #1f5e4f;
    }

    .event-card-description {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 15px;
        line-height: 1.5;
        flex: 1;
    }

    .event-card-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: auto;
        border-top: 1px solid #e9ecef;
        padding-top: 12px;
    }

    /* Action Buttons */
    .btn-edit,
    .btn-delete {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .btn-edit {
        background: #ecf5f2;
        color: #1f5e4f;
        border-color: #b7d5ce;
    }

    .btn-edit:hover {
        background: #d3ece5;
        border-color: #1f5e4f;
    }

    .btn-delete {
        background: #ffeeed;
        color: #a1463c;
        border-color: #f3cdca;
    }

    .btn-delete:hover {
        background: #ffdbd8;
        border-color: #a1463c;
    }

    /* Pagination styles */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        margin-bottom: 2rem;
    }

    .pagination-wrapper {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1rem;
        background: white;
        padding: 1rem 2rem;
        border-radius: 60px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }

    .pagination-info {
        color: #1f5e4f;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
        justify-content: center;
    }

    .pagination li a,
    .pagination li span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 6px;
        border-radius: 36px;
        background: white;
        color: #1f5e4f;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        border: 1px solid #e9ecef;
        transition: all 0.2s;
        cursor: pointer;
    }

    .pagination li a:hover {
        background: #ecf5f2;
        border-color: #1f5e4f;
    }

    .pagination li.active span,
    .pagination li.active a {
        background: #1f5e4f;
        color: white;
        border-color: #1f5e4f;
    }

    .pagination li.disabled span,
    .pagination li.disabled a {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

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
    }

    /* ===== HEADER ===== */
    .app-header {
        background: white;
        border-radius: var(--radius-lg);
        padding: 2rem 2.2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        border: 1px solid rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(4px);
    }

    .title-section h1 {
        font-weight: 700;
        font-size: 1.95rem;
        letter-spacing: -0.02em;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .title-section h1 i {
        color: var(--primary-light);
        font-size: 2rem;
    }

    .sub {
        color: var(--text-light);
        font-weight: 500;
        margin-top: 6px;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .status-filter {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .status-filter select {
        padding: 0.5rem 2rem 0.5rem 1rem;
        border-radius: 50px;
        border: 1px solid var(--border);
        background: white;
        color: var(--text-dark);
        font-weight: 500;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%234a6572' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
    }

    .create-btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: 0.9rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 6px 14px rgba(26, 83, 70, 0.25);
        transition: all 0.2s ease;
        cursor: pointer;
        border: 1px solid #358070;
    }

    .create-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(21, 77, 64, 0.3);
    }

    .create-btn:active {
        transform: translateY(0);
    }

    /* ===== FORM ===== */
    .event-form-panel {
        background: white;
        border-radius: var(--radius-md);
        padding: 2rem;
        margin-bottom: 2.5rem;
        box-shadow: var(--shadow);
        border: 1px solid #deecf0;
        transition: all 0.3s ease;
    }

    .event-form-panel.hidden {
        display: none;
        opacity: 0;
        transform: translateY(-20px);
    }

    .form-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: var(--primary-dark);
        font-size: 1.3rem;
        margin-bottom: 1.8rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .input-group.full-width {
        grid-column: 1 / -1;
    }

    .input-group label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #3d5a65;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .input-group label i {
        color: var(--primary-light);
    }

    .input-group input,
    .input-group textarea,
    .input-group select {
        padding: 0.8rem 1rem;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--border);
        background: #fbfdfe;
        font-size: 0.95rem;
        transition: all 0.15s ease;
        outline: none;
        width: 100%;
    }

    .input-group input:focus,
    .input-group textarea:focus,
    .input-group select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 4px rgba(42, 127, 110, 0.08);
        background: white;
    }

    .input-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-row {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .btn-primary,
    .btn-secondary {
        padding: 0.8rem 1.8rem;
        border-radius: 40px;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s ease;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
        border-color: #358070;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-secondary {
        background: var(--secondary);
        color: #1e4a5e;
        border-color: #cbdae2;
    }

    .btn-secondary:hover {
        background: #dfeaf0;
    }

    /* ===== EVENTS GRID ===== */
    .events-grid-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .events-grid-title h2 {
        font-weight: 650;
        color: var(--text-dark);
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .counter-badge {
        background: var(--primary);
        color: white;
        padding: 0.3rem 1rem;
        border-radius: 40px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .empty-state {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(6px);
        border-radius: 48px;
        padding: 4rem 2rem;
        text-align: center;
        border: 2px dashed #9eb9bf;
        color: #2c5a61;
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .empty-state i {
        font-size: 4rem;
        color: #54878c;
        opacity: 0.8;
    }

    /* Image upload */
    .image-upload {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .image-preview {
        width: 100%;
        max-height: 150px;
        object-fit: cover;
        border-radius: var(--radius-sm);
        border: 2px solid var(--border);
        margin-top: 8px;
    }

    .selected-file-info {
        margin-top: 10px;
        padding: 8px;
        border-radius: 5px;
        background: #d4edda;
        color: #155724;
        display: none;
    }

    .selected-file-info.show {
        display: block;
    }

    .file-upload-area {
        border: 2px dashed var(--primary);
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
    }

    .file-upload-area:hover {
        background: #e9ecef;
        border-color: var(--primary-dark);
    }

    .file-upload-area i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 10px;
    }

    /* Toast notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .toast {
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        border-left: 4px solid;
        animation: slideIn 0.3s ease;
        min-width: 300px;
    }

    .toast-success {
        border-left-color: var(--success);
    }

    .toast-error {
        border-left-color: var(--danger);
    }

    .toast-warning {
        border-left-color: var(--warning);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .loading {
        text-align: center;
        padding: 4rem;
        color: var(--primary-light);
        grid-column: 1 / -1;
    }

    .hidden {
        display: none !important;
    }
</style>

<div class="events-container">
    <!-- Toast notifications container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- HEADER -->
    <div class="app-header">
        <div class="title-section">
            <h1><i class="fas fa-calendar-alt"></i> Event Manager</h1>
            <div class="sub">
                <i class="fas fa-database"></i> Events Database
                <div class="status-filter">
                    <select id="statusFilter">
                        <option value="all">📋 All Events</option>
                        <option value="active">🟢 Active</option>
                        <option value="warning">🟡 Warning</option>
                        <option value="danger">🔴 Danger</option>
                        <option value="upcoming">🔵 Upcoming</option>
                        <option value="ended">⚫ Ended</option>
                    </select>
                </div>
            </div>
        </div>
        <button class="create-btn" id="showCreateFormBtn">
            <i class="fas fa-plus-circle"></i> Create New Event
        </button>
    </div>

    <!-- CREATE/UPDATE FORM with new fields -->
    <div id="eventFormPanel" class="event-form-panel hidden">
        <div class="form-title">
            <i class="fas fa-pen-to-square" id="formIcon"></i>
            <span id="formHeading">➕ Create New Event</span>
        </div>

        <form id="eventForm" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Title -->
                <div class="input-group full-width">
                    <label><i class="fas fa-tag"></i> Event Title *</label>
                    <input type="text" id="eventTitleInput" placeholder="e.g., Annual Tech Summit 2024" required>
                </div>

                <!-- Date and Duration -->
                <div class="input-group">
                    <label><i class="fas fa-calendar-day"></i> Event Date</label>
                    <input type="date" id="eventDateInput" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="input-group">
                    <label><i class="fas fa-hourglass-half"></i> Duration (minutes)</label>
                    <input type="number" id="eventDurationInput" placeholder="e.g., 120" min="1" step="1">
                </div>

                <!-- Location -->
                <div class="input-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" id="eventLocationInput" placeholder="Venue or online link">
                </div>

                <!-- Project Manager -->
                <div class="input-group">
                    <label><i class="fas fa-user-tie"></i> Project Manager</label>
                    <input type="text" id="eventManagerInput" placeholder="Name of project manager">
                </div>

                <!-- Image Upload -->
                <div class="input-group full-width">
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4 style="color: #333; margin-bottom: 10px;">Upload Event Image</h4>
                        <p style="color: #666; margin-bottom: 15px;">Click the button below to select an image (JPEG, PNG, GIF, WEBP - max 5MB)</p>
                        <input type="file" id="eventImageInput" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div id="selectedFileInfo" class="selected-file-info">
                            <i class="fas fa-check-circle"></i>
                            <span id="selectedFileName">No file selected</span>
                        </div>
                    </div>

                    <div class="image-upload">
                        <img id="imagePreview" class="image-preview hidden" alt="Preview">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i>
                            Select a file above to upload. Current image will be replaced.
                        </small>
                    </div>
                </div>


                <!-- Description -->
                <div class="input-group full-width">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="eventDescInput" placeholder="Event details, agenda, special instructions..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="saveEventBtn">
                    <i class="fas fa-check-circle"></i> <span id="saveBtnText">Save Event</span>
                </button>
                <button type="button" class="btn-secondary" id="cancelFormBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>

        <input type="hidden" id="editingEventId" value="">
        <input type="hidden" id="existingImageUrl" value="">
    </div>

    <!-- EVENTS GRID -->
    <div class="events-grid-title">
        <h2><i class="fas fa-list"></i> Your Events</h2>
        <span class="counter-badge" id="eventCounter">0</span>
    </div>

    <div id="eventGrid" class="event-cards">
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i> Loading events...
        </div>
    </div>
</div>

<script>
    // State
    let events = [];
    let refreshInterval = null;

    // Pagination - 4 cards per row × 2 rows = 8 items per page
    let currentPage = 1;
    let itemsPerPage = 4; // This gives you 2 rows of 4 cards
    let totalPages = 1;

    (function() {
        "use strict";

        // Configuration
        const CONFIG = {
            API_URL: 'api/events.php',
            MAX_IMAGE_SIZE: 10 * 1024 * 1024, // Changed from 5MB to 10MB
            ALLOWED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            REFRESH_INTERVAL: 60000 // Auto-refresh every minute
        };

        // DOM Elements
        const elements = {
            panel: document.getElementById('eventFormPanel'),
            form: document.getElementById('eventForm'),
            formHeading: document.getElementById('formHeading'),
            formIcon: document.getElementById('formIcon'),
            saveBtnText: document.getElementById('saveBtnText'),
            title: document.getElementById('eventTitleInput'),
            date: document.getElementById('eventDateInput'),
            duration: document.getElementById('eventDurationInput'),
            location: document.getElementById('eventLocationInput'),
            manager: document.getElementById('eventManagerInput'),
            image: document.getElementById('eventImageInput'),
            imagePreview: document.getElementById('imagePreview'),
            description: document.getElementById('eventDescInput'),
            editingId: document.getElementById('editingEventId'),
            existingImage: document.getElementById('existingImageUrl'),
            grid: document.getElementById('eventGrid'),
            counter: document.getElementById('eventCounter'),
            showCreateBtn: document.getElementById('showCreateFormBtn'),
            cancelBtn: document.getElementById('cancelFormBtn'),
            saveBtn: document.getElementById('saveEventBtn'),
            toastContainer: document.getElementById('toastContainer'),
            statusFilter: document.getElementById('statusFilter')
        };

        // State
        let events = [];
        let refreshInterval = null;

        // ===== UTILITY FUNCTIONS =====
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                                     type === 'error' ? 'fa-exclamation-circle' : 
                                     'fa-exclamation-triangle'}" 
                       style="color: ${type === 'success' ? '#28a745' : 
                                      type === 'error' ? '#dc3545' : 
                                      '#ffc107'}; font-size: 1.2rem;"></i>
                    <span style="flex: 1;">${escapeHTML(message)}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="border: none; background: none; cursor: pointer; opacity: 0.5;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            elements.toastContainer.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'No date set';
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch {
                return dateStr;
            }
        }

        // ===== COUNTDOWN FUNCTIONS =====
        function calculateEventStatus(eventDate, durationMinutes) {
            if (!eventDate) return {
                status: 'upcoming',
                timeLeft: null,
                daysLeft: null,
                hoursLeft: null,
                minutesLeft: null
            };

            const now = new Date();
            const eventStart = new Date(eventDate);
            const eventEnd = new Date(eventStart.getTime() + (durationMinutes || 0) * 60000);

            // For upcoming events (not started yet)
            if (now < eventStart) {
                const diffMs = eventStart - now;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                return {
                    status: 'upcoming',
                    timeLeft: {
                        days: diffDays,
                        hours: diffHours,
                        minutes: diffMinutes
                    },
                    daysLeft: diffDays,
                    hoursLeft: diffHours,
                    minutesLeft: diffMinutes
                };
            }

            // For events that have ended
            if (now > eventEnd) {
                return {
                    status: 'ended',
                    timeLeft: null,
                    daysLeft: null,
                    hoursLeft: null,
                    minutesLeft: null
                };
            }

            // For active events (currently happening)
            const minutesLeft = Math.floor((eventEnd - now) / 60000);
            const daysLeft = Math.floor(minutesLeft / (24 * 60));
            const hoursLeft = Math.floor((minutesLeft % (24 * 60)) / 60);
            const minsLeft = minutesLeft % 60;

            if (minutesLeft > 1440) { // More than 1 day
                return {
                    status: 'active',
                    timeLeft: {
                        days: daysLeft,
                        hours: hoursLeft,
                        minutes: minsLeft
                    },
                    daysLeft: daysLeft,
                    hoursLeft: hoursLeft,
                    minutesLeft: minsLeft
                };
            } else if (minutesLeft > 60) { // More than 1 hour
                return {
                    status: 'active',
                    timeLeft: {
                        days: 0,
                        hours: hoursLeft,
                        minutes: minsLeft
                    },
                    daysLeft: 0,
                    hoursLeft: hoursLeft,
                    minutesLeft: minsLeft
                };
            } else if (minutesLeft > 30) {
                return {
                    status: 'active',
                    timeLeft: {
                        days: 0,
                        hours: 0,
                        minutes: minutesLeft
                    },
                    daysLeft: 0,
                    hoursLeft: 0,
                    minutesLeft: minutesLeft
                };
            } else if (minutesLeft > 10) {
                return {
                    status: 'warning',
                    timeLeft: {
                        days: 0,
                        hours: 0,
                        minutes: minutesLeft
                    },
                    daysLeft: 0,
                    hoursLeft: 0,
                    minutesLeft: minutesLeft
                };
            } else {
                return {
                    status: 'danger',
                    timeLeft: {
                        days: 0,
                        hours: 0,
                        minutes: minutesLeft
                    },
                    daysLeft: 0,
                    hoursLeft: 0,
                    minutesLeft: minutesLeft
                };
            }
        }

        function formatTimeLeft(timeLeft) {
            if (!timeLeft) return '';

            const parts = [];
            if (timeLeft.days > 0) {
                parts.push(`${timeLeft.days}d`);
            }
            if (timeLeft.hours > 0) {
                parts.push(`${timeLeft.hours}h`);
            }
            if (timeLeft.minutes > 0 && timeLeft.days === 0) {
                // Only show minutes if less than a day
                parts.push(`${timeLeft.minutes}m`);
            }

            return parts.join(' ');
        }

        function formatTimeLeft(timeLeft) {
            if (!timeLeft) return '';

            const parts = [];
            if (timeLeft.days > 0) {
                parts.push(`${timeLeft.days}d`);
            }
            if (timeLeft.hours > 0) {
                parts.push(`${timeLeft.hours}h`);
            }
            if (timeLeft.minutes > 0 && timeLeft.days === 0) {
                // Only show minutes if less than a day
                parts.push(`${timeLeft.minutes}m`);
            }

            return parts.join(' ');
        }

        // ===== API FUNCTIONS =====
        async function fetchEvents() {
            try {
                const response = await fetch(CONFIG.API_URL);
                const text = await response.text(); // Get as text first for debugging

                try {
                    const data = JSON.parse(text);
                    events = data;
                    filterAndRenderEvents();
                } catch (e) {
                    console.error('Invalid JSON response:', text.substring(0, 200));
                    showToast('Server returned invalid response', 'error');
                    elements.grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Server Error</h3>
                    <p>The API returned an invalid response. Check the console for details.</p>
                    <p class="text-muted small">${escapeHTML(text.substring(0, 100))}...</p>
                </div>
            `;
                }
            } catch (error) {
                console.error('Error fetching events:', error);
                showToast('Failed to connect to server', 'error');
            }
        }

        async function createEvent(formData) {
            try {
                // Log FormData contents for debugging
                console.log('Creating event with FormData:');
                for (let pair of formData.entries()) {
                    if (pair[0] === 'image' && pair[1] instanceof File) {
                        console.log(pair[0], '(File):', pair[1].name, pair[1].type, pair[1].size);
                    } else {
                        console.log(pair[0], ':', pair[1]);
                    }
                }

                const response = await fetch(CONFIG.API_URL, {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                console.log('Server response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showToast('Server returned invalid response', 'error');
                    return false;
                }

                if (result.success) {
                    showToast('Event created successfully!', 'success');
                    resetForm();
                    await fetchEvents();
                    return true;
                } else {
                    showToast(result.error || 'Failed to create event', 'error');
                    return false;
                }
            } catch (error) {
                console.error('Error creating event:', error);
                showToast('Failed to connect to server', 'error');
                return false;
            }
        }

        async function updateEvent(formData) {
            try {
                // Log FormData contents for debugging
                console.log('=== UPDATING EVENT WITH IMAGE CHECK ===');
                let hasImage = false;
                for (let pair of formData.entries()) {
                    if (pair[0] === 'image' && pair[1] instanceof File) {
                        hasImage = true;
                        console.log('✅ IMAGE FOUND:', pair[1].name, pair[1].type, (pair[1].size / 1024).toFixed(2) + 'KB');
                    } else if (pair[0] === 'image') {
                        console.log(pair[0], ':', pair[1]);
                    } else {
                        console.log(pair[0], ':', pair[1]);
                    }
                }

                if (!hasImage) {
                    console.log('⚠️ No image file in form data');
                }

                // For updates, we need to use POST with _method override
                formData.append('_method', 'PUT');

                const response = await fetch(CONFIG.API_URL, {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                console.log('Server response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showToast('Server returned invalid response', 'error');
                    return false;
                }

                if (result.success) {
                    showToast('Event updated successfully!', 'success');
                    return true;
                } else {
                    showToast(result.error || 'Failed to update event', 'error');
                    return false;
                }
            } catch (error) {
                console.error('Error updating event:', error);
                showToast('Failed to connect to server', 'error');
                return false;
            }
        }

        async function deleteEvent(id) {
            if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`${CONFIG.API_URL}?id=${id}`, {
                    method: 'DELETE',
                });

                const text = await response.text();
                console.log('Delete response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    showToast('Server returned invalid response', 'error');
                    return;
                }

                if (result.success) {
                    showToast('Event deleted successfully!', 'success');
                    if (elements.editingId.value === id) {
                        resetForm();
                    }
                    await fetchEvents();
                } else {
                    showToast(result.error || 'Failed to delete event', 'error');
                }
            } catch (error) {
                console.error('Error deleting event:', error);
                showToast('Failed to delete event: ' + error.message, 'error');
            }
        }
        // ===== RENDERING =====
        function filterAndRenderEvents() {
            const filter = elements.statusFilter.value;
            let filteredEvents = events;

            if (filter !== 'all') {
                filteredEvents = events.filter(event => {
                    const status = calculateEventStatus(event.date, event.duration).status;
                    return status === filter;
                });
            }

            renderEvents(filteredEvents);
        }

        function renderEvents(eventsToRender) {
            if (!elements.grid) return;

            // Calculate pagination
            totalPages = Math.ceil(eventsToRender.length / itemsPerPage);
            if (currentPage > totalPages) currentPage = totalPages || 1;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedEvents = eventsToRender.slice(startIndex, endIndex);

            if (paginatedEvents.length === 0) {
                elements.grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>No Events Found</h3>
                <p>${events.length === 0 ? 
                    'Create your first event using the button above' : 
                    'No events match the selected filter'}</p>
            </div>
        `;
                elements.counter.textContent = events.length;
                renderPagination();
                return;
            }

            let cards = '';
            paginatedEvents.forEach(event => {
                const status = calculateEventStatus(event.date, event.duration);
                const timeLeft = formatTimeLeft(status.timeLeft);

                // Handle different possible column names
                const location = event.location || event.event_location || '';
                const manager = event.project_manager || event.manager || event.projectManager || '';
                const imageUrl = event.event_image || event.image || event.image_url || null;
                const duration = event.duration || event.event_duration || '';

                // Format date
                const formattedDate = event.date ? formatDate(event.date) : 'No date set';

                // Get status config
                // Get status config
                const statusConfig = {
                    active: {
                        color: '#28a745',
                        icon: 'fa-play',
                        label: 'ACTIVE'
                    },
                    warning: {
                        color: '#ffc107',
                        icon: 'fa-clock',
                        label: 'WARNING'
                    },
                    danger: {
                        color: '#dc3545',
                        icon: 'fa-exclamation',
                        label: 'DANGER'
                    },
                    upcoming: {
                        color: '#17a2b8',
                        icon: 'fa-calendar',
                        label: 'UPCOMING'
                    },
                    ended: {
                        color: '#6c757d',
                        icon: 'fa-stop',
                        label: 'ENDED'
                    }
                };
                const config = statusConfig[status.status] || statusConfig.upcoming;

                cards += `
    <div class="column">
        <div class="event-card">
            <div class="event-card-image">
                ${imageUrl ? `
                    <img src="${escapeHTML(imageUrl)}" alt="${escapeHTML(event.title)}">
                ` : `
                    <div class="no-image">
                        <i class="fas fa-calendar-alt"></i>
                        <span>No Image</span>
                    </div>
                `}
                <div class="event-status-badge-simple" style="background-color: ${config.color};">
                    <i class="fas ${config.icon}"></i>
                    ${config.label}
                </div>
            </div>
            
            <div class="event-card-content">
                <h3 class="event-card-title">${escapeHTML(event.title || 'Untitled')}</h3>
                
                <div class="event-card-details">
                    <div class="event-card-detail">
                        <i class="fas fa-calendar-alt"></i>
                        <span>${escapeHTML(formattedDate)}</span>
                    </div>
                    
                    ${location ? `
                        <div class="event-card-detail">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${escapeHTML(location)}</span>
                        </div>
                    ` : ''}
                    
                    ${manager ? `
                        <div class="event-card-detail">
                            <i class="fas fa-user-tie"></i>
                            <span>${escapeHTML(manager)}</span>
                        </div>
                    ` : ''}
                    
                    ${duration ? `
                        <div class="event-card-detail">
                            <i class="fas fa-hourglass-half"></i>
                            <span>${escapeHTML(duration)} min</span>
                        </div>
                    ` : ''}
                    
                    ${status.timeLeft ? `
                        <div class="event-card-detail" style="color: ${config.color}; font-weight: 600; background: ${config.color}10; padding: 5px 8px; border-radius: 5px; margin-top: 5px;">
                            <i class="fas ${status.status === 'active' ? 'fa-hourglass-half' : 
                                           status.status === 'warning' ? 'fa-exclamation-triangle' :
                                           status.status === 'danger' ? 'fa-exclamation-circle' :
                                           status.status === 'upcoming' ? 'fa-clock' : 'fa-stop'}"></i>
                            <span>
                                ${status.status === 'upcoming' ? 'Starts in' : 'Ends in'}: 
                                <strong>${escapeHTML(formatTimeLeft(status.timeLeft))}</strong>
                            </span>
                        </div>
                    ` : status.status === 'ended' ? `
                        <div class="event-card-detail" style="color: #6c757d;">
                            <i class="fas fa-check-circle"></i>
                            <span>Event ended</span>
                        </div>
                    ` : ''}
                </div>
                
                ${event.description ? `
                    <div class="event-card-description">
                        ${escapeHTML(event.description).replace(/\n/g, '<br>')}
                    </div>
                ` : ''}
                
                <div class="event-card-actions">
                    <button class="btn-edit" data-id="${escapeHTML(event.id)}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete" data-id="${escapeHTML(event.id)}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
`;
            });

            elements.grid.innerHTML = `
        <div class="row">
            ${cards}
        </div>
    `;

            elements.counter.textContent = events.length;
            renderPagination();

            // Attach event listeners
            elements.grid.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', handleEdit);
            });
            elements.grid.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = e.currentTarget.getAttribute('data-id');
                    deleteEvent(id);
                });
            });
        }



        // Scroll function for horizontal navigation
        window.scrollCards = function(direction) {
            const wrapper = document.getElementById('cardsWrapper');
            const scrollAmount = 320; // Width of one card + gap

            if (direction === 'left') {
                wrapper.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else {
                wrapper.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        };



        function renderPagination() {
            // Remove existing pagination
            const existingPagination = document.getElementById('paginationContainer');
            if (existingPagination) existingPagination.remove();

            if (totalPages <= 1) return;

            const paginationContainer = document.createElement('div');
            paginationContainer.id = 'paginationContainer';
            paginationContainer.className = 'pagination-container';

            // Calculate which items are being shown
            const startItem = ((currentPage - 1) * itemsPerPage) + 1;
            const endItem = Math.min(currentPage * itemsPerPage, events.length);

            let paginationHtml = `
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Showing ${startItem} - ${endItem} of ${events.length} events
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

            // Page numbers with smart ellipsis
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            // Adjust start if we're near the end
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
                <option value="4" ${itemsPerPage === 4 ? 'selected' : ''}>4 per page</option>
                <option value="8" ${itemsPerPage === 8 ? 'selected' : ''}>8 per page</option>
                <option value="12" ${itemsPerPage === 12 ? 'selected' : ''}>12 per page</option>
                <option value="16" ${itemsPerPage === 16 ? 'selected' : ''}>16 per page</option>
                <option value="20" ${itemsPerPage === 20 ? 'selected' : ''}>20 per page</option>
            </select>
        </div>
    `;

            paginationContainer.innerHTML = paginationHtml;
            elements.grid.parentNode.insertBefore(paginationContainer, elements.grid.nextSibling);
        }


        // Global functions for pagination
        window.changePage = function(newPage) {
            if (newPage < 1 || newPage > totalPages || newPage === currentPage) return;
            currentPage = newPage;
            filterAndRenderEvents();
        };

        window.changeItemsPerPage = function(newValue) {
            itemsPerPage = parseInt(newValue);
            currentPage = 1;
            filterAndRenderEvents();
        };



        // ===== FORM HANDLING =====
        function handleImagePreview() {
            const file = elements.image.files[0];
            const selectedFileInfo = document.getElementById('selectedFileInfo');
            const selectedFileName = document.getElementById('selectedFileName');

            if (!file) {
                console.log('No file selected');
                if (selectedFileInfo) selectedFileInfo.classList.remove('show');
                return;
            }

            console.log('=== ✅ FILE SELECTED ===');
            console.log('File name:', file.name);
            console.log('File type:', file.type);
            console.log('File size:', (file.size / 1024).toFixed(2), 'KB');

            // Update the selected file info
            if (selectedFileName) {
                selectedFileName.textContent = `${file.name} (${(file.size/1024).toFixed(2)} KB)`;
            }
            if (selectedFileInfo) {
                selectedFileInfo.classList.add('show');
            }

            // Validate file type
            if (!CONFIG.ALLOWED_IMAGE_TYPES.includes(file.type)) {
                showToast('❌ Invalid file type. Please upload an image (JPEG, PNG, GIF, WEBP)', 'error');
                elements.image.value = '';
                if (selectedFileInfo) selectedFileInfo.classList.remove('show');
                return;
            }

            // Validate file size with clear message
            if (file.size > CONFIG.MAX_IMAGE_SIZE) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                const maxMB = CONFIG.MAX_IMAGE_SIZE / (1024 * 1024);
                showToast(`❌ File too large: ${sizeMB}MB. Maximum size is ${maxMB}MB`, 'error');
                console.log(`❌ File rejected: ${sizeMB}MB exceeds ${maxMB}MB limit`);
                elements.image.value = '';
                if (selectedFileInfo) selectedFileInfo.classList.remove('show');

                // Change the file info to show error
                if (selectedFileName) {
                    selectedFileName.textContent = `❌ File too large! Max ${maxMB}MB`;
                    selectedFileName.style.color = 'red';
                }
                return;
            }

            // Add this right after validating the title
            console.log('Current MAX_IMAGE_SIZE:', CONFIG.MAX_IMAGE_SIZE / (1024 * 1024), 'MB');

            showToast(`✅ File selected: ${file.name} (${(file.size/1024).toFixed(2)} KB)`, 'success');

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                elements.imagePreview.src = e.target.result;
                elements.imagePreview.classList.remove('hidden');
                elements.existingImage.value = '';
            };
            reader.readAsDataURL(file);
        }


        // Add test button


        // Add click handler to the file upload area
        const uploadArea = document.getElementById('fileUploadArea');
        if (uploadArea) {
            uploadArea.addEventListener('click', function() {
                elements.image.click();
            });
            uploadArea.style.cursor = 'pointer';
        }

        async function handleFormSubmit(e) {
            e.preventDefault();

            const title = elements.title.value.trim();
            if (!title) {
                showToast('Event title is required', 'error');
                elements.title.focus();
                return;
            }

            const formData = new FormData();
            formData.append('id', elements.editingId.value || generateUUID());
            formData.append('title', title);
            formData.append('date', elements.date.value || '');
            formData.append('duration', elements.duration.value || '');
            formData.append('location', elements.location.value || '');
            formData.append('manager', elements.manager.value || '');
            formData.append('description', elements.description.value || '');

            // Handle image upload - with clear logging
            if (elements.image.files && elements.image.files.length > 0) {
                const file = elements.image.files[0];
                console.log('✅ FILE SELECTED FOR UPLOAD:', file.name);
                console.log('   Type:', file.type);
                console.log('   Size:', (file.size / 1024).toFixed(2), 'KB');
                formData.append('image', file);
            } else {
                console.log('⚠️ No file selected - keeping existing image');
                if (elements.existingImage.value) {
                    console.log('   Keeping existing image:', elements.existingImage.value);
                    formData.append('existingImage', elements.existingImage.value);
                }
            }

            // Log what we're sending
            console.log('📤 Sending FormData:');
            for (let pair of formData.entries()) {
                if (pair[0] === 'image' && pair[1] instanceof File) {
                    console.log('   📷', pair[0], ':', pair[1].name);
                } else {
                    console.log('   📝', pair[0], ':', pair[1] || '(empty)');
                }
            }

            elements.saveBtn.disabled = true;
            elements.saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                let success;
                if (elements.editingId.value) {
                    success = await updateEvent(formData);
                } else {
                    success = await createEvent(formData);
                }

                if (success) {
                    resetForm();
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showToast('Error saving event: ' + error.message, 'error');
            } finally {
                elements.saveBtn.disabled = false;
                elements.saveBtn.innerHTML = '<i class="fas fa-check-circle"></i> <span>Save Event</span>';
            }
        }


        function handleEdit(e) {
            const id = e.currentTarget.getAttribute('data-id');
            const event = events.find(e => e.id === id);

            if (!event) return;

            console.log('Editing event:', event);

            // Use the correct field names from your database
            const location = event.location || '';
            const manager = event.project_manager || ''; // Use project_manager, not manager
            const duration = event.duration || '';
            const imageUrl = event.event_image || null; // Use event_image, not image

            // Fill form with event data
            elements.title.value = event.title || '';
            elements.date.value = event.date || '';
            elements.duration.value = duration;
            elements.location.value = location;
            elements.manager.value = manager; // This will be sent as 'manager' but API maps to project_manager
            elements.description.value = event.description || '';
            elements.editingId.value = event.id;

            // Handle image
            if (imageUrl) {
                elements.existingImage.value = imageUrl;
                elements.imagePreview.src = imageUrl;
                elements.imagePreview.classList.remove('hidden');
            } else {
                elements.existingImage.value = '';
                elements.imagePreview.classList.add('hidden');
                elements.imagePreview.src = '';
            }

            // Update form UI
            elements.formHeading.innerText = '✏️ Edit Event';
            elements.formIcon.className = 'fas fa-edit';
            elements.saveBtnText.innerText = 'Update Event';

            elements.panel.classList.remove('hidden');
            elements.panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }


        function resetForm() {
            elements.form.reset();
            elements.editingId.value = '';
            elements.existingImage.value = '';
            elements.imagePreview.classList.add('hidden');
            elements.imagePreview.src = '';
            elements.image.value = ''; // Clear file input

            elements.formHeading.innerText = '➕ Create New Event';
            elements.formIcon.className = 'fas fa-plus-circle';
            elements.saveBtnText.innerText = 'Save Event';

            elements.panel.classList.add('hidden');
        }

        function showCreateForm() {
            resetForm();
            elements.panel.classList.remove('hidden');
            elements.title.focus();
        }

        // ===== AUTO-REFRESH =====
        function startAutoRefresh() {
            if (refreshInterval) clearInterval(refreshInterval);
            refreshInterval = setInterval(fetchEvents, CONFIG.REFRESH_INTERVAL);
        }

        // Add this temporary debug function
        function debugDatabaseStructure() {
            if (events.length > 0) {
                console.log('=== DATABASE FIELDS ===');
                console.log('First event object:', events[0]);
                console.log('Available fields:', Object.keys(events[0]));
                console.log('Location field exists:', 'location' in events[0]);
                console.log('project_manager field exists:', 'project_manager' in events[0]);
                console.log('manager field exists:', 'manager' in events[0]);
                console.log('event_image field exists:', 'event_image' in events[0]);
                console.log('image field exists:', 'image' in events[0]);
            }
        }

        // Call it after fetching events
        // Call it after fetching events
        async function fetchEvents() {
            try {
                const response = await fetch(CONFIG.API_URL);
                const text = await response.text();

                try {
                    const data = JSON.parse(text);
                    events = data;

                    // Debug: Log all images
                    console.log('=== IMAGE DEBUG ===');
                    events.forEach(event => {
                        console.log(`Event: ${event.title}`);
                        console.log(`  event_image:`, event.event_image);
                        console.log(`  image exists:`, !!event.event_image);
                        if (event.event_image) {
                            // Test if image loads
                            const img = new Image();
                            img.onload = () => console.log(`  ✅ Image loads: ${event.event_image}`);
                            img.onerror = () => console.log(`  ❌ Image FAILS: ${event.event_image}`);
                            img.src = event.event_image;
                        }
                    });

                    debugDatabaseStructure();
                    filterAndRenderEvents();
                } catch (e) {
                    console.error('Invalid JSON response:', text.substring(0, 200));
                    showToast('Server returned invalid response', 'error');
                }
            } catch (error) {
                console.error('Error fetching events:', error);
                showToast('Failed to connect to server', 'error');
            }
        }

        // ===== INITIALIZATION =====
        function init() {
            // Load initial data
            fetchEvents();
            startAutoRefresh();

            // Event listeners
            elements.showCreateBtn.addEventListener('click', showCreateForm);
            elements.cancelBtn.addEventListener('click', resetForm);
            elements.form.addEventListener('submit', handleFormSubmit);
            elements.image.addEventListener('change', handleImagePreview);
            elements.statusFilter.addEventListener('change', filterAndRenderEvents);

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !elements.panel.classList.contains('hidden')) {
                    resetForm();
                }
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                if (refreshInterval) clearInterval(refreshInterval);
            });
        }

        init();
    })();
</script>

<?php require_once 'views/partials/footer.php'; ?>