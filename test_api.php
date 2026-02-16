<?php
// test_api.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>API Test</h1>
    
    <button onclick="testGetItem()">Test Get Item (ID 245)</button>
    <button onclick="testCategories()">Test Categories</button>
    <button onclick="testDepartments()">Test Departments</button>
    <button onclick="testAccessories()">Test Accessories</button>
    <button onclick="testAll()">Test All APIs</button>
    
    <pre id="result" style="background:#f4f4f4; padding:10px; margin-top:20px;"></pre>

    <script>
        function testGetItem() {
            fetch('api/get_item.php?id=245')
                .then(handleResponse)
                .then(data => {
                    document.getElementById('result').textContent = 
                        JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    document.getElementById('result').textContent = 'Error: ' + error;
                });
        }

        function testCategories() {
            fetch('api/get_categories.php')
                .then(handleResponse)
                .then(data => {
                    document.getElementById('result').textContent = 
                        JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    document.getElementById('result').textContent = 'Error: ' + error;
                });
        }

        function testDepartments() {
            fetch('api/get_departments.php')
                .then(handleResponse)
                .then(data => {
                    document.getElementById('result').textContent = 
                        JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    document.getElementById('result').textContent = 'Error: ' + error;
                });
        }

        function testAccessories() {
            fetch('api/get_accessories.php')
                .then(handleResponse)
                .then(data => {
                    document.getElementById('result').textContent = 
                        JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    document.getElementById('result').textContent = 'Error: ' + error;
                });
        }

        function testAll() {
            Promise.all([
                fetch('api/get_item.php?id=245').then(r => r.json()),
                fetch('api/get_categories.php').then(r => r.json()),
                fetch('api/get_departments.php').then(r => r.json()),
                fetch('api/get_accessories.php').then(r => r.json())
            ]).then(results => {
                document.getElementById('result').textContent = 
                    JSON.stringify({
                        item: results[0],
                        categories: results[1],
                        departments: results[2],
                        accessories: results[3]
                    }, null, 2);
            }).catch(error => {
                document.getElementById('result').textContent = 'Error: ' + error;
            });
        }

        function handleResponse(response) {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        }
    </script>
</body>
</html>