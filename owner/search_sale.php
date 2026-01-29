<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole('owner');

// Get search parameters
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query
$sales = null;
$total_revenue = 0;
$total_profit = 0;

if ($search_date) {
    // Search by specific date
    $stmt = $conn->prepare("SELECT * FROM sales WHERE sale_date = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $search_date);
    $stmt->execute();
    $sales = $stmt->get_result();
    
    // Get totals
    $stmt = $conn->prepare("SELECT SUM(total_amount) as revenue, SUM(profit) as profit FROM sales WHERE sale_date = ?");
    $stmt->bind_param("s", $search_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_revenue = $row['revenue'] ?? 0;
    $total_profit = $row['profit'] ?? 0;
    
} elseif ($start_date && $end_date) {
    // Search by date range
    $stmt = $conn->prepare("SELECT * FROM sales WHERE sale_date BETWEEN ? AND ? ORDER BY sale_date DESC, created_at DESC");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $sales = $stmt->get_result();
    
    // Get totals
    $stmt = $conn->prepare("SELECT SUM(total_amount) as revenue, SUM(profit) as profit FROM sales WHERE sale_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_revenue = $row['revenue'] ?? 0;
    $total_profit = $row['profit'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Sales - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Search Sales</h1>
        
        <!-- Search Forms -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Search by Date</h2>
            </div>
            
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search_date">Specific Date</label>
                    <input type="date" id="search_date" name="search_date" value="<?php echo $search_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            
            <hr style="margin: 30px 0; border: 1px solid var(--border);">
            
            <div class="card-header">
                <h2 class="card-title">Search by Date Range</h2>
            </div>
            
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <?php if ($sales !== null): ?>
            
            <?php if ($sales->num_rows > 0): ?>
                <!-- Summary Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Sales</div>
                        <div class="stat-value"><?php echo $sales->num_rows; ?></div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value"><?php echo formatCurrency($total_revenue); ?></div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-label">Total Profit</div>
                        <div class="stat-value"><?php echo formatCurrency($total_profit); ?></div>
                    </div>
                </div>
                
                <!-- Sales Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Sales Results</h2>
                        <?php if ($search_date): ?>
                            <p class="text-muted">Date: <?php echo formatDate($search_date); ?></p>
                        <?php elseif ($start_date && $end_date): ?>
                            <p class="text-muted">Period: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Selling Price</th>
                                    <th>Total Amount</th>
                                    <th>Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                // Reset pointer to iterate again
                                $sales->data_seek(0);
                                while($sale = $sales->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo formatDate($sale['sale_date']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['item_name']); ?></td>
                                    <td><?php echo $sale['quantity']; ?></td>
                                    <td><?php echo formatCurrency($sale['selling_price']); ?></td>
                                    <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                                    <td class="text-success"><strong><?php echo formatCurrency($sale['profit']); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot style="background: var(--light); font-weight: 600;">
                                <tr>
                                    <td colspan="5" style="text-align: right; padding: 15px;">TOTAL:</td>
                                    <td><?php echo formatCurrency($total_revenue); ?></td>
                                    <td><?php echo formatCurrency($total_profit); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <p class="text-muted">No sales found for the selected date(s).</p>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
