# Cobra
Cobra is a data management for PHP to clean, modify and transform your data. Whether you're working with data from a database, CSV files, or arrays, Cobra provides a suite of tools to help you handle, transform, and analyze your data efficiently.


## Features
- Load data from SQL databases and CSV files
- Transform and manipulate data using various methods
- Generate, display, and export data
- Handle missing data and perform statistical operations
- Merge, join, and concatenate data frames

## Using cobra
Cobra has 3 class, **DataFrame**, **DB** and **Series**
You can use the DB class to perform basic SQL  operations such as select and query

## Installation
```composer require bright-webb/cobra```

## Usage
If you want to use cobra with your database, you must create a .env file, to create and use .env, you need to install ```vlucas/phpdotenv``` package
configure your environment as follows
- DB_USERNAME
- DB_PASSWORD
- DB_HOST
- DB_DATABASE

and that's it, cobra will be able to connect to your database, if you're using Laravel, even better.
```
use Cobra\DataFrame;
```
Create a new DataFrame Object
```
$df = new DataFrame();
```

You can load your data in 3 ways, from your database, csv or json. To load from your database, you use the table method
```
$df->table('table_name');
// from csv
$df->fromCsv('path');
```
## Data Analysis
```
print_r($df->describe())
print($df->mean())
print($df->median())
print($df->average())
print($df->sum('column_name'))
print_r($df->groupBy('column_name'))
```

## Data Manipulation
```
$df->head(); // You can also pass the number of rows as argument 
print_r($df->toArray());
```
Can also be printed as html table
```
print($df->toTable());
```

Drop all null or blank columns
```
$df->dropna();
$df->fillna(); // You can also pass the the value to fill as argument
$df->dropColumn('column_name')

```

## Contributing
Contributions are welcome! Please open an issue or submit a pull request.



