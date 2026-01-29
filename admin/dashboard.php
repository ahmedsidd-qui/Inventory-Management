<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole('admin');

// Get today's statistics
$today = getCurrentDate();

// Today's sales stats
$stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(total_amount) as revenue, SUM(profit) as profit FROM sales WHERE sale_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$today_sales = $result->fetch_assoc();

// Low stock items (quantity < 5)
$low_stock = $conn->query("SELECT * FROM stock WHERE quantity < 5 ORDER BY quantity ASC");

// Total stock items
$total_stock = $conn->query("SELECT COUNT(*) as count FROM stock")->fetch_assoc()['count'];

// Recent sales
$recent_sales = $conn->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 5");

// Total Other Income (Today)
$today_income = $conn->query("SELECT SUM(amount) as total FROM other_income WHERE income_date = '$today'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Admin Dashboard</h1>
        
        <!-- Today's Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Today's Sales Count</div>
                <div class="stat-value"><?php echo $today_sales['count'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value"><?php echo formatCurrency($today_sales['revenue'] ?? 0); ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Today's Profit</div>
                <div class="stat-value"><?php echo formatCurrency($today_sales['profit'] ?? 0); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Total Stock Items</div>
                <div class="stat-value"><?php echo $total_stock; ?></div>
            </div>
        </div>

            <!-- Low Stock Alert -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">⚠️ Low Stock Alert</h2>
                </div>
                
                <?php if ($low_stock->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $low_stock->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><span class="badge badge-danger"><?php echo $item['quantity']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">All items have sufficient stock!</p>
                <?php endif; ?>
            </div>

            <!-- Recent Sales -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Sales</h2>
                </div>
                
                <?php if ($recent_sales->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sale = $recent_sales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['item_name']); ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No sales recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
