<?php
require_once 'config.php';
$pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DEFAULT_DB, DB_USER, DB_PASSWORD);
$pdo->exec("SET CHARACTER SET utf8mb4");
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$output = "-- Backup created on ".date('Y-m-d H:i:s')."\n\n";
foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $output .= "DROP TABLE IF EXISTS `$table`;\n".$create['Create Table'].";\n\n";
    $rows = $pdo->query("SELECT * FROM `$table`");
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $cols = array_keys($row);
        $vals = array_map(function($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote($v);
        }, array_values($row));
        $output .= "INSERT INTO `$table` (`".implode("`, `", $cols)."`) VALUES (".implode(", ", $vals).");\n";
    }
    $output .= "\n";
}
file_put_contents("backup_".date('Ymd_His').".sql", $output);
echo "Бэкап создан: backup_".date('Ymd_His').".sql";
?>