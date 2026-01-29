    <?php
/**
 * Database Configuration
 * Establishes connection to the inventory database
 */

// Base URL Configuration
// Change this to '/' if hosting on root domain, or '/folder_name/' for subfolder
define('BASE_URL', '/MattressInventory/');

// Database Credentials
// Update these with your hosting provider's details
$host = 'localhost';
$username = 'root';      // e.g., u1234567_user
$password = '';          // e.g., password123
$database = 'invent_form'; // e.g., u1234567_inventory

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

return $conn;
?>
