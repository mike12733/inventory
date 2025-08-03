-- LNHS Documents Request Portal Database
-- Import this file into phpMyAdmin to create the database

CREATE DATABASE IF NOT EXISTS lnhs_portal;
USE lnhs_portal;

-- Users table for students, alumni, and admin
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('student', 'alumni', 'admin') NOT NULL,
    student_id VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    graduation_year YEAR,
    course VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document types table
CREATE TABLE document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    processing_days INT DEFAULT 3,
    fee DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document requests table
CREATE TABLE document_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    preferred_release_date DATE,
    status ENUM('pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    uploaded_files TEXT, -- JSON array of file paths
    total_fee DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('unpaid', 'paid', 'waived') DEFAULT 'unpaid',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    completed_date TIMESTAMP NULL,
    processed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    sent_email BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE SET NULL
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (user_type, email, password, first_name, last_name) 
VALUES ('admin', 'admin@lnhs.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User');

-- Insert default document types
INSERT INTO document_types (document_name, description, requirements, processing_days, fee) VALUES
('Certificate of Enrollment', 'Official certificate proving current enrollment status', 'Valid ID, Student ID', 3, 50.00),
('Good Moral Certificate', 'Certificate of good moral character', 'Valid ID, Student ID, Clearance Form', 5, 75.00),
('Transcript of Records', 'Official academic transcript', 'Valid ID, Student ID, Request Form', 7, 100.00),
('Diploma Copy', 'Certified true copy of diploma', 'Valid ID, Original Diploma for verification', 5, 150.00),
('Certificate of Graduation', 'Official graduation certificate', 'Valid ID, Student ID', 3, 75.00);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'LNHS Documents Request Portal', 'System title'),
('admin_email', 'admin@lnhs.edu.ph', 'Admin email for notifications'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'Allowed file extensions'),
('processing_hours', '8:00 AM - 5:00 PM, Monday - Friday', 'Office processing hours');