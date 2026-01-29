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
    $item_name = sanitizeInput($_POST['item_name']);
    $quantity = intval($_POST['quantity']);
    $selling_price = floatval($_POST['selling_price']);
    $sale_date = sanitizeInput($_POST['sale_date']);
    
    // Check if item exists in stock
    $stock_qty = getStockQuantity($item_name);
    
    if ($stock_qty < $quantity) {
        $error = "Insufficient stock! Available: $stock_qty units";
    } else {
        // Get average purchase price
        $purchase_price = getAvgPurchasePrice($item_name);
        $total_amount = $quantity * $selling_price;
        $profit = ($selling_price - $purchase_price) * $quantity;
        
        // Insert sale
        $stmt = $conn->prepare("INSERT INTO sales (item_name, quantity, purchase_price, selling_price, total_amount, profit, sale_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siddddsi", $item_name, $quantity, $purchase_price, $selling_price, $total_amount, $profit, $sale_date, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Update stock
            updateStockAfterSale($item_name, $quantity);
            $_SESSION['success'] = "Sale recorded successfully! Profit: " . formatCurrency($profit);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Error recording sale: " . $conn->error;
        }
    }
}

// Check for session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get available stock items (query after form processing)
$stock_items = $conn->query("SELECT item_name, quantity FROM stock WHERE quantity > 0 ORDER BY item_name ASC");

// Get recent sales
$recent_sales = $conn->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale - Mattress Inventory</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container">
        <h1 style="color: black ; margin-bottom: 30px;">Record Sale</h1>
        
        <!-- Record Sale Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">New Sale Transaction</h2>
            </div>
            
            <?php if ($success): echo showAlert($success, 'success'); endif; ?>
            <?php if ($error): echo showAlert($error, 'danger'); endif; ?>
            
            <form method="POST" action="">
                    <?php
                    // Group items by category
                    $inventory = [];
                    while($item = $stock_items->fetch_assoc()) {
                        $parts = explode(' - ', $item['item_name']);
                        $category = $parts[0];
                        $size = isset($parts[1]) ? $parts[1] : 'Standard';
                        
                        if (!isset($inventory[$category])) {
                            $inventory[$category] = [];
                        }
                        
                        $inventory[$category][] = [
                            'full_name' => $item['item_name'],
                            'size' => $size,
                            'quantity' => $item['quantity'],
                            'price' => $item['avg_purchase_price'] // keeping this for reference if needed
                        ];
                    }
                    
                    // Convert to JSON for JS
                    $inventory_json = json_encode($inventory);
                    ?>

                    <div class="form-group">
                        <label for="category_select">Category *</label>
                        <select id="category_select" required onchange="updateSizes()">
                            <option value="">-- Select Category --</option>
                            <?php foreach(array_keys($inventory) as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="size_select">Size *</label>
                        <select id="size_select" required onchange="updateItemDetails()">
                            <option value="">-- Select Category First --</option>
                        </select>
                    </div>

                    <!-- Hidden input to store the actual item name for backend processing -->
                    <input type="hidden" id="item_name" name="item_name" required>
                    
                    <!-- Display available quantity -->
                    <div id="stock_display" style="margin-top: -10px; margin-bottom: 15px; font-size: 0.9em; color: #666; display: none;">
                        Available Stock: <span id="available_qty" style="font-weight: bold;">0</span>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const inventory = <?php echo $inventory_json; ?>;
                            const categorySelect = document.getElementById('category_select');
                            const sizeSelect = document.getElementById('size_select');

                            window.updateSizes = function() {
                                const selectedCategory = categorySelect.value;
                                
                                // Reset size select
                                sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';
                                document.getElementById('stock_display').style.display = 'none';
                                document.getElementById('item_name').value = '';

                                if (selectedCategory && inventory[selectedCategory]) {
                                    inventory[selectedCategory].forEach(item => {
                                        const option = document.createElement('option');
                                        option.value = item.full_name;
                                        option.textContent = item.size;
                                        option.dataset.qty = item.quantity;
                                        sizeSelect.appendChild(option);
                                    });
                                }
                            };

                            window.updateItemDetails = function() {
                                const itemNameInput = document.getElementById('item_name');
                                const stockDisplay = document.getElementById('stock_display');
                                const availableQtySpan = document.getElementById('available_qty');
                                
                                const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
                                
                                if (selectedOption.value) {
                                    itemNameInput.value = selectedOption.value;
                                    availableQtySpan.textContent = selectedOption.dataset.qty;
                                    
                                    // Color code based on stock
                                    const qty = parseInt(selectedOption.dataset.qty);
                                    if (qty === 0) {
                                        availableQtySpan.style.color = 'var(--danger)';
                                    } else if (qty < 5) {
                                        availableQtySpan.style.color = 'var(--warning)';
                                    } else {
                                        availableQtySpan.style.color = 'var(--success)';
                                    }
                                    
                                    stockDisplay.style.display = 'block';
                                } else {
                                    itemNameInput.value = '';
                                    stockDisplay.style.display = 'none';
                                }
                            };
                        });
                    </script>
                
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="selling_price">Selling Price per Unit (PKR) *</label>
                    <input type="number" id="selling_price" name="selling_price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="sale_date">Sale Date *</label>
                    <input type="date" id="sale_date" name="sale_date" value="<?php echo getCurrentDate(); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-success">Record Sale</button>
                <a href="<?php echo url('admin/stock.php'); ?>" class="btn btn-secondary">View Stock</a>
            </form>
        </div>

        <!-- Recent Sales -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Sales</h2>
            </div>
            
            <?php if ($recent_sales->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th>Qty</th>
                                <th>Selling Price</th>
                                <th>Total Amount</th>
                                <th>Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sale = $recent_sales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                                <td><?php echo htmlspecialchars($sale['item_name']); ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td><?php echo formatCurrency($sale['selling_price']); ?></td>
                                <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                                <td class="text-success"><strong><?php echo formatCurrency($sale['profit']); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No sales recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo url('assets/js/main.js'); ?>"></script>
</body>
</html>
