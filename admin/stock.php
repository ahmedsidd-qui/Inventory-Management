<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole('admin');

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
    <title>Available Stock - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Available Stock</h1>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Stock List</h2>
                <p class="text-muted">All items purchased by owner are listed below</p>
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
                                <td><?php echo $item['quantity']; ?></td>
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
                <p class="text-muted">No stock available. Owner needs to make purchases first.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
