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
    $category = 'General'; // Default category since we removed the input
    $amount = floatval($_POST['amount']);
    $income_date = sanitizeInput($_POST['income_date']);
    
    // Insert income
    $stmt = $conn->prepare("INSERT INTO other_income (description, category, amount, income_date, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $description, $category, $amount, $income_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Income recorded successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Error recording income: " . $conn->error;
    }
}

// Check for session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get all income records
$incomes = $conn->query("SELECT * FROM other_income ORDER BY income_date DESC, created_at DESC");

// Get total income
$total_income = $conn->query("SELECT SUM(amount) as total FROM other_income")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Other Income - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Other Income Management</h1>
        
        <!-- Total Income -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-label">Total Other Income</div>
                <div class="stat-value"><?php echo formatCurrency($total_income); ?></div>
            </div>
        </div>
        
        <!-- Add Income Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Add New Income</h2>
            </div>
            
            <?php if ($success): echo showAlert($success, 'success'); endif; ?>
            <?php if ($error): echo showAlert($error, 'danger'); endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="description">Description *</label>
                    <input type="text" id="description" name="description" required placeholder="e.g., Delivery Fee, Scrap Sale">
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (PKR) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="income_date">Date *</label>
                    <input type="date" id="income_date" name="income_date" value="<?php echo getCurrentDate(); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-success">Record Income</button>
            </form>
        </div>

        <!-- Income History -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Income History</h2>
            </div>
            
            <?php if ($incomes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while($income = $incomes->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo formatDate($income['income_date']); ?></td>
                                <td><?php echo htmlspecialchars($income['description']); ?></td>
                                <td><strong><?php echo formatCurrency($income['amount']); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No other income recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
