<?php
/**
 * Website Functionality Checker & Progress Tracker
 * Checks all functions & pages and tracks progress on remaining tasks
 */

class WebsiteHealthChecker {
    private $config = [
        'base_url' => 'http://yourwebsite.com', // Change to your base URL
        'timeout' => 30, // Request timeout in seconds
        'output_file' => 'progress_report.json', // JSON output file
        'html_report' => 'progress_report.html'  // HTML report file
    ];
    
    private $pages_to_check = [];
    private $functions_to_check = [];
    private $api_endpoints = [];
    private $database_checks = [];
    private $results = [];
    private $progress = [
        'completed' => 0,
        'pending' => 0,
        'failed' => 0,
        'total' => 0
    ];
    
    public function __construct($custom_config = []) {
        $this->config = array_merge($this->config, $custom_config);
        $this->loadCheckLists();
    }
    
    /**
     * Define what to check - modify this method according to your needs
     */
    private function loadCheckLists() {
        // Define pages to check (URL => expected status code)
        $this->pages_to_check = [
            '/' => 200,
            '/about' => 200,
            '/contact' => 200,
            '/login' => 200,
            '/register' => 200,
            '/dashboard' => 200,
            '/products' => 200,
            '/cart' => 200,
            '/checkout' => 200,
            '/admin' => 200,
            '/admin/users' => 200,
            '/admin/products' => 200,
            '/api/health' => 200,
            '/404-test' => 404, // Expected to fail
        ];
        
        // Define functions to test
        $this->functions_to_check = [
            'Database Connection' => 'checkDatabaseConnection',
            'User Authentication' => 'testUserAuthentication',
            'Email Sending' => 'testEmailFunction',
            'File Upload' => 'testFileUpload',
            'Form Validation' => 'testFormValidation',
            'Session Management' => 'testSessionManagement',
            'CSRF Protection' => 'testCsrfProtection',
            'Data Encryption' => 'testEncryption',
            'Cron Jobs' => 'checkCronJobs',
            'Backup System' => 'testBackupSystem',
        ];
        
        // Define API endpoints to test
        $this->api_endpoints = [
            'GET /api/users' => ['method' => 'GET', 'url' => '/api/users'],
            'POST /api/login' => ['method' => 'POST', 'url' => '/api/login'],
            'GET /api/products' => ['method' => 'GET', 'url' => '/api/products'],
            'POST /api/order' => ['method' => 'POST', 'url' => '/api/order'],
        ];
        
        // Define database checks
        $this->database_checks = [
            'Users Table' => 'SELECT COUNT(*) as count FROM users',
            'Products Table' => 'SELECT COUNT(*) as count FROM products',
            'Orders Table' => 'SELECT COUNT(*) as count FROM orders',
            'Database Size' => 'SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()',
        ];
    }
    
    /**
     * Run all checks
     */
    public function runFullCheck() {
        echo "Starting comprehensive website check...\n";
        echo "========================================\n\n";
        
        $this->results['timestamp'] = date('Y-m-d H:i:s');
        $this->results['base_url'] = $this->config['base_url'];
        
        // Run all checks
        $this->checkPages();
        $this->checkFunctions();
        $this->checkAPIs();
        $this->checkDatabase();
        $this->checkServerEnvironment();
        $this->checkSecurity();
        $this->checkPerformance();
        
        // Calculate progress
        $this->calculateProgress();
        
        // Generate reports
        $this->generateJsonReport();
        $this->generateHtmlReport();
        
        // Display summary
        $this->displaySummary();
        
        return $this->results;
    }
    
    /**
     * Check all pages
     */
    private function checkPages() {
        echo "Checking Pages:\n";
        echo "----------------\n";
        
        $this->results['pages'] = [];
        
        foreach ($this->pages_to_check as $page => $expected_status) {
            $url = $this->config['base_url'] . $page;
            $status = $this->checkPage($url, $expected_status);
            
            $this->results['pages'][$page] = $status;
            
            if ($status['status'] === 'success') {
                echo "✓ {$page} - {$status['http_code']} ({$status['response_time']}s)\n";
            } elseif ($status['status'] === 'expected_failure') {
                echo "✓ {$page} - Expected failure: {$status['http_code']}\n";
            } else {
                echo "✗ {$page} - FAILED: {$status['error']}\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Check single page
     */
    private function checkPage($url, $expected_status = 200) {
        $start_time = microtime(true);
        
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $this->config['timeout'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'WebsiteHealthChecker/1.0',
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $response_time = round(microtime(true) - $start_time, 2);
            
            if ($error) {
                return [
                    'status' => 'error',
                    'error' => $error,
                    'response_time' => $response_time
                ];
            }
            
            // Check if status matches expected
            if ($http_code == $expected_status) {
                return [
                    'status' => 'success',
                    'http_code' => $http_code,
                    'response_time' => $response_time,
                    'content_length' => strlen($response)
                ];
            } else {
                // For 404 test pages, expected failure is okay
                if ($expected_status == 404 && $http_code == 404) {
                    return [
                        'status' => 'expected_failure',
                        'http_code' => $http_code,
                        'response_time' => $response_time
                    ];
                }
                
                return [
                    'status' => 'error',
                    'error' => "Expected $expected_status, got $http_code",
                    'http_code' => $http_code,
                    'response_time' => $response_time
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'response_time' => round(microtime(true) - $start_time, 2)
            ];
        }
    }
    
    /**
     * Check all functions
     */
    private function checkFunctions() {
        echo "Checking Functions:\n";
        echo "-------------------\n";
        
        $this->results['functions'] = [];
        
        foreach ($this->functions_to_check as $name => $method) {
            if (method_exists($this, $method)) {
                $result = $this->$method();
                $this->results['functions'][$name] = $result;
                
                if ($result['status'] === 'success') {
                    echo "✓ {$name}\n";
                } else {
                    echo "✗ {$name} - {$result['message']}\n";
                }
            } else {
                echo "✗ {$name} - Check method not found\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Database connection check
     */
    private function checkDatabaseConnection() {
        try {
            // Modify these with your database credentials
            $host = 'localhost';
            $dbname = 'your_database';
            $username = 'root';
            $password = '';
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return [
                'status' => 'success',
                'message' => 'Database connection successful'
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sample function checks - Implement these based on your actual functions
     */
    private function testUserAuthentication() {
        // Implement your authentication test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testEmailFunction() {
        // Implement email test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testFileUpload() {
        // Implement file upload test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testFormValidation() {
        // Implement form validation test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testSessionManagement() {
        // Implement session test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testCsrfProtection() {
        // Implement CSRF test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testEncryption() {
        // Implement encryption test
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function checkCronJobs() {
        // Check if cron jobs are running
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    private function testBackupSystem() {
        // Test backup system
        return ['status' => 'pending', 'message' => 'Not implemented'];
    }
    
    /**
     * Check APIs
     */
    private function checkAPIs() {
        echo "Checking APIs:\n";
        echo "--------------\n";
        
        $this->results['apis'] = [];
        
        foreach ($this->api_endpoints as $name => $endpoint) {
            $result = $this->testAPI($endpoint['url'], $endpoint['method']);
            $this->results['apis'][$name] = $result;
            
            if ($result['status'] === 'success') {
                echo "✓ {$name} - {$result['http_code']}\n";
            } else {
                echo "✗ {$name} - {$result['message']}\n";
            }
        }
        echo "\n";
    }
    
    private function testAPI($endpoint, $method = 'GET') {
        $url = $this->config['base_url'] . $endpoint;
        
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_TIMEOUT => $this->config['timeout'],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code >= 200 && $http_code < 300) {
                return [
                    'status' => 'success',
                    'http_code' => $http_code,
                    'message' => 'API call successful'
                ];
            } else {
                return [
                    'status' => 'error',
                    'http_code' => $http_code,
                    'message' => "API returned HTTP $http_code"
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check database
     */
    private function checkDatabase() {
        echo "Checking Database:\n";
        echo "------------------\n";
        
        $this->results['database'] = [];
        
        // First check connection
        $conn = $this->checkDatabaseConnection();
        if ($conn['status'] !== 'success') {
            echo "✗ Database connection failed\n";
            return;
        }
        
        // Run database queries
        foreach ($this->database_checks as $name => $query) {
            // This is a template - implement actual database checking
            $this->results['database'][$name] = [
                'status' => 'pending',
                'message' => 'Database check not implemented'
            ];
            echo "? {$name} - Not implemented\n";
        }
        echo "\n";
    }
    
    /**
     * Check server environment
     */
    private function checkServerEnvironment() {
        echo "Checking Server Environment:\n";
        echo "-----------------------------\n";
        
        $this->results['environment'] = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions()
        ];
        
        echo "✓ PHP Version: " . PHP_VERSION . "\n";
        echo "✓ Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "✓ Max Execution Time: " . ini_get('max_execution_time') . "s\n";
        echo "\n";
    }
    
    /**
     * Check security
     */
    private function checkSecurity() {
        echo "Security Checks:\n";
        echo "----------------\n";
        
        $this->results['security'] = [];
        
        // Check for common security issues
        $checks = [
            'Display Errors' => ini_get('display_errors') == '0',
            'HTTPS Available' => !empty($_SERVER['HTTPS']),
            'Session Security' => ini_get('session.cookie_httponly') == '1',
        ];
        
        foreach ($checks as $name => $status) {
            $this->results['security'][$name] = $status ? 'secure' : 'insecure';
            
            if ($status) {
                echo "✓ {$name}\n";
            } else {
                echo "⚠ {$name} - Needs attention\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Check performance
     */
    private function checkPerformance() {
        echo "Performance Checks:\n";
        echo "-------------------\n";
        
        $this->results['performance'] = [];
        
        // Sample performance check - implement actual checks
        $start_time = microtime(true);
        
        // Simulate some checks
        usleep(100000); // 0.1 second delay
        
        $this->results['performance']['check_time'] = round(microtime(true) - $start_time, 2);
        $this->results['performance']['memory_usage'] = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
        
        echo "✓ Check completed in " . $this->results['performance']['check_time'] . "s\n";
        echo "✓ Memory usage: " . $this->results['performance']['memory_usage'] . "\n\n";
    }
    
    /**
     * Calculate progress
     */
    private function calculateProgress() {
        $total = 0;
        $completed = 0;
        $failed = 0;
        
        // Count pages
        foreach ($this->results['pages'] ?? [] as $page) {
            $total++;
            if ($page['status'] === 'success' || $page['status'] === 'expected_failure') {
                $completed++;
            } elseif ($page['status'] === 'error') {
                $failed++;
            }
        }
        
        // Count functions
        foreach ($this->results['functions'] ?? [] as $function) {
            $total++;
            if ($function['status'] === 'success') {
                $completed++;
            } elseif ($function['status'] === 'error') {
                $failed++;
            }
        }
        
        // Count APIs
        foreach ($this->results['apis'] ?? [] as $api) {
            $total++;
            if ($api['status'] === 'success') {
                $completed++;
            } elseif ($api['status'] === 'error') {
                $failed++;
            }
        }
        
        $this->progress = [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $total - $completed - $failed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
        
        $this->results['progress'] = $this->progress;
    }
    
    /**
     * Display summary
     */
    private function displaySummary() {
        echo "========================================\n";
        echo "CHECK SUMMARY\n";
        echo "========================================\n";
        echo "Total Checks: {$this->progress['total']}\n";
        echo "Completed: {$this->progress['completed']}\n";
        echo "Pending: {$this->progress['pending']}\n";
        echo "Failed: {$this->progress['failed']}\n";
        echo "Progress: {$this->progress['percentage']}%\n";
        echo "========================================\n";
        echo "Reports saved to:\n";
        echo "- {$this->config['output_file']} (JSON)\n";
        echo "- {$this->config['html_report']} (HTML)\n";
        echo "========================================\n";
    }
    
    /**
     * Generate JSON report
     */
    private function generateJsonReport() {
        file_put_contents(
            $this->config['output_file'],
            json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
    
    /**
     * Generate HTML report
     */
    private function generateHtmlReport() {
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Website Progress Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px; }
                .progress-bar { height: 20px; background: #e0e0e0; border-radius: 10px; margin: 10px 0; overflow: hidden; }
                .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
                .status-success { color: #4CAF50; }
                .status-error { color: #f44336; }
                .status-pending { color: #ff9800; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; }
                tr:hover { background-color: #f5f5f5; }
                .timestamp { color: #666; font-style: italic; }
                .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
                .badge-success { background: #d4edda; color: #155724; }
                .badge-error { background: #f8d7da; color: #721c24; }
                .badge-pending { background: #fff3cd; color: #856404; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Website Progress Report</h1>
                <div class="timestamp">Generated on: ' . $this->results['timestamp'] . '</div>
                
                <div class="summary">
                    <h2>Overall Progress</h2>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ' . $this->progress['percentage'] . '%"></div>
                    </div>
                    <p><strong>' . $this->progress['percentage'] . '% Complete</strong></p>
                    <p>Total Checks: ' . $this->progress['total'] . ' | 
                       Completed: <span class="status-success">' . $this->progress['completed'] . '</span> | 
                       Pending: <span class="status-pending">' . $this->progress['pending'] . '</span> | 
                       Failed: <span class="status-error">' . $this->progress['failed'] . '</span></p>
                </div>';
        
        // Pages section
        if (!empty($this->results['pages'])) {
            $html .= '<h2>Pages Status</h2><table>
                <tr><th>Page</th><th>Status</th><th>HTTP Code</th><th>Response Time</th></tr>';
            foreach ($this->results['pages'] as $page => $status) {
                $badge_class = $status['status'] === 'success' ? 'badge-success' : 
                              ($status['status'] === 'expected_failure' ? 'badge-success' : 'badge-error');
                $badge_text = $status['status'] === 'expected_failure' ? 'Expected Failure' : ucfirst($status['status']);
                $http_code = $status['http_code'] ?? 'N/A';
                $response_time = isset($status['response_time']) ? $status['response_time'] . 's' : 'N/A';
                $html .= "<tr>
                    <td>{$page}</td>
                    <td><span class='badge {$badge_class}'>{$badge_text}</span></td>
                    <td>{$http_code}</td>
                    <td>{$response_time}</td>
                </tr>";
            }
            $html .= '</table>';
        }
        
        // Functions section
        if (!empty($this->results['functions'])) {
            $html .= '<h2>Functions Status</h2><table>
                <tr><th>Function</th><th>Status</th><th>Message</th></tr>';
            foreach ($this->results['functions'] as $name => $status) {
                $badge_class = $status['status'] === 'success' ? 'badge-success' : 
                              ($status['status'] === 'pending' ? 'badge-pending' : 'badge-error');
                $html .= "<tr>
                    <td>{$name}</td>
                    <td><span class='badge {$badge_class}'>" . ucfirst($status['status']) . "</span></td>
                    <td>{$status['message']}</td>
                </tr>";
            }
            $html .= '</table>';
        }
        
        // Environment info
        if (!empty($this->results['environment'])) {
            $html .= '<h2>Server Environment</h2><table>';
            foreach ($this->results['environment'] as $key => $value) {
                if ($key === 'extensions') continue;
                $html .= "<tr><td>" . ucfirst(str_replace('_', ' ', $key)) . "</td><td>{$value}</td></tr>";
            }
            $html .= '</table>';
        }
        
        $html .= '</div></body></html>';
        
        file_put_contents($this->config['html_report'], $html);
    }
    
    /**
     * Add custom check
     */
    public function addPageCheck($url, $expected_status = 200) {
        $this->pages_to_check[$url] = $expected_status;
    }
    
    public function addFunctionCheck($name, $callback) {
        $this->functions_to_check[$name] = $callback;
    }
    
    /**
     * Get results
     */
    public function getResults() {
        return $this->results;
    }
    
    /**
     * Get progress
     */
    public function getProgress() {
        return $this->progress;
    }
}

// Usage example:
if (php_sapi_name() === 'cli') {
    // Command line usage
    $checker = new WebsiteHealthChecker([
        'base_url' => 'http://localhost/your-project', // Change this
    ]);
    
    $checker->runFullCheck();
} else {
    // Web usage
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Website Health Check</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            .btn { background: #4CAF50; color: white; padding: 10px 20px; 
                   border: none; border-radius: 4px; cursor: pointer; 
                   text-decoration: none; display: inline-block; }
            .btn:hover { background: #45a049; }
            .status { margin: 20px 0; padding: 15px; border-radius: 4px; }
            .success { background: #d4edda; color: #155724; }
            .error { background: #f8d7da; color: #721c24; }
        </style>
    </head>
    <body>
        <h1>Website Health Check</h1>
        <p>Click the button below to run a comprehensive check of all website functions and pages.</p>
        
        <a href="?run_check=1" class="btn">Run Full Check</a>
        
        <?php
        if (isset($_GET['run_check'])) {
            echo '<div class="status">';
            $checker = new WebsiteHealthChecker([
                'base_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
            ]);
            
            $results = $checker->runFullCheck();
            $progress = $checker->getProgress();
            
            echo '<div class="' . ($progress['percentage'] > 80 ? 'success' : 'error') . ' status">';
            echo "<h3>Check Complete!</h3>";
            echo "<p>Progress: {$progress['percentage']}%</p>";
            echo "<p>Completed: {$progress['completed']}/{$progress['total']}</p>";
            echo "<p>View detailed reports:</p>";
            echo "<ul>";
            echo "<li><a href='progress_report.json' target='_blank'>JSON Report</a></li>";
            echo "<li><a href='progress_report.html' target='_blank'>HTML Report</a></li>";
            echo "</ul>";
            echo '</div>';
            echo '</div>';
        }
        ?>
    </body>
    </html>
    <?php
}
?>