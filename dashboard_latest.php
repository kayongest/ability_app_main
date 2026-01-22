<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];

// Get statistics
$conn = getConnection();

// Total items
$total_items = $conn->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];

// Available items
$available_items = $conn->query("SELECT COUNT(*) as count FROM items WHERE status = 'available'")->fetch_assoc()['count'];

// Items by category
$categories = $conn->query("SELECT category, COUNT(*) as count FROM items GROUP BY category")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ability DB Inventory</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 1.5rem 0;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .sidebar li:hover {
            background: #34495e;
        }

        .sidebar li.active {
            background: #667eea;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .quick-actions h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .user-role {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">Ability DB Inventory</div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
            <span>Department: <?php echo htmlspecialchars($department); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <ul>
                <li class="active">Dashboard</li>
                <li><a href="items.php" style="color: white; text-decoration: none;">Items Management</a></li>
                <li><a href="add_item.php" style="color: white; text-decoration: none;">Add New Item</a></li>
                <li><a href="categories.php" style="color: white; text-decoration: none;">Categories</a></li>
                <?php if ($role === 'admin'): ?>
                    <li><a href="users.php" style="color: white; text-decoration: none;">User Management</a></li>
                    <li><a href="reports.php" style="color: white; text-decoration: none;">Reports</a></li>
                <?php endif; ?>
                <li><a href="profile.php" style="color: white; text-decoration: none;">My Profile</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h1>Dashboard Overview</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Items</h3>
                    <div class="stat-number"><?php echo $total_items; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Available Items</h3>
                    <div class="stat-number"><?php echo $available_items; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Your Role</h3>
                    <div class="stat-number"><?php echo ucfirst($role); ?></div>
                </div>

                <div class="stat-card">
                    <h3>Department</h3>
                    <div class="stat-number"><?php echo $department ?: 'Not assigned'; ?></div>
                </div>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="add_item.php" class="action-btn">Add New Item</a>
                    <a href="items.php" class="action-btn">View All Items</a>
                    <a href="search.php" class="action-btn">Search Items</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="users.php" class="action-btn">Manage Users</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($categories)): ?>
                <div class="quick-actions">
                    <h3>Items by Category</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['category'] ?: 'Uncategorized'; ?></td>
                                    <td><?php echo $category['count']; ?></td>
                                    <td><?php echo round(($category['count'] / $total_items) * 100, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>