<?php
// Security Analysis Test
echo "=== Security Analysis ===" . PHP_EOL . PHP_EOL;

$security_issues = array();
$warnings = array();
$good_practices = array();

// 1. Check for SQL Injection vulnerabilities
echo "[1] SQL Injection Protection Check" . PHP_EOL;
$files_with_direct_queries = array();

// Sample check - in real scenario would scan all PHP files
$test_files = array(
    'api/generate_qr.php',
    'config/database.php',
    'includes/db_connect.php'
);

foreach ($test_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for prepared statements (good)
        if (strpos($content, 'prepare(') !== false) {
            $good_practices[] = "$file uses prepared statements";
        }
        
        // Check for direct query concatenation (bad)
        if (preg_match('/query\s*\(\s*["\'].*\$/', $content)) {
            $security_issues[] = "$file may have SQL injection vulnerability";
        }
    }
}

if (empty($security_issues)) {
    echo "  [OK] No obvious SQL injection vulnerabilities found" . PHP_EOL;
} else {
    echo "  [WARNING] Potential SQL injection issues:" . PHP_EOL;
    foreach ($security_issues as $issue) {
        echo "    - $issue" . PHP_EOL;
    }
}
echo PHP_EOL;

// 2. Check for XSS vulnerabilities
echo "[2] XSS Protection Check" . PHP_EOL;
$xss_issues = array();

// Check if htmlspecialchars or similar is used
$sample_view_files = array(
    'login.php',
    'register.php'
);

foreach ($sample_view_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for output escaping
        if (strpos($content, 'htmlspecialchars') !== false || 
            strpos($content, 'htmlentities') !== false) {
            $good_practices[] = "$file uses output escaping";
        }
        
        // Check for direct echo of user input (potential XSS)
        if (preg_match('/echo\s+\$_(GET|POST|REQUEST)\[/', $content)) {
            $xss_issues[] = "$file may have XSS vulnerability";
        }
    }
}

if (empty($xss_issues)) {
    echo "  [OK] No obvious XSS vulnerabilities found in sampled files" . PHP_EOL;
} else {
    echo "  [WARNING] Potential XSS issues:" . PHP_EOL;
    foreach ($xss_issues as $issue) {
        echo "    - $issue" . PHP_EOL;
    }
}
echo PHP_EOL;

// 3. Check for authentication
echo "[3] Authentication Check" . PHP_EOL;
if (file_exists('includes/auth.php')) {
    echo "  [OK] Authentication file exists" . PHP_EOL;
    $good_practices[] = "Authentication system in place";
} else {
    $warnings[] = "No dedicated authentication file found";
    echo "  [WARNING] No dedicated authentication file found" . PHP_EOL;
}
echo PHP_EOL;

// 4. Check for session security
echo "[4] Session Security Check" . PHP_EOL;
if (file_exists('config/database.php')) {
    $content = file_get_contents('config/database.php');
    if (strpos($content, 'session_start()') !== false) {
        echo "  [OK] Session management detected" . PHP_EOL;
        $good_practices[] = "Session management implemented";
    }
}
echo PHP_EOL;

// 5. Check for file upload security
echo "[5] File Upload Security Check" . PHP_EOL;
if (file_exists('api/items/create.php')) {
    $content = file_get_contents('api/items/create.php');
    
    $has_file_validation = false;
    $has_extension_check = false;
    
    if (strpos($content, 'UPLOAD_ERR_OK') !== false) {
        $has_file_validation = true;
    }
    
    if (preg_match('/\$allowed\s*=\s*\[/', $content) || 
        preg_match('/in_array.*\$ext/', $content)) {
        $has_extension_check = true;
    }
    
    if ($has_file_validation && $has_extension_check) {
        echo "  [OK] File upload validation detected" . PHP_EOL;
        $good_practices[] = "File upload validation in place";
    } else {
        echo "  [WARNING] File upload may need better validation" . PHP_EOL;
        $warnings[] = "File upload validation could be improved";
    }
}
echo PHP_EOL;

// 6. Check for password hashing
echo "[6] Password Security Check" . PHP_EOL;
if (file_exists('register.php')) {
    $content = file_get_contents('register.php');
    
    if (strpos($content, 'password_hash') !== false) {
        echo "  [OK] Password hashing detected" . PHP_EOL;
        $good_practices[] = "Passwords are hashed";
    } else {
        echo "  [CRITICAL] No password hashing found!" . PHP_EOL;
        $security_issues[] = "Passwords may not be properly hashed";
    }
}
echo PHP_EOL;

// 7. Check for CSRF protection
echo "[7] CSRF Protection Check" . PHP_EOL;
$csrf_found = false;
$sample_forms = array('login.php', 'register.php');

foreach ($sample_forms as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'csrf') !== false || strpos($content, 'token') !== false) {
            $csrf_found = true;
            break;
        }
    }
}

if ($csrf_found) {
    echo "  [OK] CSRF protection detected" . PHP_EOL;
    $good_practices[] = "CSRF protection implemented";
} else {
    echo "  [WARNING] No obvious CSRF protection found" . PHP_EOL;
    $warnings[] = "Consider implementing CSRF protection";
}
echo PHP_EOL;

// Summary
echo "=== Security Summary ===" . PHP_EOL . PHP_EOL;

echo "Critical Issues: " . count($security_issues) . PHP_EOL;
if (!empty($security_issues)) {
    foreach ($security_issues as $issue) {
        echo "  [!] $issue" . PHP_EOL;
    }
    echo PHP_EOL;
}

echo "Warnings: " . count($warnings) . PHP_EOL;
if (!empty($warnings)) {
    foreach ($warnings as $warning) {
        echo "  [*] $warning" . PHP_EOL;
    }
    echo PHP_EOL;
}

echo "Good Practices Found: " . count($good_practices) . PHP_EOL;
if (!empty($good_practices)) {
    foreach ($good_practices as $practice) {
        echo "  [+] $practice" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "Note: This is a basic security scan. A full security audit is recommended." . PHP_EOL;
?>
