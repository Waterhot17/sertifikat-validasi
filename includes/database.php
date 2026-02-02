<?php
// ============================================
// FILE: DATABASE.PHP - KONEKSI DATABASE
// ============================================

class Database {
    private static $instance = null;
    private $conn;
    
    // Ubah dari private ke public
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function query($sql) {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("Database Error: " . $this->conn->error . " - SQL: " . $sql);
        }
        return $result;
    }
    
    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
}

// Fungsi helper untuk query cepat
function db_query($sql) {
    $db = Database::getInstance();
    return $db->query($sql);
}

function db_escape($string) {
    $db = Database::getInstance();
    return $db->escape($string);
}

function db_insert_id() {
    $db = Database::getInstance();
    return $db->getLastInsertId();
}
?>