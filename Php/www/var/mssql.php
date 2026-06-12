<?php
$serverName = "95.173.83.115";
$connectionOptions = array(
    "Database" => "JasK2Db",
    "Uid" => "sa",
    "PWD" => "Perft1535"
);

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=" . $connectionOptions['Database'], $connectionOptions['Uid'], $connectionOptions['PWD']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
