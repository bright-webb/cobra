<?php
require __DIR__ .'/bootstrap.php';
use Cobra\DataFrame;
use Cobra\Series;



$sql = new DataFrame();
$csv = new DataFrame();
$employee = new DataFrame();
$employees = $employee->table('employees');

$customers = $sql->table('customers');
$consumer = $csv->fromCsv('consumer.csv');
$newData = $customers->concat($consumer, true);
$newData->head(4);
$newData->fillna($newData->median());

$newObj = $newData->merge($employees, 'employeeNumber');






?>