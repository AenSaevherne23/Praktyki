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
$wynik = sqlsrv_query($conn, $zapytanie);

if ($wynik === false) {
    echo "Błąd wykonania zapytania: ";
    die(print_r(sqlsrv_errors(), true));
}

if(sqlsrv_has_rows($wynik)) {
    echo "<ul>";
    while($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
        $tk_id = $wiersz["tk_id"];
        $tk_data = $wiersz["tk_data"] instanceof DateTime ? $wiersz["tk_data"]->format('Y-m-d H:i:s') : $wiersz["tk_data"];
        $tk_siec = $wiersz["tk_siec"];
        $tk_plu = $wiersz["tk_plu"];
        $tk_cena = $wiersz["tk_cena"];
        $tk_zweryfikowane = $wiersz["tk_zweryfikowane"];

        echo "<li>$tk_id $tk_data $tk_siec $tk_plu $tk_cena $tk_zweryfikowane</li>";
    }
    echo "</ul>";
} else {
    echo "BRAK";
}

// Zamknięcie połączenia
sqlsrv_free_stmt($wynik);
sqlsrv_close($conn);
?>

</body>
</html>
