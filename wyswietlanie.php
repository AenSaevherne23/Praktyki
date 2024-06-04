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
            $tk_cena = round($wiersz["tk_cena"], 1) - 0.01;
            $cenyProduktow[] = $tk_cena;
        }

        $liczbaCenProduktow = count($cenyProduktow);
        $medianaProduktow = $liczbaCenProduktow % 2 == 0 ? ($cenyProduktow[$liczbaCenProduktow / 2 - 1] + $cenyProduktow[$liczbaCenProduktow / 2]) / 2 : $cenyProduktow[($liczbaCenProduktow - 1) / 2];

        while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
            $tk_cena = round($wiersz["tk_cena"], 1) - 0.01;
            $cenyProduktow[] = $tk_cena;
        }
        
        $liczbaCenProduktow = count($cenyProduktow);
        $medianaProduktow = $liczbaCenProduktow % 2 == 0 ? ($cenyProduktow[$liczbaCenProduktow / 2 - 1] + $cenyProduktow[$liczbaCenProduktow / 2]) / 2 : $cenyProduktow[($liczbaCenProduktow - 1) / 2];
        
        $sumaOdchylenProduktow = 0;
        foreach ($cenyProduktow as $cena) {
            $sumaOdchylenProduktow += pow($cena - $medianaProduktow, 2);
        }
        $odchylenieStandardoweProduktow = sqrt($sumaOdchylenProduktow / $liczbaCenProduktow);
        
        $cenyFiltrProduktow = array_filter($cenyProduktow, function($cena) use ($medianaProduktow, $odchylenieStandardoweProduktow) {
            return $cena <= $medianaProduktow + 2.2 * $odchylenieStandardoweProduktow;
        });
        
        $sumaCenProduktow = array_sum($cenyFiltrProduktow);
        $liczbaProduktowFiltr = count($cenyFiltrProduktow);
        $sredniaCenaProduktow = $liczbaProduktowFiltr ? $sumaCenProduktow / $liczbaProduktowFiltr : 0;
        
        sort($cenyFiltrProduktow);
        $medianaFiltrProduktow = $liczbaProduktowFiltr % 2 == 0 ? ($cenyFiltrProduktow[$liczbaProduktowFiltr / 2 - 1] + $cenyFiltrProduktow[$liczbaProduktowFiltr / 2]) / 2 : $cenyFiltrProduktow[($liczbaProduktowFiltr - 1) / 2];
        
      // Obliczanie dominanty z przefiltrowanych cen
$dominantaProduktow = array_count_values(array_map('strval', $cenyFiltrProduktow));
$maxOccurrencesProduktow = max($dominantaProduktow);
$pierwszaDominantaProduktow = array_search($maxOccurrencesProduktow, $dominantaProduktow);

// Sprawdzenie, czy istnieje więcej niż jedna dominanta
foreach ($dominantaProduktow as $key => $value) {
    if ($value === $maxOccurrencesProduktow && $key > $pierwszaDominantaProduktow) {
        $pierwszaDominantaProduktow = $key;
    }
}

        // Wyświetlenie wyników dla danego numeru EAN
        echo "<tr>";
        echo "<td>$ean</td>";
        echo "<td>" . number_format($sredniaCenaProduktow, 2, '.', '') . "</td>";
        echo "<td>" . number_format($medianaFiltrProduktow, 2, '.', '') . "</td>";
        if (isset($pierwszaDominantaProduktow)) {
        echo "<td>" . number_format($pierwszaDominantaProduktow, 2, '.', '') . "</td>";
        } else {
        echo "<td>Nie znaleziono dominanty</td>";
        }
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
