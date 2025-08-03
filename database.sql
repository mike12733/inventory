-- Inventory Tracking and Equipment Management System Database
-- Compatible with phpMyAdmin in XAMPP

-- Create database
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- Users table for admin login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    location VARCHAR(200),
    status ENUM('available', 'in_use', 'maintenance', 'damaged', 'lost') DEFAULT 'available',
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    supplier VARCHAR(200),
    warranty_expiry DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample equipment data
INSERT INTO equipment (equipment_code, barcode, name, description, category, location, status, purchase_date, purchase_price, supplier) VALUES
('EQ001', '123456789012', 'Laptop Dell Inspiron', 'Dell Inspiron 15-inch laptop with Intel i5 processor', 'Computers', 'Office Room 1', 'available', '2023-01-15', 45000.00, 'Dell Philippines'),
('EQ002', '123456789013', 'Printer HP LaserJet', 'HP LaserJet Pro M404n printer', 'Printers', 'Printing Room', 'available', '2023-02-20', 15000.00, 'HP Store'),
('EQ003', '123456789014', 'Projector Epson', 'Epson projector for presentations', 'AV Equipment', 'Conference Room', 'in_use', '2023-03-10', 25000.00, 'Epson Philippines'),
('EQ004', '123456789015', 'Scanner Canon', 'Canon document scanner', 'Scanners', 'Document Room', 'available', '2023-04-05', 8000.00, 'Canon Store'),
('EQ005', '123456789016', 'Air Conditioner', 'Split type air conditioner 1.5HP', 'HVAC', 'Office Room 2', 'available', '2023-05-12', 35000.00, 'Carrier Philippines');