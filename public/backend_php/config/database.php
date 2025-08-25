<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Configuración para Railway
        if (isset($_ENV['MYSQLDATABASE'])) {
            // Producción en Railway
            $host = $_ENV['MYSQLHOST'];
            $port = $_ENV['MYSQLPORT'];
            $dbname = $_ENV['MYSQLDATABASE'];
            $username = $_ENV['MYSQLUSER'];
            $password = $_ENV['MYSQLPASSWORD'];
        } else {
            // Desarrollo local
            $host = 'localhost';
            $port = 3306;
            $dbname = 'carnet_socios';
            $username = 'root';
            $password = '';
        }
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>