<?php
session_start();
$conn = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require owner role
requireRole('owner');

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mattress_type = sanitizeInput($_POST['mattress_type']);
    $mattress_size = sanitizeInput($_POST['mattress_size']);
    
    // Combine type and size to create item name
    $item_name = trim($mattress_type) . ' - ' . trim($mattress_size);
    
    $quantity = intval($_POST['quantity']);
    $purchase_price = floatval($_POST['purchase_price']);
    $purchase_date = sanitizeInput($_POST['purchase_date']);
    $total_amount = $quantity * $purchase_price;
    
    // Insert purchase
    $stmt = $conn->prepare("INSERT INTO purchases (item_name, quantity, purchase_price, total_amount, purchase_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siddsi", $item_name, $quantity, $purchase_price, $total_amount, $purchase_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update stock
        updateStockAfterPurchase($item_name, $quantity, $purchase_price);
        $_SESSION['success'] = "Purchase added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Error adding purchase: " . $conn->error;
    }
}

// Check for session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get all purchases grouped by date
$purchases_query = "SELECT purchase_date, 
                           COUNT(*) as item_count,
                           SUM(quantity) as total_quantity,
                           SUM(total_amount) as daily_total
                    FROM purchases 
                    GROUP BY purchase_date 
                    ORDER BY purchase_date DESC";
$purchases_by_date = $conn->query($purchases_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Management - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/owner_nav.php'; ?>

    <div class="container">
        <h1 style="color: black; margin-bottom: 30px;">Purchase Management</h1>
        
        <!-- Add Purchase Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Add New Purchase</h2>
            </div>
            
            <?php if ($success): echo showAlert($success, 'success'); endif; ?>
            <?php if ($error): echo showAlert($error, 'danger'); endif; ?>
            
            <form method="POST" action="" id="purchaseForm">
                    <?php
                    // Fetch existing categories and sizes for dropdowns
                    $categories = [];
                    $sizes = [];
                    
                    $stock_query = $conn->query("SELECT DISTINCT item_name FROM stock");
                    while($row = $stock_query->fetch_assoc()) {
                        $parts = explode(' - ', $row['item_name']);
                        if (!empty($parts[0]) && !in_array($parts[0], $categories)) {
                            $categories[] = $parts[0];
                        }
                        if (isset($parts[1]) && !empty($parts[1]) && !in_array($parts[1], $sizes)) {
                            $sizes[] = $parts[1];
                        }
                    }
                    sort($categories);
                    sort($sizes);
                    
                    // Add standard sizes if list is empty (fallback)
                    if (empty($sizes)) {
                        $sizes = [
                            "Single (36 x 72 inches)", "Double (54 x 72 inches)", 
                            "Queen (60 x 78 inches)", "King (72 x 78 inches)", 
                            "Super King (78 x 80 inches)"
                        ];
                    }
                    ?>

                    <div class="form-group">
                        <label for="category_select">Mattress Type *</label>
                        <select id="category_select" name="category_select" required onchange="toggleOtherInput('category')">
                            <option value="">-- Select Type --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other (Add New)...</option>
                        </select>
                        <input type="text" id="mattress_type" name="mattress_type" placeholder="Enter New Mattress Type" style="display: none; margin-top: 10px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="size_select">Mattress Size *</label>
                        <select id="size_select" name="size_select" required onchange="toggleOtherInput('size')">
                            <option value="">-- Select Size --</option>
                            <?php foreach($sizes as $size): ?>
                                <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other (Add New)...</option>
                        </select>
                        <input type="text" id="mattress_size" name="mattress_size" placeholder="Enter New Size (e.g. King (72 x 78 inches))" style="display: none; margin-top: 10px;">
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            window.toggleOtherInput = function(type) {
                                const select = document.getElementById(type + '_select');
                                const input = document.getElementById(type === 'category' ? 'mattress_type' : 'mattress_size');
                                
                                if (select.value === 'Other') {
                                    input.style.display = 'block';
                                    input.required = true;
                                    input.value = '';
                                    input.focus();
                                } else {
                                    input.style.display = 'none';
                                    input.required = false;
                                    // If not "Other", set the hidden input value to the selected option
                                    input.value = select.value;
                                }
                            };
                            
                            // Pre-fill hidden inputs with initial select values if not empty
                            const catSelect = document.getElementById('category_select');
                            const sizeSelect = document.getElementById('size_select');
                            
                            if(catSelect.value && catSelect.value !== 'Other') {
                                document.getElementById('mattress_type').value = catSelect.value;
                            }
                            if(sizeSelect.value && sizeSelect.value !== 'Other') {
                                document.getElementById('mattress_size').value = sizeSelect.value;
                            }
                        });
                    </script>
                
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="purchase_price">Purchase Price per Unit (PKR) *</label>
                    <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="purchase_date">Purchase Date *</label>
                    <input type="date" id="purchase_date" name="purchase_date" value="<?php echo getCurrentDate(); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Purchase</button>
            </form>
        </div>

        <!-- Purchase History by Date -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📋 Purchase History (Grouped by Date)</h2>
                <p class="text-muted">Daily purchase bills showing all items purchased each day</p>
            </div>
            
            <?php if ($purchases_by_date->num_rows > 0): ?>
                <?php while($date_group = $purchases_by_date->fetch_assoc()): ?>
                    <div style="border: 2px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px; background: var(--light);">
                        <!-- Date Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                            <div>
                                <h3 style="margin: 0; color: var(--primary);">
                                    📅 <?php echo formatDate($date_group['purchase_date']); ?>
                                </h3>
                                <p style="margin: 5px 0 0 0; color: var(--secondary); font-size: 14px;">
                                    <?php echo $date_group['item_count']; ?> item(s) | Total Quantity: <?php echo $date_group['total_quantity']; ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--success);">
                                    <?php echo formatCurrency($date_group['daily_total']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Items for this date -->
                        <?php
                        $date = $date_group['purchase_date'];
                        $items_query = $conn->prepare("SELECT * FROM purchases WHERE purchase_date = ? ORDER BY created_at ASC");
                        $items_query->bind_param("s", $date);
                        $items_query->execute();
                        $items = $items_query->get_result();
                        ?>
                        
                        <div class="table-responsive">
                            <table style="margin: 0;">
                                <thead style="background: white;">
                                    <tr>
                                        <th>#</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Price per Unit</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $item_counter = 1;
                                    while($item = $items->fetch_assoc()): 
                                    ?>
                                    <tr style="background: white;">
                                        <td><?php echo $item_counter++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatCurrency($item['purchase_price']); ?></td>
                                        <td><strong><?php echo formatCurrency($item['total_amount']); ?></strong></td>
                                        <td>
                                            <a href="edit_purchase.php?id=<?php echo $item['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">Edit</a>
                                            <a href="edit_purchase.php?id=<?php echo $item['id']; ?>&action=delete" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px; background: #dc3545; color: white; border: none;">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot style="background: var(--primary); color: white;">
                                    <tr>
                                        <td colspan="4" style="text-align: right; padding: 12px; font-weight: 600;">DAILY TOTAL:</td>
                                        <td style="padding: 12px;"><strong style="font-size: 16px;"><?php echo formatCurrency($date_group['daily_total']); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No purchases yet. Add your first purchase above!</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
