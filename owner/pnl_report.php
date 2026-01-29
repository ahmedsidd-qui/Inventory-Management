<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require owner role
requireRole('owner');

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

// Calculate Revenue (Total Sales)
$stmt = $conn->prepare("SELECT SUM(total_amount) as revenue, SUM(profit) as gross_profit FROM sales WHERE sale_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$revenue = $row['revenue'] ?? 0;
$gross_profit = $row['gross_profit'] ?? 0;

// Calculate Cost of Goods Sold
$stmt = $conn->prepare("SELECT SUM(quantity * purchase_price) as cogs FROM sales WHERE sale_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$cogs = $row['cogs'] ?? 0;

// Calculate Total Expenses
$stmt = $conn->prepare("SELECT SUM(amount) as expenses FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$expenses = $row['expenses'] ?? 0;

// Calculate Other Income
$stmt = $conn->prepare("SELECT SUM(amount) as income FROM other_income WHERE income_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$other_income = $row['income'] ?? 0;

// Calculate Net Profit
$net_profit = ($gross_profit + $other_income) - $expenses;

// Get expense breakdown
$expense_breakdown = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category");
$expense_breakdown->bind_param("ss", $start_date, $end_date);
$expense_breakdown->execute();
$expense_result = $expense_breakdown->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P&L Report - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Profit & Loss Report</h1>
        
        <!-- Date Filter -->
        <div class="card">
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
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <!-- P&L Summary -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?php echo formatCurrency($revenue); ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Cost of Goods Sold</div>
                <div class="stat-value"><?php echo formatCurrency($cogs); ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Other Income</div>
                <div class="stat-value"><?php echo formatCurrency($other_income); ?></div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-label">Total Expenses</div>
                <div class="stat-value"><?php echo formatCurrency($expenses); ?></div>
            </div>
            
            <div class="stat-card <?php echo $net_profit >= 0 ? 'success' : 'danger'; ?>">
                <div class="stat-label">Net Profit</div>
                <div class="stat-value"><?php echo formatCurrency($net_profit); ?></div>
            </div>
        </div>

        <!-- Detailed P&L Statement -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detailed P&L Statement</h2>
                <p class="text-muted">Period: <?php echo formatDate($start_date) . ' to ' . formatDate($end_date); ?></p>
            </div>
            
            <table style="width: 100%;">
                <tr style="background: var(--light);">
                    <td style="padding: 15px; font-weight: 600;">Revenue</td>
                    <td style="padding: 15px; text-align: right; font-weight: 600;"><?php echo formatCurrency($revenue); ?></td>
                </tr>
                <tr>
                    <td style="padding: 15px; padding-left: 30px;">Total Sales</td>
                    <td style="padding: 15px; text-align: right;"><?php echo formatCurrency($revenue); ?></td>
                </tr>
                
                <tr style="background: var(--light);">
                    <td style="padding: 15px; font-weight: 600;">Cost of Goods Sold</td>
                    <td style="padding: 15px; text-align: right; font-weight: 600;"><?php echo formatCurrency($cogs); ?></td>
                </tr>
                
                <tr style="border-top: 2px solid var(--border);">
                    <td style="padding: 15px; font-weight: 600;">Gross Profit</td>
                    <td style="padding: 15px; text-align: right; font-weight: 600; color: var(--success);"><?php echo formatCurrency($gross_profit); ?></td>
                </tr>

                <tr style="background: var(--light);">
                    <td style="padding: 15px; font-weight: 600;">Other Income</td>
                    <td style="padding: 15px; text-align: right; font-weight: 600; color: var(--success);"><?php echo formatCurrency($other_income); ?></td>
                </tr>
                
                <tr style="background: var(--light);">
                    <td style="padding: 15px; font-weight: 600;">Operating Expenses</td>
                    <td style="padding: 15px; text-align: right; font-weight: 600;"><?php echo formatCurrency($expenses); ?></td>
                </tr>
                
                <?php while($exp = $expense_result->fetch_assoc()): ?>
                <tr>
                    <td style="padding: 15px; padding-left: 30px;"><?php echo htmlspecialchars($exp['category']); ?></td>
                    <td style="padding: 15px; text-align: right;"><?php echo formatCurrency($exp['total']); ?></td>
                </tr>
                <?php endwhile; ?>
                
                <tr style="border-top: 3px solid var(--dark); background: var(--light);">
                    <td style="padding: 15px; font-weight: 700; font-size: 18px;">Net Profit / Loss</td>
                    <td style="padding: 15px; text-align: right; font-weight: 700; font-size: 18px; color: <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?php echo formatCurrency($net_profit); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Profit Margin -->
        <?php if ($revenue > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Profit Margins</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Gross Profit Margin</div>
                    <div class="stat-value"><?php echo number_format(($gross_profit / $revenue) * 100, 2); ?>%</div>
                </div>
                <div class="stat-card <?php echo $net_profit >= 0 ? 'success' : 'danger'; ?>">
                    <div class="stat-label">Net Profit Margin</div>
                    <div class="stat-value"><?php echo number_format(($net_profit / $revenue) * 100, 2); ?>%</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
