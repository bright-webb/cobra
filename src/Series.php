<?php

namespace Cobra;

class Series {
    private $data = [];
    private $name;

    public function __construct(array $data = [], $name = null){
        $this->data = $data;
        $this->name = $name;
    }

    // Get data using index
    public function getIndex($index){
       $this->flatten($this->data);
       return $this->data[$index];
    }

    public function getAll(){
        return $this->data;
    }

    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
    }

    public function sum(){
        return array_sume($this->data);
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
        return new self(array_map($callback, $this->data), $this->name);
    }

    public function filter(callable $callback){
        return new self(array_filter($this->data, $callback), $this->name);
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
        return new self($result, $this->name);
    }

    public function shuffle(){
        $shuffle = $this->data;
        shuffle($shuffle);
        $this->data = $shuffle;
        return new self($shuffle, $this->name);
    }

    public function print(){
        print_r($this->data);
    }
}