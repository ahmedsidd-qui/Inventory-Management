<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require owner role
requireRole('owner');

// Get all stock items
$stock_items = $conn->query("SELECT * FROM stock ORDER BY item_name ASC");

// Calculate total stock value
$total_value = 0;
$total_quantity = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock View - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">📦 Current Stock Overview</h1>
        
        <!-- Stock Statistics -->
        <div class="stats-grid">
            <?php
            // Calculate stats
            $stock_result = $conn->query("SELECT 
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(quantity * avg_purchase_price) as total_value,
                SUM(CASE WHEN quantity < 5 AND quantity > 0 THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count
                FROM stock");
            $stats = $stock_result->fetch_assoc();
            ?>
            
            <div class="stat-card">
                <div class="stat-label">Total Stock Items</div>
                <div class="stat-value"><?php echo $stats['total_items']; ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Total Units in Stock</div>
                <div class="stat-value"><?php echo $stats['total_quantity']; ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Total Stock Value</div>
                <div class="stat-value"><?php echo formatCurrency($stats['total_value']); ?></div>
            </div>
            
            <div class="stat-card <?php echo $stats['low_stock_count'] > 0 ? 'danger' : ''; ?>">
                <div class="stat-label">Low Stock Items</div>
                <div class="stat-value"><?php echo $stats['low_stock_count']; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Stock Details</h2>
                <p class="text-muted">Complete inventory from all purchases</p>
            </div>
            
            <?php if ($stock_items->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Available Quantity</th>
                                <th>Avg. Purchase Price</th>
                                <th>Total Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            $stock_items->data_seek(0); // Reset pointer
                            while($item = $stock_items->fetch_assoc()): 
                                $item_value = $item['quantity'] * $item['avg_purchase_price'];
                                $total_value += $item_value;
                                $total_quantity += $item['quantity'];
                                
                                // Determine status
                                $status = '';
                                $badge_class = 'badge-success';
                                if ($item['quantity'] == 0) {
                                    $status = 'Out of Stock';
                                    $badge_class = 'badge-danger';
                                } elseif ($item['quantity'] < 5) {
                                    $status = 'Low Stock';
                                    $badge_class = 'badge-warning';
                                } else {
                                    $status = 'In Stock';
                                    $badge_class = 'badge-success';
                                }
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td>
                                    <?php if ($item['quantity'] < 5 && $item['quantity'] > 0): ?>
                                        <span style="color: var(--warning); font-weight: 600;"><?php echo $item['quantity']; ?></span>
                                    <?php elseif ($item['quantity'] == 0): ?>
                                        <span style="color: var(--danger); font-weight: 600;"><?php echo $item['quantity']; ?></span>
                                    <?php else: ?>
                                        <?php echo $item['quantity']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatCurrency($item['avg_purchase_price']); ?></td>
                                <td><?php echo formatCurrency($item_value); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot style="background: var(--light); font-weight: 600;">
                            <tr>
                                <td colspan="2" style="text-align: right; padding: 15px;">TOTAL:</td>
                                <td><?php echo $total_quantity; ?></td>
                                <td colspan="2"><?php echo formatCurrency($total_value); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No stock available. Start by adding purchases first.</p>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert -->
        <?php
        $low_stock = $conn->query("SELECT * FROM stock WHERE quantity > 0 AND quantity < 5 ORDER BY quantity ASC");
        if ($low_stock->num_rows > 0):
        ?>
        <div class="card" style="border-left: 4px solid var(--warning);">
            <div class="card-header">
                <h2 class="card-title">⚠️ Low Stock Alert</h2>
                <p class="text-muted">Items that need restocking soon</p>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Current Quantity</th>
                            <th>Avg. Purchase Price</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $low_stock->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><span class="badge badge-warning"><?php echo $item['quantity']; ?> units</span></td>
                            <td><?php echo formatCurrency($item['avg_purchase_price']); ?></td>
                            <td><span style="color: var(--warning);">📝 Consider restocking</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Out of Stock Alert -->
        <?php
        $out_of_stock = $conn->query("SELECT * FROM stock WHERE quantity = 0 ORDER BY item_name ASC");
        if ($out_of_stock->num_rows > 0):
        ?>
        <div class="card" style="border-left: 4px solid var(--danger);">
            <div class="card-header">
                <h2 class="card-title">🚫 Out of Stock Items</h2>
                <p class="text-muted">Items that are completely out of stock</p>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Last Avg. Purchase Price</th>
                            <th>Action Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $out_of_stock->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo formatCurrency($item['avg_purchase_price']); ?></td>
                            <td><span style="color: var(--danger);">⚠️ Urgent: Restock immediately</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
