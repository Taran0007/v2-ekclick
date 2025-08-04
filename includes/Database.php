<?php
class Database {
    private static $instance = null;
    private $connection = null;
    private $isCloudflare = false;
    private $apiEndpoint = '';
    private $apiToken = '';

    private function __construct() {
        $this->isCloudflare = defined('DB_TYPE') && DB_TYPE === 'cloudflare';
        
        if ($this->isCloudflare) {
            $this->apiEndpoint = API_ENDPOINT;
            $this->apiToken = API_TOKEN;
        } else {
            try {
                $this->connection = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PASS,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Connection failed: " . $e->getMessage());
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function makeApiRequest($endpoint, $method = 'GET', $data = null) {
        $ch = curl_init($this->apiEndpoint . $endpoint);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new Exception("API request failed with status $statusCode: $response");
        }

        return json_decode($response, true);
    }

    public function query($sql, $params = []) {
        if ($this->isCloudflare) {
            // Convert SQL query to API endpoint call
            // This is a simplified example - you'll need to implement proper SQL parsing
            if (strpos($sql, 'SELECT') === 0) {
                $table = $this->extractTableName($sql);
                return $this->makeApiRequest("/api/$table");
            }
            throw new Exception("Direct SQL queries not supported in Cloudflare mode");
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function getUser($id) {
        if ($this->isCloudflare) {
            return $this->makeApiRequest("/api/users/$id");
        }
        
        $stmt = $this->query("SELECT * FROM users WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        if ($this->isCloudflare) {
            return $this->makeApiRequest("/api/users", "POST", $data);
        }
        
        $sql = "INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)";
        $stmt = $this->query($sql, [
            $data['username'],
            $data['email'],
            $data['password'],
            $data['user_type']
        ]);
        return $this->connection->lastInsertId();
    }

    // Add similar methods for other entities (products, orders, etc.)

    private function extractTableName($sql) {
        // Simple SQL parser - you might want to use a proper SQL parser library
        preg_match('/FROM\s+(\w+)/i', $sql, $matches);
        return $matches[1] ?? null;
    }
}
