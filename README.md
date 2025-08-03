# LNHS Documents Request Portal

A modern, comprehensive web-based document request system for LNHS (Lyceum of the North High School) that allows students and alumni to request official documents online without the need to visit the school physically.

## Features

### 🔐 **Authentication System**
- Student/Alumni registration and login
- Admin authentication
- Password-protected user accounts
- Session management

### 📄 **Document Request System**
- Online document request forms
- File upload functionality for requirements
- Multiple document types supported:
  - Certificate of Enrollment
  - Good Moral Certificate
  - Transcript of Records
  - Diploma Copy
  - Certificate of Graduation

### 📊 **Request Tracking**
- Real-time status tracking
- Status progression: Pending → Processing → Approved/Denied → Ready for Pickup → Completed
- Email notifications for status updates
- Request history and details

### 👨‍💼 **Admin Dashboard**
- Comprehensive request management
- Status update functionality
- User management
- Statistics and analytics
- Data export capabilities

### 🔔 **Notification System**
- Email notifications for status updates
- In-app notification center
- Real-time notification badges

### 💰 **Fee Management**
- Automatic fee calculation
- Payment status tracking
- Revenue reporting

### 🎨 **Modern UI/UX**
- Responsive Bootstrap design
- Mobile-friendly interface
- Professional styling
- Intuitive navigation

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **XAMPP**: Recommended for local development

## Installation Instructions

### 1. Download and Setup XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your computer
3. Start Apache and MySQL services

### 2. Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database or import the provided SQL file:
   - Option A: Import `lnhs_portal.sql` file
   - Option B: Create database manually named `lnhs_portal`
3. The database will include:
   - All necessary tables
   - Default admin account
   - Sample document types
   - System settings

### 3. File Installation

1. Copy all project files to `C:\xampp\htdocs\lnhs_portal\` (Windows) or `/opt/lampp/htdocs/lnhs_portal/` (Linux)
2. Ensure the `uploads/` directory has write permissions
3. Verify database connection settings in `config/database.php`

### 4. Default Login Credentials

**Admin Account:**
- Email: `admin@lnhs.edu.ph`
- Password: `password`

**Database Settings:**
- Host: `localhost`
- Database: `lnhs_portal`
- Username: `root`
- Password: (leave empty for XAMPP default)

## Usage Guide

### For Students/Alumni

1. **Registration**
   - Visit the portal homepage
   - Click "Register Now"
   - Fill in personal information
   - Select user type (Student or Alumni)
   - Submit registration

2. **Document Request**
   - Login to your account
   - Navigate to "New Request"
   - Select document type
   - Fill in purpose and details
   - Upload required documents
   - Submit request

3. **Track Requests**
   - View "My Requests" for all submissions
   - Check status updates in real-time
   - Receive email notifications
   - Access detailed request information

### For Administrators

1. **Login**
   - Use admin credentials to access admin dashboard
   - Monitor all system activities

2. **Request Management**
   - View all incoming requests
   - Update request statuses
   - Add admin notes
   - Export data for reporting

3. **User Management**
   - Monitor user registrations
   - View user statistics
   - Manage user accounts

## File Structure

```
lnhs_portal/
├── config/
│   ├── database.php          # Database configuration
│   └── session.php           # Session management
├── includes/
│   ├── header.php            # Common header template
│   ├── footer.php            # Common footer template
│   └── functions.php         # Utility functions
├── ajax/
│   ├── get-notifications.php # AJAX notification loader
│   └── mark-notification-read.php # AJAX notification updater
├── uploads/                  # File upload directory
├── index.php                 # Homepage
├── login.php                 # Login page
├── register.php              # Registration page
├── dashboard.php             # Student/Alumni dashboard
├── admin-dashboard.php       # Admin dashboard
├── request-document.php      # Document request form
├── my-requests.php           # User requests listing
├── view-request.php          # Detailed request view
├── profile.php               # User profile management
├── notifications.php         # Notifications center
├── logout.php                # Logout handler
├── lnhs_portal.sql          # Database structure
└── README.md                 # This file
```

## Database Schema

### Tables Overview

- **users**: User accounts (students, alumni, admin)
- **document_types**: Available document types and fees
- **document_requests**: Document request records
- **notifications**: System notifications
- **system_settings**: Configuration settings

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session security and timeout management
- File upload validation and restrictions

## Customization

### Adding New Document Types

1. Access admin dashboard
2. Navigate to document types management
3. Add new document type with:
   - Document name
   - Description
   - Requirements
   - Processing days
   - Fee amount

### Email Configuration

Update email settings in `includes/functions.php`:
- SMTP configuration
- Email templates
- Notification preferences

### Styling Customization

- Modify CSS variables in `includes/header.php`
- Update Bootstrap classes
- Add custom styling rules

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify MySQL service is running
   - Check database credentials in `config/database.php`
   - Ensure database `lnhs_portal` exists

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify PHP upload settings in `php.ini`
   - Ensure file size limits are appropriate

3. **Email Notifications Not Working**
   - Configure SMTP settings
   - Check server mail configuration
   - Verify email addresses are valid

### Debug Mode

Enable error reporting for development by adding to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## System Features Detail

### Request Status Flow

1. **Pending**: Initial status when request is submitted
2. **Processing**: Admin has started reviewing the request
3. **Approved**: Request approved, document preparation begins
4. **Denied**: Request rejected (with admin notes)
5. **Ready for Pickup**: Document is ready for collection
6. **Completed**: Request fully processed and closed

### Notification Types

- **Registration Welcome**: Sent upon successful registration
- **Request Submitted**: Confirmation of request submission
- **Status Updates**: Notifications for each status change
- **System Alerts**: Important system announcements

### File Upload Support

Supported file types:
- Images: JPG, JPEG, PNG
- Documents: PDF, DOC, DOCX
- Maximum file size: 5MB per file
- Multiple file upload support

## Support and Maintenance

### Regular Maintenance Tasks

1. **Database Backup**: Regular backup of the `lnhs_portal` database
2. **File Cleanup**: Periodic cleanup of uploaded files
3. **Log Monitoring**: Check error logs for issues
4. **Security Updates**: Keep PHP and MySQL updated

### Contact Information

For technical support or questions about the LNHS Documents Request Portal:
- Email: admin@lnhs.edu.ph
- Office Hours: 8:00 AM - 5:00 PM, Monday - Friday

## License

This project is developed for LNHS (Lyceum of the North High School) internal use.

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Developed by**: LNHS IT Department