<?php
/**
 * Common Utility Functions
 * Helper functions for formatting and calculations
 */

/**
 * Get absolute URL
 */
function url($path = '') {
    // Remove leading slash if present to avoid double slashes
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}

/**
 * Format currency in PKR
 */
function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Get current date in SQL format
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Display alert message
 */
function showAlert($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-danger';
    return "<div class='alert $class'>$message</div>";
}

/**
 * Calculate profit percentage
 */
function calculateProfitPercentage($cost, $selling) {
    if ($cost == 0) return 0;
    return (($selling - $cost) / $cost) * 100;
}

/**
 * Update stock after purchase
 */
function updateStockAfterPurchase($item_name, $quantity, $purchase_price) {
    global $conn;
    
    // Check if item exists in stock
    $stmt = $conn->prepare("SELECT id, quantity, avg_purchase_price FROM stock WHERE item_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing stock
        $stock = $result->fetch_assoc();
        $old_quantity = $stock['quantity'];
        $old_avg_price = $stock['avg_purchase_price'];
        
        $new_quantity = $old_quantity + $quantity;
        $new_avg_price = (($old_quantity * $old_avg_price) + ($quantity * $purchase_price)) / $new_quantity;
        
        $stmt = $conn->prepare("UPDATE stock SET quantity = ?, avg_purchase_price = ? WHERE item_name = ?");
        $stmt->bind_param("ids", $new_quantity, $new_avg_price, $item_name);
        $stmt->execute();
    } else {
        // Insert new stock
        $stmt = $conn->prepare("INSERT INTO stock (item_name, quantity, avg_purchase_price) VALUES (?, ?, ?)");
        $stmt->bind_param("sid", $item_name, $quantity, $purchase_price);
        $stmt->execute();
    }
}

/**
 * Update stock after sale
 */
function updateStockAfterSale($item_name, $quantity) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE stock SET quantity = quantity - ? WHERE item_name = ?");
    $stmt->bind_param("is", $quantity, $item_name);
    return $stmt->execute();
}

/**
 * Get stock quantity for an item
 */
function getStockQuantity($item_name) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT quantity FROM stock WHERE item_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['quantity'];
    }
    
    return 0;
}

/**
 * Get average purchase price for an item
 */
function getAvgPurchasePrice($item_name) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT avg_purchase_price FROM stock WHERE item_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['avg_purchase_price'];
    }
    
    return 0;
}
/**
 * Revert stock after purchase deletion or edit
 */
function revertStockFromPurchase($item_name, $quantity, $purchase_price) {
    global $conn;
    
    // Check if item exists in stock
    $stmt = $conn->prepare("SELECT id, quantity, avg_purchase_price FROM stock WHERE item_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stock = $result->fetch_assoc();
        $current_quantity = $stock['quantity'];
        $current_avg_price = $stock['avg_purchase_price'];
        
        // Calculate total value before revert
        $current_total_value = $current_quantity * $current_avg_price;
        
        // Calculate value of purchase to revert
        $purchase_value = $quantity * $purchase_price;
        
        // New values
        $new_quantity = $current_quantity - $quantity;
        
        if ($new_quantity > 0) {
            $new_total_value = $current_total_value - $purchase_value;
            // Prevent negative value due to floating point errors
            if ($new_total_value < 0) $new_total_value = 0;
            
            $new_avg_price = $new_total_value / $new_quantity;
        } else {
            $new_quantity = 0;
            $new_avg_price = 0;
        }
        
        $stmt = $conn->prepare("UPDATE stock SET quantity = ?, avg_purchase_price = ? WHERE item_name = ?");
        $stmt->bind_param("ids", $new_quantity, $new_avg_price, $item_name);
        $stmt->execute();
    }
}
?>
