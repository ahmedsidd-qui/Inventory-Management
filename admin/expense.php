<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole('admin');

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = sanitizeInput($_POST['description']);
    $category = sanitizeInput($_POST['category']);
    $amount = floatval($_POST['amount']);
    $expense_date = sanitizeInput($_POST['expense_date']);
    
    // Insert expense
    $stmt = $conn->prepare("INSERT INTO expenses (description, category, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $description, $category, $amount, $expense_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Expense recorded successfully!";
    } else {
        $error = "Error recording expense: " . $conn->error;
    }
}

// Get all expenses
$expenses = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC");

// Get total expenses
$total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Expense Management</h1>
        
        <!-- Total Expenses -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="stat-label">Total Expenses</div>
                <div class="stat-value"><?php echo formatCurrency($total_expenses); ?></div>
            </div>
        </div>
        
        <!-- Add Expense Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Add New Expense</h2>
            </div>
            
            <?php if ($success): echo showAlert($success, 'success'); endif; ?>
            <?php if ($error): echo showAlert($error, 'danger'); endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="description">Description *</label>
                    <input type="text" id="description" name="description" required placeholder="e.g., Delivery charges, Rent, Utilities">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Rent">Rent</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Salaries">Salaries</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (PKR) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="expense_date">Expense Date *</label>
                    <input type="date" id="expense_date" name="expense_date" value="<?php echo getCurrentDate(); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-danger">Add Expense</button>
            </form>
        </div>

        <!-- Expense History -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Expense History</h2>
            </div>
            
            <?php if ($expenses->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while($expense = $expenses->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo formatDate($expense['expense_date']); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($expense['category']); ?></span></td>
                                <td><strong><?php echo formatCurrency($expense['amount']); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No expenses recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
