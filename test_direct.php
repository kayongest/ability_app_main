<?php
// test_direct.php - Direct form test
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Form Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        form { max-width: 500px; }
        input, select { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Direct Form Test</h2>
    <p>This bypasses JavaScript to test the API directly.</p>
    
    <form method="POST" action="/ability_app_main/api/items/create.php" enctype="multipart/form-data">
        <input type="text" name="item_name" placeholder="Item Name" value="Test Item" required><br>
        <input type="text" name="serial_number" placeholder="Serial" value="TEST-<?php echo time(); ?>" required><br>
        <input type="text" name="category" placeholder="Category" value="Electronics" required><br>
        
        <h4>Optional Fields:</h4>
        <input type="text" name="department" placeholder="Department" value="IT"><br>
        <input type="text" name="brand" placeholder="Brand" value="Test Brand"><br>
        <input type="text" name="model" placeholder="Model" value="Test Model"><br>
        
        <h4>Image Upload:</h4>
        <input type="file" name="item_image"><br><br>
        
        <button type="submit">Submit Directly to API</button>
    </form>
    
    <hr>
    
    <h3>Test API Endpoints:</h3>
    <p><a href="/ability_app_main/api/test.php" target="_blank">Test API Connection</a></p>
    <p><a href="/ability_app_main/test_upload.php" target="_blank">Test Upload Settings</a></p>
</body>
</html>