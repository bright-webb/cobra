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
    public $db;
    
    private $table;
    private $where = [];
    private $joins = [];
    private $select = '*';
    private $orderBy = [];
    private $limit;
    private $offset;

    public function __construct($user = null, $pass = null, $db_name = null, $host = null) {
       $this->user = $_ENV['DB_USERNAME'] ?? $user;
       $this->pass = $_ENV['DB_PASSWORD'] ?? $pass;
       $this->db_name = $_ENV['DB_DATABASE'] ?? $db_name;
       $this->host = $_ENV['DB_HOST']?? $host;

       try {
           $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user, $this->pass);
           $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH);
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

    public function raw(string $sql, array $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            foreach($params as $key => $value){
                $stmt->bindValue($key, $value);
            }
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Failed to select: " . $e->getMessage());
            throw new PDOException("Failed to select");
        }
    }

     public function select(array $columns): self {
        $this->select = implode(', ', $columns);
        return $this;
    }
     public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self {
        $this->offset = $offset;
        return $this;
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

     public function join(string $table, string $condition, string $type = 'INNER'): self {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function get() {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $key => $value) {
                $whereClauses[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if (!empty($this->limit)) {
            $sql .= " LIMIT {$this->limit}";
            if (!empty($this->offset)) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        $row = $this->raw($sql, $this->where);
        if($row){
            return $row->fetchAll(PDO::FETCH_BOTH)[0];
        }
        else{
            return 0;
        }
        

        
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

    public function lastInsertId(): int {
        return $this->db->lastInsertId();
    }

    public function insert($data){
        $table = $this->table;
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }

        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(array_values($data));
            return $this->db->lastInsertId(); 
        } catch (PDOException $e) {
            echo "Insert failed: " . $e->getMessage();
            return false;
        }
    }

    public function update($data){
        if (!$this->table) {
            throw new InvalidArgumentException('Table not set.');
        }

        $setClause = implode(", ", array_map(fn($col) => "$col = ?", array_keys($data)));
        $whereClause = implode(" AND ", array_map(fn($col) => "$col = ?", array_keys($this->where)));
        $sql = "UPDATE {$this->table} SET $setClause WHERE $whereClause";

        $stmt = $this->db->prepare($sql);
        $values = array_merge(array_values($data), array_values($this->where));

        try {
            return $stmt->execute($values);
        } catch (PDOException $e) {
            echo "Update failed: " . $e->getMessage();
            return false;
        }
}


    public function delete($condition){
        $table = $this->table;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }
        $set = [];
        foreach($condition as $key=>$value){
            $set[] = "$key = :$key";
        }

        $set = implode(" AND ", $condition);

        $sql = "DELETE FROM $table WHERE $condition";
        $stmt = $this->db->prepare($sql);

        foreach($condition as $key=>$value){
            $stmt->bindValue(":where_$key", $value);  
        }

        return $stmt->execute();
    }

    public function count(){
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
        $row = $this->raw($sql, $params);
        if($row){
            return $row->rowCount();
        }
        return 0;
    }
}
