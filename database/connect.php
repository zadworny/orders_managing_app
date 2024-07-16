<?php
session_start();
error_reporting(0);
//error_reporting(E_ALL);
date_default_timezone_set('Europe/Warsaw');

class Database {
    /*private string $host = "localhost";
    private string $db_name = "jrqerflhfm_app";
    private string $username = "jrqerflhfm_app";
    private string $password = "Hydrodyna13!";*/

    private string $host = "localhost";
    private string $db_name = "jrqerflhfm_app";
    private string $username = "root";
    private string $password = "root";
    
    private ?PDO $conn = null;

    public function getConnection(): PDO {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $exception) {
                //echo "Connection error: " . $exception->getMessage();
                error_log("Connection error: " . $exception->getMessage());
                throw new Exception("Database connection error.");
            }
        }

        return $this->conn;
    }
}
?>