# 🛏️ Mattress Inventory Management System

A professional inventory management system built with PHP and MySQL for mattress businesses. Features separate owner and admin panels with comprehensive purchase tracking, sales management, expense recording, and P&L reporting.

## 🌟 Features

### Owner Panel
- **Dashboard**: View total purchases, investment, and stock value
- **Purchase Management**: Add mattress purchases with automatic stock updates
- **P&L Report**: Comprehensive profit & loss statements with date filtering
  - Total revenue and cost of goods sold
  - Expense breakdown by category
  - Gross and net profit calculations
  - Profit margin percentages

### Admin Panel
- **Dashboard**: Today's sales statistics and low stock alerts
- **Stock Management**: View all available items from owner's purchases
- **Sales Recording**: Record sales with automatic stock validation and profit calculation
- **Expense Management**: Track business expenses with categories
- **Sales Search**: Search sales by specific date or date range

## 💻 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Security**: Password hashing, prepared statements, session management
- **Currency**: Pakistani Rupee (PKR)

## 📁 Project Structure

```
/inv2
├── config/              # Configuration files
│   └── database.php     # Database connection
├── includes/            # Reusable PHP files
│   ├── auth.php         # Authentication functions
│   └── functions.php    # Utility functions
├── assets/              # Static assets
│   ├── css/
│   │   └── style.css    # Professional styling
│   └── js/
│       └── main.js      # JavaScript utilities
├── owner/               # Owner panel pages
│   ├── dashboard.php
│   ├── purchase.php
│   └── pnl_report.php
├── admin/               # Admin panel pages
│   ├── dashboard.php
│   ├── stock.php
│   ├── sale.php
│   ├── expense.php
│   └── search_sale.php
├── install.php          # Database installer
├── login.php            # Unified login page
├── logout.php           # Logout handler
└── index.php            # Landing page
```

## 🚀 Installation

### Prerequisites
- XAMPP/WAMP/LAMP (Apache + PHP + MySQL)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Steps

1. **Copy Files**
   ```bash
   # Copy the inv2 folder to your htdocs directory
   cp -r inv2 /Applications/XAMPP/xamppfiles/htdocs/
   ```

2. **Install Database**
   - Navigate to: `http://localhost/inv2/install.php`
   - The installer will create the `inventory` database and all tables
   - Default users will be created automatically

3. **Login**
   - Navigate to: `http://localhost/inv2/login.php`
   - Use default credentials (see below)

### Default Credentials

| Role  | Username | Password  |
|-------|----------|-----------|
| Owner | owner    | owner123  |
| Admin | admin    | admin123  |

**⚠️ Important**: Change these passwords after first login for security!

## 📊 Database Schema

### Tables

- **users**: User accounts (owner and admin)
- **purchases**: Purchase records made by owner
- **stock**: Current inventory with average purchase prices
- **sales**: Sales transactions with profit tracking
- **expenses**: Business expenses with categories

### Database Flow

1. **Purchase**: Owner adds purchase → Stock updated with new quantity and average price
2. **Sale**: Admin records sale → Stock reduced, profit calculated automatically
3. **P&L**: Owner views reports → System calculates from sales, purchases, and expenses

## 🎨 Features in Detail

### Stock Management
- Automatic stock updates when purchases are added
- Average purchase price calculation (FIFO weighted average)
- Real-time stock validation before sales
- Low stock alerts (quantity < 5)

### Profit Calculation
```
Gross Profit = Selling Price - Purchase Price (per unit)
Total Profit = (Selling Price - Avg Purchase Price) × Quantity
Net Profit = Gross Profit - Total Expenses
```

### P&L Report Components
- **Revenue**: Total sales amount
- **COGS**: Cost of goods sold (purchase price × quantity sold)
- **Gross Profit**: Revenue - COGS
- **Operating Expenses**: Categorized business expenses
- **Net Profit**: Gross Profit - Operating Expenses
- **Profit Margins**: Gross and net profit percentages

## 🔒 Security Features

- **Password Security**: Passwords hashed using PHP's `password_hash()`
- **SQL Injection Protection**: All queries use prepared statements
- **Session Management**: Secure PHP sessions with role-based access
- **Input Sanitization**: All user inputs sanitized before processing
- **Role-Based Access Control**: Owner and admin have separate permissions

## 📱 Responsive Design

The system features a modern, responsive design that works on:
- Desktop computers
- Tablets
- Mobile phones

## 🛠️ Configuration

### Database Connection
Edit `config/database.php` to change database credentials:

```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventory';
```

### Currency Format
PKR formatting is applied throughout. To change currency, edit the `formatCurrency()` function in `includes/functions.php`.

## 📖 Usage Guide

### For Owners

1. **Add Purchases**
   - Go to Purchase page
   - Enter item name, quantity, and purchase price
   - Stock is automatically updated

2. **View P&L Report**
   - Select date range
   - View comprehensive profit/loss statement
   - Analyze profit margins

### For Admins

1. **Record Sales**
   - Select item from available stock
   - Enter quantity and selling price
   - System validates stock availability
   - Profit calculated automatically

2. **Manage Expenses**
   - Add expenses with description
   - Categorize for better reporting
   - Track total expenses

3. **Search Sales**
   - Search by specific date
   - Or search by date range
   - View summary statistics

## 🎯 Best Practices

1. **Regular Backups**: Backup the `inventory` database regularly
2. **Change Default Passwords**: Update default credentials immediately
3. **Monitor Stock Levels**: Check low stock alerts daily
4. **Review P&L Monthly**: Analyze profitability monthly
5. **Categorize Expenses**: Use proper categories for accurate reporting

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check if MySQL is running
- Verify database credentials in `config/database.php`
- Ensure `inventory` database exists

**Login Issues**
- Clear browser cache and cookies
- Verify credentials are correct
- Check if sessions are enabled in PHP

**Stock Not Updating**
- Check database permissions
- Verify triggers/functions in `includes/functions.php`

## 📝 License

This project is created for educational and commercial use.

## 👨‍💻 Support

For issues or questions, please contact the system administrator.

## 🔄 Version History

- **v1.0.0** (2025-12-03)
  - Initial release
  - Owner and Admin panels
  - Purchase, Sales, Expense management
  - P&L Reporting
  - Professional UI/UX

---

**Built with ❤️ for Professional Mattress Businesses**
