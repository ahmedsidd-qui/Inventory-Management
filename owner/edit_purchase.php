<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require owner role
requireRole('owner');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'edit';
$success = '';
$error = '';

// Fetch purchase
$stmt = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();

if (!$purchase) {
    die("Purchase not found.");
}

// Handle Delete
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        // 1. Revert stock
        revertStockFromPurchase($purchase['item_name'], $purchase['quantity'], $purchase['purchase_price']);
        
        // 2. Delete purchase
        $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Purchase deleted successfully and stock adjusted.";
            header("Location: " . url('owner/purchase.php'));
            exit;
        } else {
            $error = "Error deleting purchase: " . $conn->error;
        }
    }
}

// Handle Edit
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $purchase_price = floatval($_POST['purchase_price']);
    $purchase_date = sanitizeInput($_POST['purchase_date']);
    $total_amount = $quantity * $purchase_price;
    
    // 1. Revert OLD stock effect
    revertStockFromPurchase($purchase['item_name'], $purchase['quantity'], $purchase['purchase_price']);
    
    // 2. Update Purchase Record
    $stmt = $conn->prepare("UPDATE purchases SET quantity = ?, purchase_price = ?, total_amount = ?, purchase_date = ? WHERE id = ?");
    $stmt->bind_param("iddsi", $quantity, $purchase_price, $total_amount, $purchase_date, $id);
    
    if ($stmt->execute()) {
        // 3. Apply NEW stock effect
        updateStockAfterPurchase($purchase['item_name'], $quantity, $purchase_price);
        
        $_SESSION['success'] = "Purchase updated successfully and stock adjusted.";
        header("Location: " . url('owner/purchase.php'));
        exit;
    } else {
        $error = "Error updating purchase: " . $conn->error;
        // Try to re-apply old stock effect if update failed (safety net)
        updateStockAfterPurchase($purchase['item_name'], $purchase['quantity'], $purchase['purchase_price']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> Purchase - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;"><?php echo ucfirst($action); ?> Purchase</h1>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?php echo $purchase['item_name']; ?></h2>
                <p class="text-muted">Original Date: <?php echo formatDate($purchase['purchase_date']); ?></p>
            </div>
            
            <?php if ($error): echo showAlert($error, 'danger'); endif; ?>
            
            <?php if ($action === 'delete'): ?>
                <div class="alert alert-danger">
                    <strong>Warning:</strong> Deleting this purchase will remove <strong><?php echo $purchase['quantity']; ?></strong> units from your stock.
                    Are you sure you want to proceed?
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger">Yes, Delete Purchase</button>
                    <a href="<?php echo url('owner/purchase.php'); ?>" class="btn btn-secondary">Cancel</a>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($purchase['item_name']); ?>" disabled style="background: #eee;">
                        <p class="text-muted" style="font-size: 12px; margin-top: 5px;">Item name cannot be changed to ensure data integrity.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="<?php echo $purchase['quantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="purchase_price">Purchase Price per Unit (PKR) *</label>
                        <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" value="<?php echo $purchase['purchase_price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="purchase_date">Purchase Date *</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="<?php echo $purchase['purchase_date']; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Purchase</button>
                    <a href="<?php echo url('owner/purchase.php'); ?>" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
