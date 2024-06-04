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

        $sumaOdchylenProduktow = 0;
        foreach ($cenyProduktow as $cena) {
            $sumaOdchylenProduktow += pow($cena - $medianaProduktow, 2);
        }
        $odchylenieStandardoweProduktow = sqrt($sumaOdchylenProduktow / $liczbaCenProduktow);
        
        $cenyFiltrProduktow = array_filter($cenyProduktow, function($cena) use ($medianaProduktow, $odchylenieStandardoweProduktow) {
            return $cena <= $medianaProduktow + 2.3 * $odchylenieStandardoweProduktow;
        });
        
        $sumaCenProduktow = array_sum($cenyFiltrProduktow);
        $liczbaProduktowFiltr = count($cenyFiltrProduktow);
        $sredniaCenaProduktow = $liczbaProduktowFiltr ? $sumaCenProduktow / $liczbaProduktowFiltr : 0;
        
        sort($cenyFiltrProduktow);
        $medianaFiltrProduktow = $liczbaProduktowFiltr % 2 == 0 ? ($cenyFiltrProduktow[$liczbaProduktowFiltr / 2 - 1] + $cenyFiltrProduktow[$liczbaProduktowFiltr / 2]) / 2 : $cenyFiltrProduktow[($liczbaProduktowFiltr - 1) / 2];
        
        $dominantaProduktow = array_count_values(array_map('strval', $cenyFiltrProduktow));
        $maxOccurrencesProduktow = max($dominantaProduktow);
        $pierwszaDominantaProduktow = array_search($maxOccurrencesProduktow, $dominantaProduktow);

        foreach ($dominantaProduktow as $key => $value) {
            if ($value === $maxOccurrencesProduktow && $key > $pierwszaDominantaProduktow) {
                $pierwszaDominantaProduktow = $key;
            }
        }

        // Wstawianie wyników do bazy danych
        $query = "INSERT INTO dbo.tw_konkurencja_obliczenia (
                    tk_plu, tk_ilosc_wystapien, tk_srednia_cena, tk_mediana, tk_dominanta
                  ) VALUES (
                    ?, ?, ?, ?, ?
                  )";
        $params = array($ean, $liczbaProduktowFiltr, $sredniaCenaProduktow, $medianaFiltrProduktow, $pierwszaDominantaProduktow);
        $insertResult = sqlsrv_query($conn, $query, $params);

        if ($insertResult === false) {
            echo "Błąd podczas dodawania danych dla EAN $ean: " . print_r(sqlsrv_errors(), true);
        }
    }
    echo "Dane zostały pomyślnie dodane do bazy danych.";
} else {
    echo "Brak unikalnych numerów EAN w bazie danych.";
}

sqlsrv_free_stmt($wynik_eany);
sqlsrv_close($conn);
?>
