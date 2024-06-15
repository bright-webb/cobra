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

    public function raw(string $sql, array $params = []): array {
        try {
            $stmt = $this->db->prepare($sql);
            foreach($params as $key => $value){
                $stmt->bindValue($key, $value);
            }
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function get(): array {
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

        return $this->raw($sql, $this->where);

        
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

    public function insert($table, $data){
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }

        $key = implode(',', array_keys($data));
        $value = implode(', :', array_values($data));

        $sql = "INSERT INTO $table ($key) VALUES ($value)";

        $stmt = $this->db->prepare($sql);
        foreach($data as $key => $val){
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();

        return $this->lastInsertId();
    }

    public function update($table, $data, $condition){
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new InvalidArgumentException('Invalid table name');
    }

    // Build the SET part of the query
    $set = [];
    foreach ($data as $key => $value) {
        $set[] =  "$key = :$key";
    }
    $set = implode(', ', $set);

    // Build the WHERE part of the query
    $whereClauses = [];
    foreach ($condition as $key => $value) {
        $whereClauses[] = "$key = :where_$key";
    }
    $where = implode(' AND ', $whereClauses);

    $sql = "UPDATE $table SET $set WHERE $where";
    $stmt = $this->db->prepare($sql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    foreach ($condition as $key => $value) {
        $stmt->bindValue(":where_$key", $value);
    }

    return $stmt->execute();
}


    public function delete($table, $condition){
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
        return count($row);
    }
}
