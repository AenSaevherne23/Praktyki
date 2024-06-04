<?php
// Połączenie z bazą danych
$serverName = "localhost\\SQLEXPRESS";
$database = "leclerc";
$username = "sa";
$password = "123456789";

$connectionInfo = array("Database" => $database, "UID" => $username, "PWD" => $password);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn) {
   //echo "Połączenie z bazą danych powiodło się!";
} else {
    echo "Błąd połączenia: ";
            die(print_r(sqlsrv_errors(), true));
}
?>