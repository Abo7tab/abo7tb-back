<?php
$tables = DB::select('SHOW TABLES');
$sql = "-- Database Dump\n\n";

foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    
    // Create table syntax
    $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0];
    $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
    $sql .= array_values((array)$createTable)[1] . ";\n\n";
    
    // Insert data
    $rows = DB::table($tableName)->get();
    foreach ($rows as $row) {
        $row = (array)$row;
        $values = array_map(function($val) {
            if ($val === null) return 'NULL';
            return "'" . addslashes($val) . "'";
        }, array_values($row));
        
        $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
    }
    $sql .= "\n\n";
}

file_put_contents('C:\Users\moham\OneDrive\Desktop\database_backup.sql', $sql);
echo "Database dumped successfully to Desktop\database_backup.sql\n";
