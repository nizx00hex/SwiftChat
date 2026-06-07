<?php

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;

    private function __construct(){
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = getenv('DB_PORT') ?: '3306';
        $this->db_name = getenv('DB_DATABASE') ?: 'switfchat';
        $this->username = getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: 'pass123';

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name,
                $this->port
            );

            if($this->conn->connect_error){
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            //set charset
            $this->conn->set_charset("utf8mb4");
            //set timezone
            $this->conn->query("SET time_zone = '+00:00'");
            //enable strict mode
            $this->conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed, Please try again later.");
        }
    }

    //singleton method
    public static function getInstance(){
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //get connection
    public function getConnection(){
        return $this->conn;
    }

    //prepare query helper
    public function prepare($query){
        return $this->conn->prepare($query);
    }

    //escape string
    public function escape($string){
        return $this->conn->real_escape_string($string);
    }

    //get last inser ID
    public function lastInsertId(){
        return $this->conn->insert_id;
    }

    //get affected rows
    public function affectedRows(){
        return $this->conn->affected_rows;
    }

    //begin transaction
    public function beginTransaction(){
        return $this->conn->begin_transaction();
    }

    //commit transaction
    public function commit(){
        return $this->conn->commit();
    }

    //rolback transaction
    public function rollback(){
        return $this->conn->rollback();
    }

    //close connection
    public function close(){
        if ($this->conn) {
            $this->conn->close();
        }
    }
}