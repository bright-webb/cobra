<?php

namespace Cobra;
use InvalidArgumentException;

class Series {
    private $data = [];

    public function __construct($data = null){
       if($data !== null){
            if(is_array($data) && count($data) > 0){
                $this->data = $data;
            }
            else if(is_object($data) && method_exists($data, 'toArray')){
                $this->data = $data->toArray();
            }
            else {
                throw new InvalidArgumentException("Data should be a non-empty array");
            }
       }
    }

    public function set($data){

    }

    // Get data using index
    public function getIndex($index){
       if(is_array($this->data)){
        $this->flatten();
        return $this->data[$index];
       }
    }

    public function loc(int $position, string $column){
        if(is_array($this->data)){
            $array = $this->data;
            if(array_key_exists($position, $this->data) && array_key_exists($column, $this->data[$position])){
                $this->data = $this->data[$position][$column];
                return $this;
            }
            else{
                echo "Key not found";
            }
  
        }
    }

    public function row(int $pos){
        if(array_key_exists($pos, $this->data)){
            $this->data = $this->data[$pos];
            return $this;
        }
        else{
            echo "Invalid index position: Position not ound";
            exit;
        }
    }

    public function col(string $loc){
        if(array_key_exists($loc, $this->data)){
            $this->data = $this->data[$loc];
            return $this;
        }
        else{
            echo "Column not found";
            exit;
        }
    }

    public function keys(){
        $this->data = array_keys($this->data);

        return $this->toArray();
    }

    public function values(){
        $this->data = array_values($this->data);
        return $this->toArray();
    }

    public function toArray(){
        return $this->data;
    }

    public function toObject(){
        return (object)$this->data;

    }

    public function dropna(){
         /* I really don't know what to say.
           But this method drops keys with empty or null values in an associative array
           1D or 2D
        */

        $data = [];
        $keys = []; 
        $values = []; 
        

        foreach($this->data as $col => $row){
            if(is_array($row)){
                foreach($row as $key=>$value){
                    if(isset($row[$key])){
                       $data[] = [$key=>$value];
                    }
                }  
            }
            else{
                $keys[] = $col;
                $values[] = $row;
            }
        }
        for($i = 0; $i < count($keys); $i++){
            for($j = $i; $j < count($values);){  
                if(isset($values[$i])){
                    $data[$keys[$i]] = $values[$i];
                }
                break;
            }
        }
        $this->data = $data;
    
        return new self($this);
    }

    public function drop(array $cols = []){
        // This method is used to drop a given set of columns
        $obj = (object)$this->data; // Convert the data to object
        foreach($cols as $col){
            unset($obj->$col);
        }
        
        $this->data = (array)$obj;
        return new self($this);
    }

    public function sum(){
        return array_sum($this->data);
    }



    public function mean(){
        $count = count($this->data);
        if($count === 0){
            return null;
        }

        return array_sum($this->data) / $count;
    }

    public function median(){
        $count = count($this->data);
        if($count === 0){
            return null;
        }

        $values = $this->data;
        sort($values);
        $middle = floor(($count - 1) / 2);
        if($count % 2){
            return $values[$middle];
        }
        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    public function min(){
        return min($this->data);
    }

    public function max(){
        return max($this->data);
    }

    public function std(){
        $mean = $this->mean();
        if($mean === null){
            return null;
        }

        $sum = array_reduce($this->data, function($carry, $item) use ($mean) {
            $carry += pow($item - $mean, 2);
            return $carry;
        }, 0);
        return sqrt($sum / count($this->data));
    }

    public function map(callable  $callback){
        return new self(array_map($callback, $this->data));
    }

    public function filter(callable $callback){
        return new self(array_filter($this->data, $callback));
    }

    public function count(){
        return count($this->data);
    }

    public function unique(){
        return array_unique($this->data);
    }

    // Create a 2d array with specified rows and columns
    public function array($rows, $column){
        $array = [];
        for($i = 0; $i < $rows; $i++){
            $row = [];
            for($j = 0; $j < $column; $j++){
                $index = $i * $column + $j;
                $row[] = isset($this->data[$index]) ? $this->data[$index] : null;
            }
            $array[] = $row;
        }
        return $array;
    }

    public function flatten(){
        $result = [];
        array_walk_recursive($this->data, function($item) use (&$result){
            $result[] = $item;
        });
        if (count(array_filter(array_keys($this->data), 'is_string')) > 0) {
            $result = array_values($result);
        }
        $this->data = $result;
        return $this;
    }

    public function shuffle(){
        $shuffle = $this->data;
        shuffle($shuffle);
        $this->data = $shuffle;
        return new self($shuffle);
    }

    public function print(){
        print_r($this->data);
    }

    public function toJson(){
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function asJson(){
        var_dump(json_encode($this->data, JSON_PRETTY_PRINT));
    }
}