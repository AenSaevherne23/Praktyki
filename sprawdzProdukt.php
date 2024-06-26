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
        <form action="sprawdzProdukt.php" method="POST">
            <label for="ean">Podaj nr EAN:</label>
            <input type="text" id="ean" name="ean" />
            <br />
            <button type="submit">SPRAWDŹ</button>
        </form>

        <?php
        require_once("config.php");

        // Funkcja zaokrąglająca cenę do drugiego miejsca po przecinku
        function zaokraglij_do_drugiego_miejsca($cena) {
            return round($cena, 1) - 0.01;
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

                $wszystkieCeny = array();

                if (sqlsrv_has_rows($wynik)) {
                    echo "<table border='1'>";
                    echo "<tr><th>ID</th><th>Data</th><th>Sieć</th><th>EAN</th><th>Cena</th></tr>";
                    while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
                        $tk_id = $wiersz["tk_id"];
                        $tk_data = $wiersz["tk_data"] instanceof DateTime ? $wiersz["tk_data"]->format('Y-m-d') : $wiersz["tk_data"];
                        $tk_siec = $wiersz["tk_siec"];
                        $tk_plu = $wiersz["tk_plu"];
                        $tk_cena = number_format($wiersz["tk_cena"], 2, '.', '');

                        $zaokraglonaCena = zaokraglij_do_drugiego_miejsca($wiersz["tk_cena"]);
                        $wszystkieCeny[] = $zaokraglonaCena; // Dodanie zaokrąglonej ceny do pełnej listy cen

                        echo "<tr><td>$tk_id</td><td>$tk_data</td><td>$tk_siec</td><td>$tk_plu</td><td>" . number_format($zaokraglonaCena, 2, '.', '') . "</td></tr>";
                    }
                    echo "</table>";

                    $czyFiltruj = true;

                    while ($czyFiltruj) {
                        // Obliczenie mediany
                        sort($wszystkieCeny);
                        $liczbaCen = count($wszystkieCeny);
                        $mediana = 0;

                        if ($liczbaCen % 2 == 0) {
                            $mediana = ($wszystkieCeny[$liczbaCen / 2 - 1] + $wszystkieCeny[$liczbaCen / 2]) / 2;
                        } else {
                            $mediana = $wszystkieCeny[($liczbaCen - 1) / 2];
                        }

                        // Obliczenie odchylenia standardowego
                        $sumaOdchylen = 0;
                        foreach ($wszystkieCeny as $cena) {
                            $sumaOdchylen += pow($cena - $mediana, 2);
                        }
                        $odchylenieStandardowe = sqrt($sumaOdchylen / $liczbaCen);

                        // Filtrowanie cen odstających (powyżej mediany + 2.3 * odchylenie standardowe)
                        $cenyFiltr = array_filter($wszystkieCeny, function($cena) use ($mediana, $odchylenieStandardowe) {
                            return $cena >= $mediana - 2.3 * $odchylenieStandardowe && $cena <= $mediana + 2.3 * $odchylenieStandardowe;
                        });
                        

                        // Sprawdzenie, czy są jakieś ceny do odfiltrowania
                        if (count($cenyFiltr) == count($wszystkieCeny)) {
                            $czyFiltruj = false;
                        } else {
                            $wszystkieCeny = $cenyFiltr;
                        }
                    }

                    // Obliczenie średniej ceny
                    $sumaCen = array_sum($wszystkieCeny);
                    $liczbaProduktow = count($wszystkieCeny);
                    $sredniaCena = $liczbaProduktow ? $sumaCen / $liczbaProduktow : 0;
                    echo "<div class='result'><span>Średnia cena:</span> <span class='average'>" . number_format($sredniaCena, 2, '.', '') . "</span></div>";

                    // Obliczenie mediany z przefiltrowanych cen
                    sort($wszystkieCeny);
                    $liczbaCenFiltr = count($wszystkieCeny);
                    $medianaFiltr = 0;

                    if ($liczbaCenFiltr % 2 == 0) {
                        $medianaFiltr = ($wszystkieCeny[$liczbaCenFiltr / 2 - 1] + $wszystkieCeny[$liczbaCenFiltr / 2]) / 2;
                    } else {
                        $medianaFiltr = $wszystkieCeny[($liczbaCenFiltr - 1) / 2];
                    }
                    echo "<div class='result'><span>Mediana:</span> <span class='median'>" . number_format($medianaFiltr, 2, '.', '') . "</span></div>";

                    // Obliczenie dominanty z przefiltrowanych cen
                    $dominanta = array_count_values(array_map('strval', $wszystkieCeny));
                    $maxOccurrences = max($dominanta);
                    $pierwszaDominanta = array_search($maxOccurrences, $dominanta);

                    // Sprawdź, czy istnieje więcej niż jedna dominanta
                    foreach ($dominanta as $key => $value) {
                        if ($value === $maxOccurrences && $key > $pierwszaDominanta) {
                            $pierwszaDominanta = $key;
                        }
                    }
                    
                    echo "<div class='result'><span>Dominanta:</span> <span class='mode'>" . number_format($pierwszaDominanta, 2, '.', '') . "</span></div>";
                    echo "<div class='result'><span>Liczba cen wziętych do obliczeń:</span> <span class='count'>" . $liczbaCenFiltr . "</span></div>";

                    // Obliczenie ceny minimalnej i maksymalnej
                    if ($liczbaCenFiltr > 0) {
                        $minCena = min($wszystkieCeny);
                        $maxCena = max($wszystkieCeny);
                        echo "<div class='result'><span>Minimalna cena:</span> <span class='min'>" . number_format($minCena, 2, '.', '') . "</span></div>";
                        echo "<div class='result'><span>Maksymalna cena:</span> <span class='max'>" . number_format($maxCena, 2, '.', '') . "</span></div>";
                    }
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
