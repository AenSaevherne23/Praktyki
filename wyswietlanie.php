<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wyświetl</title>
</head>
<body>
<?php
require_once("config.php");

// Funkcja zaokrąglająca cenę do drugiego miejsca po przecinku
function zaokraglij_do_drugiego_miejsca($cena) {
    return round($cena, 1) - 0.01;
}

// Zapytanie SQL dla unikalnych numerów EAN z tabeli
$zapytanie_eany = "SELECT DISTINCT [tk_plu] FROM [leclerc].[dbo].[tw_konkurencja]";
$wynik_eany = sqlsrv_query($conn, $zapytanie_eany);

if ($wynik_eany === false) {
    echo "Błąd wykonania zapytania: ";
    die(print_r(sqlsrv_errors(), true));
}

if (sqlsrv_has_rows($wynik_eany)) {
    echo "<table border='1'>";
    echo "<tr><th>Numer EAN</th><th>Średnia cena</th><th>Mediana</th><th>Dominanta</th><th>Liczba Produktów</th></tr>";

    while ($wiersz_ean = sqlsrv_fetch_array($wynik_eany, SQLSRV_FETCH_ASSOC)) {
        $ean = $wiersz_ean["tk_plu"];

        // Zapytanie SQL dla konkretnego numeru EAN
        $zapytanie = "SELECT [tk_id], [tk_data], [tk_siec], [tk_plu], [tk_cena] 
                      FROM [leclerc].[dbo].[tw_konkurencja] 
                      WHERE [tk_plu] = ?";
        
        // Przygotowanie i wykonanie zapytania dla danego numeru EAN
        $params = array($ean);
        $wynik = sqlsrv_query($conn, $zapytanie, $params);

        if ($wynik === false) {
            echo "Błąd wykonania zapytania dla EAN $ean: ";
            die(print_r(sqlsrv_errors(), true));
        }

        $cenyProduktow = array();

        while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
            $tk_cena = zaokraglij_do_drugiego_miejsca($wiersz["tk_cena"]);
            $cenyProduktow[] = $tk_cena;
        }

        if (empty($cenyProduktow)) {
            continue;
        }

        $czyFiltruj = true;

        while ($czyFiltruj) {
            // Sortowanie cen
            sort($cenyProduktow);

            // Obliczenie mediany
            $liczbaCen = count($cenyProduktow);
            if ($liczbaCen % 2 == 0) {
                $mediana = ($cenyProduktow[$liczbaCen / 2 - 1] + $cenyProduktow[$liczbaCen / 2]) / 2;
            } else {
                $mediana = $cenyProduktow[($liczbaCen - 1) / 2];
            }

            // Obliczenie odchylenia standardowego
            $sumaOdchylen = 0;
            foreach ($cenyProduktow as $cena) {
                $sumaOdchylen += pow($cena - $mediana, 2);
            }
            $odchylenieStandardowe = sqrt($sumaOdchylen / $liczbaCen);

            // Filtrowanie cen odstających
            $cenyFiltr = array_filter($cenyProduktow, function($cena) use ($mediana, $odchylenieStandardowe) {
                return $cena >= $mediana - 2.3 * $odchylenieStandardowe && $cena <= $mediana + 2.3 * $odchylenieStandardowe;
            });

            // Sprawdzenie, czy są jakieś ceny do odfiltrowania
            if (count($cenyFiltr) == count($cenyProduktow)) {
                $czyFiltruj = false;
            } else {
                $cenyProduktow = $cenyFiltr;
            }
        }

        // Obliczenie średniej ceny z przefiltrowanych cen
        $sumaCen = array_sum($cenyProduktow);
        $liczbaProduktowFiltr = count($cenyProduktow);
        $sredniaCenaProduktow = $liczbaProduktowFiltr ? $sumaCen / $liczbaProduktowFiltr : 0;

        // Obliczenie mediany z przefiltrowanych cen
        sort($cenyProduktow);
        if ($liczbaProduktowFiltr % 2 == 0) {
            $medianaFiltr = ($cenyProduktow[$liczbaProduktowFiltr / 2 - 1] + $cenyProduktow[$liczbaProduktowFiltr / 2]) / 2;
        } else {
            $medianaFiltr = $cenyProduktow[($liczbaProduktowFiltr - 1) / 2];
        }

        // Obliczenie dominanty z przefiltrowanych cen
        $dominanta = array_count_values(array_map('strval', $cenyProduktow));
        $maxOccurrences = max($dominanta);
        $pierwszaDominanta = array_search($maxOccurrences, $dominanta);

        // Sprawdzenie, czy istnieje więcej niż jedna dominanta
        foreach ($dominanta as $key => $value) {
            if ($value === $maxOccurrences && $key > $pierwszaDominanta) {
                $pierwszaDominanta = $key;
            }
        }

        // Wyświetlenie wyników dla danego numeru EAN
        echo "<tr>";
        echo "<td>$ean</td>";
        echo "<td>" . number_format($sredniaCenaProduktow, 2, '.', '') . "</td>";
        echo "<td>" . number_format($medianaFiltr, 2, '.', '') . "</td>";
        echo "<td>" . number_format($pierwszaDominanta, 2, '.', '') . "</td>";
        echo "<td>$liczbaProduktowFiltr</td>";
        echo "</tr>";

        sqlsrv_free_stmt($wynik);
    }

    echo "</table>";
} else {
    echo "Brak unikalnych numerów EAN w bazie danych.";
}

sqlsrv_free_stmt($wynik_eany);
sqlsrv_close($conn);
?>
</body>
</html>
