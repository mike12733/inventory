-- Inventory Tracking and Equipment Management System Database
-- Compatible with phpMyAdmin and XAMPP

CREATE DATABASE IF NOT EXISTS `inventory_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inventory_management`;

-- Table structure for admins
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for equipment categories
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for equipment
CREATE TABLE `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `category_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `serial_number` varchar(100),
  `model` varchar(100),
  `brand` varchar(100),
  `status` enum('Available','In Use','Maintenance','Damaged','Lost') NOT NULL DEFAULT 'Available',
  `location` varchar(200),
  `purchase_date` date,
  `purchase_price` decimal(10,2),
  `warranty_expiry` date,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for inventory transactions
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `transaction_type` enum('Check In','Check Out','Maintenance','Return','Transfer') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` text,
  `user_name` varchar(100),
  `location_from` varchar(200),
  `location_to` varchar(200),
  `admin_id` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for admin activity logs
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_affected` varchar(50),
  `record_id` int(11),
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
INSERT INTO `admins` (`email`, `password`, `full_name`) VALUES
('admin@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Insert default categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Computer Equipment', 'Laptops, desktops, monitors, keyboards, mice'),
('Network Equipment', 'Routers, switches, access points, cables'),
('Office Equipment', 'Printers, scanners, projectors, phones'),
('Furniture', 'Desks, chairs, cabinets, shelves'),
('Tools & Accessories', 'Screwdrivers, cables, adapters, chargers');

-- Sample equipment data
INSERT INTO `equipment` (`name`, `description`, `category_id`, `barcode`, `serial_number`, `model`, `brand`, `status`, `location`, `purchase_date`, `purchase_price`) VALUES
('Dell Laptop', 'Dell Inspiron 15 3000 Series', 1, 'EQ001001', 'DL123456789', 'Inspiron 15 3000', 'Dell', 'Available', 'IT Storage Room A', '2023-01-15', 25000.00),
('HP Printer', 'HP LaserJet Pro M404n', 3, 'EQ003001', 'HP987654321', 'LaserJet Pro M404n', 'HP', 'Available', 'Office Floor 2', '2023-02-20', 8500.00),
('Cisco Router', 'Cisco ISR 4321 Router', 2, 'EQ002001', 'CS555444333', 'ISR 4321', 'Cisco', 'In Use', 'Server Room', '2023-03-10', 35000.00);

-- Auto-increment settings
ALTER TABLE `admins` AUTO_INCREMENT=2;
ALTER TABLE `categories` AUTO_INCREMENT=6;
ALTER TABLE `equipment` AUTO_INCREMENT=4;
ALTER TABLE `inventory_transactions` AUTO_INCREMENT=1;
ALTER TABLE `admin_logs` AUTO_INCREMENT=1;