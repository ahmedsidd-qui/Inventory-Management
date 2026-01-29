<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-content">
        <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-brand">🛏️ Mattress Inventory</a>
        <button class="nav-toggle" id="navToggle">☰</button>
        <ul class="nav-links">
            <li><a href="<?php echo url('admin/dashboard.php'); ?>" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="<?php echo url('admin/stock.php'); ?>" class="<?php echo $current_page == 'stock.php' ? 'active' : ''; ?>">Stock</a></li>
            <li><a href="<?php echo url('admin/sale.php'); ?>" class="<?php echo $current_page == 'sale.php' ? 'active' : ''; ?>">Record Sale</a></li>
            <li><a href="<?php echo url('admin/expense.php'); ?>" class="<?php echo $current_page == 'expense.php' ? 'active' : ''; ?>">Expenses</a></li>
            <li><a href="<?php echo url('admin/income.php'); ?>" class="<?php echo $current_page == 'income.php' ? 'active' : ''; ?>">Other Income</a></li>
            <li><a href="<?php echo url('admin/search_sale.php'); ?>" class="<?php echo $current_page == 'search_sale.php' ? 'active' : ''; ?>">Search Sales</a></li>
            <li class="mobile-logout"><a href="<?php echo url('logout.php'); ?>" style="color: var(--danger);">Logout</a></li>
        </ul>
        <div class="nav-user">
            <span class="user-name"><?php echo $_SESSION['full_name']; ?> (Admin)</span>
            <a href="<?php echo url('logout.php'); ?>" class="btn-logout">Logout</a>
        </div>
    </div>
</nav>
