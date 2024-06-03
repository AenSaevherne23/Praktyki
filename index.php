<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Page Title</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='main.css'>
    <script src='main.js'></script>
</head>
<body>
    
<?php
$serverName = "localhost\\SQLEXPRESS";
$database = "leclerc";
$username = "sa";
$password = "123456789";

// Połączenie za pomocą SQLSRV
$connectionInfo = array("Database" => $database, "UID" => $username, "PWD" => $password);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn) {
    echo "Połączenie z bazą danych powiodło się!";
} else {
    echo "Błąd połączenia: ";
    die(print_r(sqlsrv_errors(), true));
}
?>
<br />
<h3>Testowe zapytanie</h3>

<?php
$zapytanie = "SELECT TOP (100) [tk_id], [tk_data], [tk_siec], [tk_plu], [tk_cena], [tk_zweryfikowane] FROM [leclerc].[dbo].[tw_konkurencja]";
$wynik = $conn->query($zapytanie);

if($wynik->num_rows > 0)
{
    while($wiersz = $wynik->fetch_assoc())
    {
        echo "<li>".$wiersz["tk_id"]." ".$wiersz["tk_data"]." ".$wiersz["tk_siec"]." ".$wiersz["tk_plu"]." ".$wiersz["tk_cena"]." ".$wiersz["tk_zweryfikowane"]."</li>";
    }
}
else
{
    echo "BRAK";
}
$conn->close();
?>

</body>
</html>