# LNHS Documents Request Portal

A comprehensive web-based document request management system for LNHS (Local National High School) that allows students and alumni to request documents online without physically visiting the school.

## 🎯 Features

### For Students/Alumni:
- **User Registration & Login System**
  - Secure registration for students and alumni
  - User profile management
  - Password-protected accounts

- **Document Request System**
  - Online form to request various documents
  - Support for multiple document types (Certificate of Enrollment, Good Moral Certificate, etc.)
  - File upload for requirements (ID, supporting documents)
  - Purpose specification and preferred release date

- **Request Tracking System**
  - Real-time status tracking: Pending → Processing → Approved/Denied → Ready for Pickup
  - Visual progress indicators
  - Detailed request history
  - Status notifications

- **Notification System**
  - Portal notifications for status updates
  - Email notifications (configurable)
  - Real-time alerts for request updates

### For Administrators:
- **Admin Dashboard**
  - Comprehensive overview of all requests
  - Statistics and analytics
  - Quick action buttons

- **Request Management**
  - View and manage all document requests
  - Update request statuses
  - Add admin notes and comments
  - Filter requests by status, document type, date range

- **User Management**
  - View all registered users
  - Manage user accounts
  - User activity tracking

- **Reporting System**
  - Generate reports on request statistics
  - Export data to Excel/PDF
  - Admin activity logs

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Server**: Apache/Nginx with XAMPP/WAMP
- **Additional**: Font Awesome Icons

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)
- Modern web browser

## 🚀 Installation Guide

### Step 1: Download and Extract
1. Download the project files
2. Extract to your web server directory (e.g., `htdocs` for XAMPP)

### Step 2: Database Setup
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `lnhs_documents_portal`
3. Import the `database.sql` file:
   - Click on the database you just created
   - Go to "Import" tab
   - Choose the `database.sql` file
   - Click "Go" to import

### Step 3: Configuration
1. Open `config/database.php`
2. Update the database connection settings if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'lnhs_documents_portal');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### Step 4: File Permissions
1. Create an `uploads` folder in the root directory
2. Set write permissions for the uploads folder:
   ```bash
   chmod 755 uploads/
   ```

### Step 5: Access the System
1. Start your web server (Apache) and MySQL
2. Open your browser and navigate to:
   ```
   http://localhost/your-project-folder
   ```

## 👤 Default Login Credentials

### Admin Account:
- **Username**: admin
- **Password**: password

**Important**: Change the default admin password after first login!

## 📁 Project Structure

```
lnhs-documents-portal/
├── config/
│   └── database.php          # Database configuration
├── admin/
│   ├── dashboard.php         # Admin dashboard
│   ├── manage_requests.php   # Request management
│   ├── update_status.php     # Status updates
│   └── view_request.php      # Request details
├── user/
│   ├── dashboard.php         # User dashboard
│   ├── request_document.php  # Document request form
│   ├── my_requests.php       # Request tracking
│   └── view_request.php      # Request details
├── uploads/                  # File uploads directory
├── index.php                 # Login page
├── register.php              # Registration page
├── database.sql              # Database schema
└── README.md                 # This file
```

## 🔧 Configuration Options

### Email Notifications
To enable email notifications, update the email settings in `config/database.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### Document Types
Add or modify document types in the `document_types` table:
```sql
INSERT INTO document_types (name, description, processing_time, fee) VALUES 
('New Document Type', 'Description', 3, 100.00);
```

## 🎨 Customization

### Styling
- Modify CSS in the `<style>` sections of each PHP file
- Update color schemes by changing the gradient values
- Customize Bootstrap classes for different layouts

### Features
- Add new document types in the database
- Modify status workflow in the PHP files
- Add additional notification types
- Extend user roles and permissions

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- File upload validation
- Admin activity logging

## 📊 Database Schema

### Main Tables:
- `users` - User accounts (students, alumni, admin)
- `document_types` - Available document types
- `document_requests` - Document requests
- `notifications` - System notifications
- `admin_logs` - Admin activity logs

## 🚨 Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **File Upload Issues**
   - Check `uploads/` folder permissions
   - Verify PHP upload settings in `php.ini`
   - Ensure folder exists and is writable

3. **Login Problems**
   - Clear browser cache and cookies
   - Check session configuration
   - Verify default admin credentials

4. **Page Not Found**
   - Check Apache/Nginx configuration
   - Verify file paths and permissions
   - Ensure URL rewriting is enabled

## 📞 Support

For technical support or feature requests:
- Check the troubleshooting section above
- Review error logs in your web server
- Ensure all requirements are met

## 📝 License

This project is developed for educational purposes. Feel free to modify and use according to your needs.

## 🔄 Updates

### Version 1.0
- Initial release with core features
- User registration and login
- Document request system
- Admin management panel
- Notification system
- Request tracking

---

**Note**: This system is designed to be compatible with XAMPP and can be easily imported into phpMyAdmin. All features are functional and ready for use.