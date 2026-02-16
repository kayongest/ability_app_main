<?php
// test_item.php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Item Data Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; max-height: 400px; overflow: auto; }
        .data-section { margin-bottom: 30px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>🔍 Item Data Debug Tool</h1>
        <p class="lead">Test and verify item data retrieval</p>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Test Single Item</h5>
                    </div>
                    <div class="card-body">
                        <form id="testItemForm">
                            <div class="mb-3">
                                <label>Item ID:</label>
                                <input type="number" class="form-control" id="testItemId" value="245" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Fetch Item Data</button>
                        </form>
                        
                        <div id="singleResult" class="mt-3" style="display: none;">
                            <h6>Result:</h6>
                            <pre id="singleData"></pre>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Test Multiple Items</h5>
                    </div>
                    <div class="card-body">
                        <form id="testItemsForm">
                            <div class="mb-3">
                                <label>Item IDs (comma separated):</label>
                                <input type="text" class="form-control" id="testItemIds" value="241,242,243,244,245">
                            </div>
                            <button type="submit" class="btn btn-success">Fetch Multiple Items</button>
                        </form>
                        
                        <div id="multipleResult" class="mt-3" style="display: none;">
                            <h6>Results:</h6>
                            <pre id="multipleData"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Database Structure</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = getConnection();
                        $result = $db->query("DESCRIBE items");
                        echo '<table class="table table-sm table-bordered">';
                        echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
                        echo '<tbody>';
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td><strong>' . $row['Field'] . '</strong></td>';
                            echo '<td>' . $row['Type'] . '</td>';
                            echo '<td>' . $row['Null'] . '</td>';
                            echo '<td>' . $row['Key'] . '</td>';
                            echo '<td>' . ($row['Default'] ?? 'NULL') . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5>Sample Data Check</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $result = $db->query("SELECT id, item_name, serial_number, category, status, created_at FROM items ORDER BY id DESC LIMIT 5");
                        echo '<table class="table table-hover">';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Serial</th><th>Category</th><th>Status</th><th>Created</th></tr></thead>';
                        echo '<tbody>';
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td><a href="#" onclick="testItem(' . $row['id'] . '); return false;">' . $row['id'] . '</a></td>';
                            echo '<td>' . htmlspecialchars($row['item_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['serial_number']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '<td>' . $row['created_at'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function testItem(id) {
            $('#testItemId').val(id);
            $('#testItemForm').submit();
        }
        
        $('#testItemForm').on('submit', function(e) {
            e.preventDefault();
            
            const itemId = $('#testItemId').val();
            $('#singleResult').show();
            $('#singleData').text('Loading...');
            
            fetch(`api/debug_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    $('#singleData').text(JSON.stringify(data, null, 2));
                })
                .catch(error => {
                    $('#singleData').text('Error: ' + error.message);
                });
        });
        
        $('#testItemsForm').on('submit', function(e) {
            e.preventDefault();
            
            const ids = $('#testItemIds').val().split(',').map(id => id.trim());
            $('#multipleResult').show();
            $('#multipleData').text('Loading...');
            
            let results = [];
            let promises = ids.map(id => 
                fetch(`api/debug_item.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => ({ id, data }))
                    .catch(error => ({ id, error: error.message }))
            );
            
            Promise.all(promises).then(results => {
                $('#multipleData').text(JSON.stringify(results, null, 2));
            });
        });
    </script>
</body>
</html>