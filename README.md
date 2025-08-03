# Inventory Tracking and Equipment Management System

A comprehensive PHP-based inventory management system with barcode functionality, designed for tracking and managing equipment inventory.

## Features

### 🔐 Authentication & Security
- **Secure Login System**: Email and password authentication
- **Session Management**: Secure user sessions with activity logging
- **User Management**: Add, delete, and manage system users
- **Role-based Access**: Admin and user roles

### 📦 Equipment Management
- **Add Equipment**: Complete equipment registration with detailed information
- **Edit Equipment**: Update equipment details and status
- **Delete Equipment**: Remove equipment from inventory
- **Search & Filter**: Advanced search and filtering capabilities
- **Status Tracking**: Available, In Use, Maintenance, Damaged, Lost

### 🏷️ Barcode System
- **Barcode Generation**: Automatic barcode generation for equipment
- **Barcode Printing**: Print-friendly barcode layouts
- **Bulk Barcode Generation**: Generate multiple barcodes at once
- **Barcode Scanning**: Compatible with standard barcode scanners

### 📊 Reports & Analytics
- **Dashboard Statistics**: Real-time equipment statistics
- **Visual Charts**: Equipment status and category charts
- **Detailed Reports**: Equipment by category, location, and status
- **Activity Logs**: Complete audit trail of system activities

### 🔍 Advanced Features
- **Activity Monitoring**: Track all user actions and system changes
- **Search Functionality**: Search equipment by name, code, or barcode
- **Category Management**: Organize equipment by categories
- **Location Tracking**: Track equipment locations
- **Warranty Management**: Track warranty expiry dates
- **Supplier Information**: Maintain supplier details

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Download and Extract**
   ```bash
   # Extract the files to your XAMPP htdocs folder
   # Example: C:\xampp\htdocs\inventory-system\
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `inventory_system`
   - Import the `database.sql` file to create tables and sample data

3. **Configuration**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'inventory_system');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Access the System**
   - Start XAMPP (Apache and MySQL)
   - Navigate to: `http://localhost/inventory-system/`
   - Login with default credentials:
     - **Email**: admin@inventory.com
     - **Password**: admin123

## Database Structure

### Tables

1. **users** - System user accounts
   - id, username, email, password, full_name, role, created_at, updated_at

2. **equipment** - Equipment inventory
   - id, equipment_code, barcode, name, description, category, location, status, purchase_date, purchase_price, supplier, warranty_expiry, notes, created_at, updated_at

3. **activity_logs** - System activity tracking
   - id, user_id, action, table_name, record_id, details, ip_address, user_agent, created_at

## Usage Guide

### Adding Equipment
1. Navigate to "Add Equipment" from the sidebar
2. Fill in equipment details (code, name, category, etc.)
3. Barcode will be auto-generated if left empty
4. Click "Add Equipment" to save

### Managing Equipment
1. Go to "Equipment" to view all equipment
2. Use search and filters to find specific items
3. Click edit/delete buttons for individual equipment
4. Update status as needed (Available, In Use, Maintenance, etc.)

### Generating Barcodes
1. Navigate to "Barcode Generator"
2. Select equipment from dropdown
3. Click "Generate Barcode" to create individual barcodes
4. Use "Print Barcodes" for bulk printing

### Viewing Reports
1. Access "Reports" for analytics and statistics
2. View equipment distribution charts
3. Check category and location breakdowns
4. Monitor recent equipment additions

### Activity Monitoring
1. Go to "Activity Logs" to view system activities
2. Filter by user, action, or search terms
3. Track all create, update, delete, and login activities

## Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's password_hash()
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: HTML escaping for all user inputs
- **Session Security**: Secure session management with activity logging
- **Input Validation**: Comprehensive form validation and sanitization

## File Structure

```
inventory-system/
├── config/
│   └── database.php          # Database configuration
├── database.sql              # Database structure and sample data
├── login.php                 # Login page
├── dashboard.php             # Main dashboard
├── equipment.php             # Equipment listing and management
├── add_equipment.php         # Add new equipment
├── edit_equipment.php        # Edit equipment details
├── barcode_generator.php     # Barcode generation and printing
├── reports.php               # Reports and analytics
├── activity_logs.php         # Activity monitoring
├── users.php                 # User management
├── logout.php                # Logout functionality
└── README.md                 # This file
```

## Customization

### Adding New Categories
Edit the category options in `add_equipment.php` and `edit_equipment.php`:
```php
<option value="New Category">New Category</option>
```

### Modifying Barcode Format
Update the barcode generation in `add_equipment.php`:
```php
$barcode = 'PREFIX' . date('Ymd') . rand(1000, 9999);
```

### Changing Default Settings
Modify default values in the database or PHP files as needed.

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP is running
   - Verify database credentials in `config/database.php`
   - Ensure database `inventory_system` exists

2. **Login Issues**
   - Default credentials: admin@inventory.com / admin123
   - Check if users table has data
   - Verify password hashing is working

3. **Barcode Not Generating**
   - Check internet connection (for CDN resources)
   - Verify JsBarcode library is loading
   - Check browser console for JavaScript errors

4. **Permission Issues**
   - Ensure web server has read/write permissions
   - Check file ownership and permissions

## Support

For technical support or feature requests, please contact the development team.

## License

This project is developed for educational and organizational use.

---

**System Requirements:**
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Modern web browser with JavaScript enabled

**Recommended:**
- XAMPP for easy setup
- Barcode scanner for physical inventory tracking