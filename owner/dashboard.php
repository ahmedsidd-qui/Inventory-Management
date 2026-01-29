<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require owner role
requireRole('owner');

// Get statistics
$total_purchases = 0;
$total_investment = 0;
$total_stock_value = 0;

// Total purchases
$result = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM purchases");
$row = $result->fetch_assoc();
$total_purchases = $row['count'];
$total_investment = $row['total'] ?? 0;

// Total stock value
$result = $conn->query("SELECT SUM(quantity * avg_purchase_price) as total FROM stock");
$row = $result->fetch_assoc();
$total_stock_value = $row['total'] ?? 0;

// Recent purchases
$recent_purchases = $conn->query("SELECT * FROM purchases ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Owner Dashboard</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Purchases</div>
                <div class="stat-value"><?php echo $total_purchases; ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Total Investment</div>
                <div class="stat-value"><?php echo formatCurrency($total_investment); ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Current Stock Value</div>
                <div class="stat-value"><?php echo formatCurrency($total_stock_value); ?></div>
            </div>
        </div>

        <!-- Recent Purchases -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Purchases</h2>
            </div>
            
            <?php if ($recent_purchases->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Price per Unit</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent_purchases->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($row['purchase_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo formatCurrency($row['purchase_price']); ?></td>
                                <td><strong><?php echo formatCurrency($row['total_amount']); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No purchases yet. Start by adding your first purchase!</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
