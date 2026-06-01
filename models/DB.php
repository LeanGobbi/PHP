<?php

class DB {
    private static $connection;

    public static function getConnection() {
        if (!self::$connection) {
            $host = 'localhost';
            $dbname = 'seminariophp';
            $user = 'root';
            $pass = '';

            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die(json_encode(['error' => $e->getMessage()]));
            }
        }

        return self::$connection;
    }
}





<?php

class DB {
    private static $connection;

    public static function getConnection() {
        
        if (!self::$connection) {
            $host = $_ENV['DB_HOST'];
            $db = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASS'];
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            try {

                self::$connection = new PDO($dsn, $user, $pass, $options);

            } catch (PDOException $e) {

                die(json_encode([
                    "error" => "Database connection failed: " . $e->getMessage()
                ]));
            }
        }

        return self::$connection;
    }
}
