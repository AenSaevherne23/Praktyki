<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprawdź Produkt</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Sprawdź Produkt</h1>
        <form action="index.php" method="POST">
            <label for="ean">Podaj nr EAN:</label>
            <input type="text" id="ean" name="ean" />
            <br />
            <button type="submit">SPRAWDŹ</button>
        </form>

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
                    echo "<div class='result'><span>Średnia cena:</span> <span class='average'>" . number_format($sredniaCena, 2, '.', '') . "</span></div>";

                    // Obliczenie mediany
                    sort($ceny);
                    $liczbaCen = count($ceny);
                    $mediana = 0;

                    if ($liczbaCen % 2 == 0) {
                        $mediana = ($ceny[$liczbaCen / 2 - 1] + $ceny[$liczbaCen / 2]) / 2;
                    } else {
                        $mediana = $ceny[($liczbaCen - 1) / 2];
                    }
                    echo "<div class='result'><span>Mediana:</span> <span class='median'>" . number_format($mediana, 2, '.', '') . "</span></div>";

                    // Obliczenie dominaty
                    $dominanta = array_count_values($ceny);
                    arsort($dominanta);
                    $pierwszaDominanta = key($dominanta);
                    echo "<div class='result'><span>Dominanta:</span> <span class='mode'>" . number_format($pierwszaDominanta, 2, '.', '') . "</span></div>";
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

    </div>
</body>
</html>
