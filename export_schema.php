<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select('SHOW TABLES');
$database = DB::getDatabaseName();
$tableKey = "Tables_in_{$database}";

$sql = "-- Parental Control Database Schema Dump\n";
$sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $tableInfo) {
    $tableName = $tableInfo->$tableKey;
    
    // Ignore views for schema dump (or dump them as views, but let's just get tables)
    // We can use SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'
}

$tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
foreach ($tables as $tableInfo) {
    $tableName = $tableInfo->$tableKey;
    
    $createStmt = DB::select("SHOW CREATE TABLE `{$tableName}`");
    $createTableSql = $createStmt[0]->{'Create Table'};
    
    $sql .= "-- Table structure for table `{$tableName}`\n";
    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
    $sql .= $createTableSql . ";\n\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

File::put(__DIR__.'/parental_control_schema.sql', $sql);
echo "Schema dumped successfully to parental_control_schema.sql\n";
