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
<form action="index.php" method="POST">
    <label>Podaj nr EAN:</label>
    <input type="text" name="ean" />
    <br />
    <input type="submit" value="SPRAWDŹ" />
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["ean"])) {
        $ean = htmlspecialchars($_POST["ean"]);
        echo "Podany nr EAN: " . $ean . "<br />";

        // Zapytanie SQL filtrowane po kolumnie tk_plu
        $zapytanie = "SELECT TOP (100) [tk_id], [tk_data], [tk_siec], [tk_plu], [tk_cena] 
                      FROM [leclerc].[dbo].[tw_konkurencja] 
                      WHERE [tk_plu] = ?";
        
        // Przygotowanie i wykonanie zapytania
        $params = array($ean);
        $wynik = sqlsrv_query($conn, $zapytanie, $params);

        if ($wynik === false) {
            echo "Błąd wykonania zapytania: ";
            die(print_r(sqlsrv_errors(), true));
        }

        $sumaCen = 0;
        $liczbaProduktow = 0;
        $ceny = array();

        if (sqlsrv_has_rows($wynik)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Data</th><th>Sieć</th><th>EAN</th><th>Cena</th></tr>";
            while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
                $tk_id = $wiersz["tk_id"];
                $tk_data = $wiersz["tk_data"] instanceof DateTime ? $wiersz["tk_data"]->format('Y-m-d') : $wiersz["tk_data"];
                $tk_siec = $wiersz["tk_siec"];
                $tk_plu = $wiersz["tk_plu"];
                $tk_cena = number_format($wiersz["tk_cena"], 2, '.', '');

                $sumaCen += $wiersz["tk_cena"];
                $liczbaProduktow++;

                $ceny[] = $wiersz["tk_cena"]; // Dodanie ceny do tablicy

                echo "<tr><td>$tk_id</td><td>$tk_data</td><td>$tk_siec</td><td>$tk_plu</td><td>$tk_cena</td></tr>";
            }
            echo "</table>";

            // Obliczenie średniej ceny
            $sredniaCena = $liczbaProduktow ? $sumaCen / $liczbaProduktow : 0;
            echo "<br />Średnia cena: " . number_format($sredniaCena, 2, '.', '');

            // Obliczenie dominaty
            $dominanta = array_count_values($ceny);
            arsort($dominanta);
            $pierwszaDominanta = key($dominanta);
            echo "<br />Dominanta: " . number_format($pierwszaDominanta, 2, '.', '');
        } else {
            echo "Brak wyników.";
        }

        // Zamknięcie połączenia
        sqlsrv_free_stmt($wynik);
    } else {
        echo "Proszę podać numer EAN.";
    }
}
sqlsrv_close($conn);
?>

</body>
</html>
