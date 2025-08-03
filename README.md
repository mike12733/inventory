# Inventory Tracking and Equipment Management System

A comprehensive web-based inventory management system built with PHP and MySQL, designed to track equipment and manage inventory using barcode technology.

## 🌟 Features

### Core Features
- **User Authentication**: Secure login system for admin users
- **Equipment Management**: Add, edit, delete, and view equipment with full CRUD operations
- **Barcode Generation**: Automatic barcode generation and scanning for equipment tracking
- **Category Management**: Organize equipment into customizable categories
- **Transaction Tracking**: Record equipment check-in/out, maintenance, transfers, and returns
- **Admin Activity Logs**: Complete audit trail of all system activities
- **Reports & Analytics**: Comprehensive reporting with charts and statistics

### Advanced Features
- **Modern UI/UX**: Beautiful, responsive design with gradient backgrounds and animations
- **Search & Filtering**: Advanced search and filter capabilities across all modules
- **Real-time Status Updates**: Equipment status automatically updates with transactions
- **Warranty Tracking**: Monitor equipment warranties and expiration dates
- **Value Analysis**: Track equipment values and generate financial reports
- **Print Support**: Print-friendly reports and barcode labels
- **Mobile Responsive**: Works seamlessly on desktop, tablet, and mobile devices

## 🚀 Installation

### Prerequisites
- **XAMPP** (Apache, MySQL, PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **phpMyAdmin** (included with XAMPP)

### Step 1: Setup XAMPP
1. Download and install [XAMPP](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP Control Panel
3. Ensure ports 80 (Apache) and 3306 (MySQL) are available

### Step 2: Database Setup
1. Open phpMyAdmin by navigating to `http://localhost/phpmyadmin`
2. Create a new database or import the provided SQL file:
   - **Option A**: Import the `database.sql` file directly
   - **Option B**: Copy and paste the SQL content from `database.sql`
3. The database will be created with the name `inventory_management`

### Step 3: File Installation
1. Copy all project files to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\inventory-system\
   ```
2. Ensure all files maintain their directory structure:
   ```
   inventory-system/
   ├── assets/
   │   └── css/
   │       └── style.css
   ├── config/
   │   └── database.php
   ├── index.php
   ├── login.php
   ├── dashboard.php
   ├── equipment.php
   ├── categories.php
   ├── transactions.php
   ├── reports.php
   ├── barcode_generator.php
   ├── admin_logs.php
   ├── get_equipment.php
   ├── get_log_details.php
   └── database.sql
   ```

### Step 4: Configuration
1. Open `config/database.php`
2. Verify database connection settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'inventory_management');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
3. Adjust settings if your XAMPP configuration is different

### Step 5: Access the System
1. Open your web browser
2. Navigate to: `http://localhost/inventory-system/`
3. You'll be redirected to the login page

## 🔐 Default Login Credentials

- **Email**: `admin@inventory.com`
- **Password**: `password`

> **Important**: Change the default password after first login for security.

## 📊 System Overview

### Dashboard
- Equipment statistics and overview
- Recent transactions
- System alerts and notifications
- Quick action buttons

### Equipment Management
- **Add Equipment**: Register new equipment with details, categories, and auto-generated barcodes
- **Edit Equipment**: Update equipment information, status, and location
- **Delete Equipment**: Remove equipment from the system (with transaction history preserved)
- **View Details**: Comprehensive equipment information display
- **Search & Filter**: Find equipment by name, barcode, category, or status

### Category Management
- Create and manage equipment categories
- View equipment count per category
- Prevent deletion of categories with assigned equipment

### Transaction System
- **Check Out**: Record when equipment is borrowed or assigned
- **Check In**: Log equipment returns
- **Maintenance**: Track equipment sent for repair or servicing
- **Transfer**: Record location changes
- **Return**: Complete equipment return process

### Barcode System
- Automatic barcode generation for new equipment
- SVG-based barcode creation for high-quality printing
- Print and download barcode labels
- Barcode scanner compatibility

### Reports & Analytics
- Equipment status distribution charts
- Category statistics and analysis
- Transaction history reports
- Equipment value analysis
- Warranty expiration tracking
- Most active equipment reports

### Admin Logs
- Complete audit trail of all system activities
- User action tracking
- Change history with before/after values
- Advanced filtering and search
- Detailed log views with timestamps

## 🛠️ Usage Guide

### Adding New Equipment
1. Navigate to **Equipment** page
2. Click **Add Equipment** button
3. Fill in equipment details:
   - Name (required)
   - Category (required)
   - Description
   - Brand and Model
   - Serial Number
   - Location
   - Purchase information
   - Warranty details
4. Click **Save Equipment**
5. A unique barcode will be automatically generated

### Managing Transactions
1. Go to **Transactions** page
2. Click **New Transaction**
3. Select equipment and transaction type
4. Fill in relevant details (user, locations, notes)
5. Submit to record the transaction
6. Equipment status will update automatically

### Generating Barcodes
1. Visit **Barcode Generator** page
2. Enter equipment barcode or select from list
3. Generate and preview barcode
4. Print or download for labeling

### Viewing Reports
1. Access **Reports** page
2. View various charts and statistics
3. Print reports using the Print button
4. Analyze equipment usage and trends

## 🔧 Customization

### Adding New Equipment Categories
1. Go to **Categories** page
2. Click **Add Category**
3. Enter category name and description
4. Save to make available for equipment assignment

### Modifying User Interface
- Edit `assets/css/style.css` for styling changes
- Colors, fonts, and layouts can be customized
- Responsive design maintains mobile compatibility

### Database Modifications
- Additional fields can be added to equipment table
- Custom transaction types can be implemented
- Report queries can be modified for specific needs

## 🚨 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify XAMPP MySQL service is running
- Check database credentials in `config/database.php`
- Ensure database exists in phpMyAdmin

**Page Not Found Errors**
- Verify Apache service is running in XAMPP
- Check file paths and directory structure
- Ensure files are in correct htdocs subdirectory

**Login Issues**
- Use default credentials: `admin@inventory.com` / `password`
- Check database was imported correctly
- Clear browser cache and cookies

**Barcode Generation Problems**
- Ensure PHP GD extension is enabled
- Check file permissions for writing
- Verify SVG support in browser

### System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **Memory**: 512MB RAM minimum
- **Storage**: 100MB available space

## 📝 Database Schema

### Tables Overview
- **admins**: System administrators and users
- **categories**: Equipment categories
- **equipment**: Main equipment records
- **inventory_transactions**: Transaction history
- **admin_logs**: System activity audit trail

### Key Relationships
- Equipment belongs to Categories (1:many)
- Transactions reference Equipment (many:1)
- Logs reference Admins (many:1)

## 🔒 Security Features

- **Password Hashing**: Secure bcrypt password encryption
- **Session Management**: Secure session handling
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Activity Logging**: Complete audit trail
- **Access Control**: Login requirement for all features

## 🎯 Future Enhancements

- **Multi-user Support**: Role-based access control
- **Email Notifications**: Alerts for warranty expiration
- **API Integration**: REST API for mobile apps
- **Advanced Reporting**: Custom report builder
- **Backup System**: Automated database backups
- **Barcode Scanning**: Camera-based scanning interface

## 📞 Support

For technical support or questions:
- Review this README for common solutions
- Check XAMPP and phpMyAdmin documentation
- Verify all prerequisites are met
- Ensure proper file permissions

## 📄 License

This project is created for educational and practical inventory management purposes. Feel free to modify and adapt for your specific needs.

---

**System Title**: Inventory Tracking and Equipment Management System  
**Technology Stack**: PHP, MySQL, HTML5, CSS3, JavaScript  
**Features**: Complete CRUD operations, Barcode generation, Transaction tracking, Admin logs, Reports  
**Compatible**: XAMPP, phpMyAdmin, Modern web browsers