<?php
namespace Cobra;
use Cobra\DB;
use InvalidArgumentException;

class DataFrame{
    public $data;
    private $columns;
    
    public function __construct($data = null, $columns = null) {
        if ($data !== null) {
            if (is_array($data) && count($data) > 0) {
                if ($columns === null) {
                    $this->columns = array_keys($data[0]);
                } else {
                    $this->columns = $columns;
                }
                $this->data = $data;
            } else {
                throw new InvalidArgumentException("Data should be a non-empty array");
            }
        }
        
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }

    public function table($table){
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Invalid table name');
        }
        $db = new DB();

        $result =  $db->select("SELECT * FROM $table");
        if ($result) {
            $this->data = $result;
            $this->columns = array_keys($result[0]);
        }
        return new self($result);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
        if (!empty($data)) {
            $this->columns = array_keys($data[0]);
        }
    }


    // Load csv data
    public function fromCSV($file){
        if (!file_exists($file)) {
            throw new InvalidArgumentException('Invalid file path');
        }
        $data = [];
        $columns = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Get the first row as column headings
            $columns = fgetcsv($handle, 1000, ",");
            $columnCount = count($columns);
    
            // Read the remaining rows as data
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) === $columnCount) {
                    // Combine the column headings and row values into an associative array
                    $data[] = array_combine($columns, $row);
                } else {
                    // Skip the row if the number of values does not match
                    continue;
                }
            }
    
            fclose($handle);
        }
        $this->data = $data;
        $this->columns = $columns;
        return $this;
    }

    // Load json data
    public function loadJson($file){
        $json = file_get_contents($file);
        $this->data =  json_decode($json, true);
        return $this->data;
    }

    public function toArray(){
        return $this->data;
    }

    public function toTable($drop = []) {
        if (empty($this->data)) {
            echo '<table><tr><td>No data available</td></tr></table>';
        }

        else{
            // Filter columns to exclude
                $columns = array_diff($this->columns, $drop);
                
                $html = '<table border="1"><thead><tr>';
                foreach ($columns as $column) {
                    $html .= "<th>$column</th>";
                }
                $html .= '</tr></thead><tbody>';

                foreach ($this->data as $row) {
                    $html .= '<tr>';
                    foreach ($columns as $column) {
                        $html .= "<td>{$row[$column]}</td>";
                    }
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
                return $html;
                }
    }

    

    public function toCSV() {
        if (empty($this->data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->columns); // Add the headers
        foreach ($this->data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function toJSON() {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

   // Drop columns with null or empty values
   public function dropna() {
    if ($this->data) {
        $columnsToKeep = [];
        foreach ($this->columns as $column) {
            $dropColumn = false;
            foreach ($this->data as $row) {
                if ($row[$column] === NULL || $row[$column] === '') {
                    $dropColumn = true;
                    break;
                }
            }
            if (!$dropColumn) {
                $columnsToKeep[] = $column;
            }
        }
        $newData = [];
        foreach ($this->data as $row) {
            $newRow = [];
            foreach ($columnsToKeep as $column) {
                $newRow[$column] = $row[$column];
            }
            $newData[] = $newRow;
        }
        $this->data = $newData;
        $this->columns = $columnsToKeep;
    }
    return $this->data;
}

public function dropNan(){
    if($this->data){
        $columnsToKeep = [];
        foreach($this->columns as $column){
            $dropColumn = false;
            foreach($this->data as $row){
                if(gettype($row[$column]) !== 'int' || !is_numeric($row[$column])){
                    $dropColumn = true;
                    break;
                }
            }
            if(!$dropColumn){
                $columnsTokeep[] = $column;
            }
        }
        $newData = [];
        foreach($this->data as $row){
            $newRow = [];
            foreach($columnsTokeep as $column){
                $newRow[$column] = $row[$column];
            }
            $newData = $newRow;
        }
        $this->data = $newData;
        $this->columns = $columnsToKeep;
    }
    return $this->data;
}

public function head($rows = 10) {
    $headData = array_slice($this->data, 0, $rows);
    $this->data = $headData;
    return new self($headData);
}

public function tail($n = 10)
{
    // Check if $this->data is an array
    if (is_array($this->data)) {
        $tailData = array_slice($this->data, -$n, null, true);
        $this->data = $tailData;
        return $tailData;
    } else {
        throw new \Exception('$this->data is not an array');
    }
}

public function describe(){
    $desc = [];
    foreach($this->columns as $column){
        $values = array_column($this->data, $column);
        $desc[$column] = [
            'mean' => array_sum($values) / count($values),
            'min' => min($values),
            'max' => max($values),
            'count' => count($values),
        ];
        return $desc;
    }
}

    public function addColumn($name, array $values){
        foreach($this->data as $key => &$row){
            $row[$name] = $values[$key];
        }
        $this->columns[] = $name;
    }
    public function setColumn(array $columns){
        $this->columns = $columns;
    }


public function getColumns(){
    return $this->columns;
}

public function getColumn(string $column)
{
    if (!in_array($column, $this->columns)) {
        throw new InvalidArgumentException("$column not found");
    }

    return $this->columns[$column];
}

public function dropColumn(array $columnsToDrop = []){
    if (empty($columnsToDrop)) {
        throw new InvalidArgumentException('Columns to drop must be specified.');
    }
    foreach ($this->data as &$row) {
        foreach ($columnsToDrop as $column) {
            unset($row[$column]);
        }
    }
    unset($row);

    $this->columns = array_diff($this->columns, $columnsToDrop);

    return $this;
}

public function stdDev(array $values): float{
    $mean = array_sum($values) / count($values);
    $sumSquaredDiffs = 0;
    foreach($values as $value){
        $sumSquaredDiffs += pow($value - $mean, 2);
    }
    return sqrt($sumSquaredDiffs / count($values));
}

// Fill null or empty columns
public function fillna($value = ''){
    if($this->data) {
        foreach($this->data as &$row){
            foreach($row as $key => &$val){
                if($val === null || $val === '' || $val === 'N/A'){
                    $val = $value;
                }
            }
        }
        unset($row);
        unset($val);
    }
    return $this->data;
}

public function renameColumn($oldName, $newName) {
    if ($this->data && in_array($oldName, $this->columns)) {
        $index = array_search($oldName, $this->columns);
        $this->columns[$index] = $newName;

        foreach ($this->data as &$row) {
            $row[$newName] = $row[$oldName];
            unset($row[$oldName]);
        }
        unset($row); 
    }
    return $this->data;
}
public function replaceColumn($column, $oldValue, $newValue) {
    if ($this->data && in_array($column, $this->columns)) {
        foreach ($this->data as &$row) {
            if ($row[$column] === $oldValue) {
                $row[$column] = $newValue;
            }
        }
        unset($row); 
    }
    return $this->data;
}

public function at($row, $column){
    return $this->data[$row][$column] ?? null;
}

public function isNull(){
    $result = [];
    foreach($this->data as $row){
        $result[] = array_map(function($value) {
            return $value === null;
        }, $row);
    }
    return $result;
}

public function notNull(){
    $result = [];
    foreach($this->data as $row){
        $result[] = array_map(function($value) {
            return $value !== null;
        }, $row);
    }
    return $result;
}

public function replace($column, $oldValue, $newValue){
    foreach($this->data as &$row){
        if($row[$column] === $oldValue){
            $row[$column] = $newValue;
        }
    }
    unset($row);
    return $this;
}
public function map($column, callable $callback) {
    foreach ($this->data as &$row) {
        $row[$column] = $callback($row[$column]);
    }
    unset($row);
    return $this;
}

public function filter(array $conditions) {
    $filteredData = [];
    
    foreach ($this->data as $row) {
        $allConditions = true;

        foreach ($conditions as $column => $conditionCallback) {
            if (!$conditionCallback($row[$column])) {
                $allConditions = false;
                break; 
            }
        }

        if ($allConditions) {
            $filteredData[] = $row;
        }
    }

    $this->data = $filteredData;
    return $this;
}

// Method to group data by a column
public function groupBy($column) {
    $groupedData = [];
    foreach ($this->data as $row) {
        $key = $row[$column];
        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [];
        }
        $groupedData[$key][] = $row;
    }
    return $groupedData;
}


public function concat(DataFrame $other, $forceRows = true) {
    // Combine the column names
    $this->columns = array_merge($this->columns, array_diff($other->getColumns(), $this->columns));

    // Iterate over the rows and combine them
    foreach ($this->data as $key => $row) {
        if (isset($other->data[$key])) {
            $this->data[$key] = array_merge($row, $other->data[$key]);
        } else {
            $this->data[$key] = array_merge($row, array_fill_keys($other->getColumns(), null));
        }
    }

    foreach ($other->data as $key => $row) {
        if (!isset($this->data[$key])) {
            $this->data[$key] = array_merge(array_fill_keys($this->columns, null), $row);
        }
    }

    return $this;
}

public function join(DataFrame $other, $on, $how = 'inner') {
    $joinedData = [];
    $thisData = $this->getData();
    $otherData = $other->getData();
    $otherColumns = $other->getColumns();

    // Create associative arrays for faster lookup
    $thisAssoc = [];
    foreach ($thisData as $row) {
        $thisAssoc[$row[$on]] = $row;
    }

    $otherAssoc = [];
    foreach ($otherData as $row) {
        $otherAssoc[$row[$on]] = $row;
    }

    switch ($how) {
        case 'inner':
            foreach ($thisAssoc as $key => $row1) {
                if (isset($otherAssoc[$key])) {
                    $joinedData[] = array_merge($row1, $otherAssoc[$key]);
                }
            }
            break;

        case 'left':
            foreach ($thisAssoc as $key => $row1) {
                $joinedData[] = array_merge($row1, isset($otherAssoc[$key]) ? $otherAssoc[$key] : array_fill_keys($otherColumns, null));
            }
            break;

        case 'right':
            foreach ($otherAssoc as $key => $row2) {
                $joinedData[] = array_merge(isset($thisAssoc[$key]) ? $thisAssoc[$key] : array_fill_keys(array_keys($row2), null), $row2);
            }
            break;

        case 'outer':
            $allKeys = array_unique(array_merge(array_keys($thisAssoc), array_keys($otherAssoc)));
            foreach ($allKeys as $key) {
                $row1 = isset($thisAssoc[$key]) ? $thisAssoc[$key] : array_fill_keys(array_keys(reset($thisAssoc)), null);
                $row2 = isset($otherAssoc[$key]) ? $otherAssoc[$key] : array_fill_keys($otherColumns, null);
                $joinedData[] = array_merge($row1, $row2);
            }
            break;
    }

    $newDataFrame = new self();
    $newDataFrame->setData($joinedData);
    return $newDataFrame;
}

public function merge(DataFrame $other, $on, $how = 'inner') {
    return $this->join($other, $on, $how);
}

// Method to count values in a column
public function count($column) {
    $count = [];
    foreach ($this->data as $row) {
        $value = $row[$column];
        if (!isset($count[$value])) {
            $count[$value] = 0;
        }
        $count[$value]++;
    }
    return $count;
}

// Get the size of the data, in column and row
public function size(){
    $rowCount = count($this->data);
    $columnCount = count($this->columns);

    return "[$rowCount, $columnCount]";
}

public function sum($column) {
    if (!in_array($column, $this->columns)) {
        print("Column $column does not exist");
    }

    $sum = 0;
    foreach ($this->data as $row) {
        if (is_numeric($row[$column])) {
            $sum += $row[$column];
        }
    }

    return $sum;
}

public function mean() {
    $values = array_merge(...$this->data);
    $values = array_filter($values, function($value) {
        return !is_null($value) && $value !== '';
    });

    $count = count($values);

    if ($count === 0) {
        return null; 
    }

    return array_sum($values) / $count;
}

public function median() {
    // Flatten the 2D data array into a 1D array of values
    $values = array_merge(...$this->data);

    // Remove any null or empty values
    $values = array_filter($values, function($value) {
        return !is_null($value) && $value !== '';
    });

    // Sort the values in ascending order
    sort($values);

    $count = count($values);
    $middleIndex = floor(($count - 1) / 2);

    if ($count % 2 === 0) {
        return ($values[$middleIndex] + $values[$middleIndex + 1]) / 2;
    } else {
        return $values[$middleIndex];
    }
}

public function average() {
    $values = array_merge(...$this->data);

    $values = array_filter($values, function($value) {
        return !is_null($value) && $value !== '';
    });

    $count = count($values);

    if ($count === 0) {
        return null; 
    }

    $sum = array_sum($values);

    return $sum / $count;
}

public function max() {
    return max(array_column($this->data, $this->columns));
}

public function min($column) {
    return min(array_column($this->data, $column));
}

public function normalize() {
    foreach ($this->data as &$row) {
        // Filter out non numeric values
        $numericValues = array_filter($row, 'is_numeric');

        if (empty($numericValues)) {
            continue; // Skip rows without numeric values
        }

        $max = max($numericValues);
        $min = min($numericValues);

        if ($max == $min) {
            foreach ($row as &$value) {
                if (is_numeric($value)) {
                    $value = 0; 
                }
            }
        } else {
            foreach ($row as &$value) {
                if (is_numeric($value)) {
                    $value = ($value - $min) / ($max - $min);
                }
            }
        }
    }
}

    public function split($trainRatio = 0.8) {
        shuffle($this->data); // Shuffle data randomly
        $splitIndex = (int) (count($this->data) * $trainRatio);
    
        $trainData = array_slice($this->data, 0, $splitIndex);
        $testData = array_slice($this->data, $splitIndex);
    
        return [$trainData, $testData];
    }

    public function dropDuplicates($subset = null, $keep = 'first') {
        $result = [];
        $seen = [];
    
        foreach ($this->data as $key => $row) {
            $columns = $subset ? array_intersect_key($row, array_flip($subset)) : $row;
            $hash = json_encode($columns);
    
            if (!isset($seen[$hash])) {
                $seen[$hash] = true;
                $result[$key] = $row;
            } elseif ($keep == 'last') {
                unset($result[$seen[$hash]]);
                $result[$key] = $row;
                $seen[$hash] = true;
            }
        }
    
        $this->data = $result;
        return $this;
    }

    public function apply($func, $axis = 0) {
        if ($axis == 0) {
            $result = array_map($func, array_values($this->data));
            return $result;
        } else {
            $result = array_map($func, ...$this->data);
            return array_combine($this->columns, $result);
        }
    }

    public function valueCounts($col = null, $sort = true, $ascending = false) {
        if ($col === null) {
            $values = array_merge(...array_values($this->data));
            $counts = array_count_values($values);
        } else {
            $values = array_column($this->data, $col);
            $counts = array_count_values($values);
        }
    
        if ($sort) {
            arsort($counts, $ascending);
        }
    
        return $counts;
    }
    
}