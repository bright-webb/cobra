<?php
namespace Cobra;

use PDO;
use PDOException;
use InvalidArgumentException;

class DB {
    private $user;
    private $pass;
    private $db_name;
    private $host;
    private $db;
    
    private $table;
    private $where = [];

    public function __construct() {
       $this->user = env('DB_USERNAME');
       $this->pass = env('DB_PASSWORD');
       $this->db_name = env('DB_DATABASE');
       $this->host = env('DB_HOST');

       try {
           $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user, $this->pass);
           $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
       } catch(PDOException $e) {
           error_log("Connection failure: " . $e->getMessage());
           echo "Connection error ". $e->getMessage();
       }
    }

    public function query(string $sql, array $params = []): bool {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            error_log("Execution failed: " . $e->getMessage());
            throw new PDOException("Execution failed");
        }
    }

    public function select(string $sql, array $params = []): array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Failed to select: " . $e->getMessage());
            throw new PDOException("Failed to select");
        }
    }

    public function selectOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: null; 
        } catch (PDOException $e) {
            error_log("Failed to select: " . $e->getMessage());
            throw new PDOException("Failed to select");
        }
    }
    

    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    public function where(array $params): self {
        foreach ($params as $key => $value) {
            $this->where[$key] = $value;
        }
        return $this;
    }

    public function get(): array {
        $whereClause = '';
        $params = [];

        if (count($this->where) > 0) {
            $clauses = [];
            foreach ($this->where as $key => $value) {
                $clauses[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $clauses);
        }

        $sql = "SELECT * FROM {$this->table} $whereClause";
        return $this->select($sql, $params);
    }

    // Select the first row of the table that matches the any given condition
    public function first(): ?array {
        $whereClause = '';
        $params = [];

        if(count($this->where) > 0){
            $clauses = [];
            foreach($this->where as $key => $value){
                $clauses[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            $whereClause = 'WHERE '. implode(' AND ', $clauses);
            $sql = "SELECT * FROM {$this->table} $whereClause LIMIT 1";
            $result = $this->selectOne($sql, $params);

            return $result ?: [];
        }
    }
}
