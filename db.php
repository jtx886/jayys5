<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $conn;
    private $connected = false;

    private function __construct() {
        try {
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            );
            
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
            } elseif (defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
                $options[Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
            }
            
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $options
            );
            
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->conn->exec("USE `" . DB_NAME . "`");
            $this->conn->exec("SET NAMES utf8mb4");
            
            $this->connected = true;
        } catch(PDOException $e) {
            $this->connected = false;
            $this->conn = null;
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function getConnection() {
        return $this->conn;
    }

    private function ensureConnection() {
        if (!$this->connected) {
            if (php_sapi_name() !== 'cli' && !defined('INSTALLING')) {
                if (strpos($_SERVER['PHP_SELF'], 'install.php') === false) {
                    header("Location: install.php");
                    exit;
                }
            }
            return false;
        }
        return true;
    }

    public function query($sql, $params = array()) {
        if (!$this->ensureConnection()) {
            return false;
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchAll($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return array();
        return $stmt->fetchAll();
    }

    public function fetch($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return null;
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        if (!$this->ensureConnection()) return false;
        $fields = implode(',', array_keys($data));
        $placeholders = ':' . implode(',:', array_keys($data));
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        if ($this->query($sql, $data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($table, $data, $where, $whereParams = array()) {
        if (!$this->ensureConnection()) return false;
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = :$key,";
        }
        $set = rtrim($set, ',');
        $sql = "UPDATE $table SET $set WHERE $where";
        $params = array_merge($data, $whereParams);
        return $this->query($sql, $params);
    }

    public function delete($table, $where, $params = array()) {
        if (!$this->ensureConnection()) return false;
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params);
    }
    
    public function exec($sql) {
        if (!$this->ensureConnection()) return false;
        try {
            return $this->conn->exec($sql);
        } catch(PDOException $e) {
            error_log("Database exec error: " . $e->getMessage());
            return false;
        }
    }
}

function db() {
    return Database::getInstance();
}
