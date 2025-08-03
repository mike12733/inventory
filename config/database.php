<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lnhs_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $conn;
    
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
            die();
        }
        
        return $this->conn;
    }
}

// Create database connection
function getDBConnection() {
    $database = new Database();
    return $database->connect();
}
?>