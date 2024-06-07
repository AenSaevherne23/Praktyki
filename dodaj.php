<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uruchom skrypt</title>
</head>

<body>
    <h1>Uruchom skrypt PHP</h1>
    <form action="" method="post">
        <input type="submit" value="Uruchom skrypt">
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_once("config.php");

        // Funkcja zaokrąglająca cenę do drugiego miejsca po przecinku
        function zaokraglij_do_drugiego_miejsca($cena)
        {
            return round($cena, 1) - 0.01;
        }

        // Zapytanie SQL dla unikalnych numerów EAN z tabeli, ograniczone do 500 rekordów
        $zapytanie_eany = "SELECT DISTINCT [tk_plu] 
                           FROM [leclerc].[dbo].[tw_konkurencja] 
                           GROUP BY [tk_plu] 
                           HAVING COUNT(*) > 1";
        $wynik_eany = sqlsrv_query($conn, $zapytanie_eany);

        if ($wynik_eany === false) {
            echo "Błąd wykonania zapytania: ";
            die(print_r(sqlsrv_errors(), true));
        }

        // Zbieranie EAN-ów do tablicy
        $eany = array();
        if (sqlsrv_has_rows($wynik_eany)) {
            while ($wiersz_ean = sqlsrv_fetch_array($wynik_eany, SQLSRV_FETCH_ASSOC)) {
                $eany[] = $wiersz_ean["tk_plu"];
            }
        }

        // Sprawdzenie, czy tablica nie jest pusta
        if (count($eany) > 0) {
            // Przygotowanie listy EAN-ów jako stringa do użycia w zapytaniu SQL
            $eany_string = implode("', '", $eany);

            // Zapytanie SQL dla wszystkich wybranych numerów EAN
            $zapytanie = "SELECT [tk_id], [tk_data], [tk_siec], [tk_plu], [tk_cena] 
                          FROM [leclerc].[dbo].[tw_konkurencja] 
                          WHERE [tk_plu] IN ('$eany_string')";
            $wynik = sqlsrv_query($conn, $zapytanie);

            if ($wynik === false) {
                echo "Błąd wykonania zapytania: ";
                die(print_r(sqlsrv_errors(), true));
            }

            // Przetwarzanie wyników
            $produkty = [];
            if (sqlsrv_has_rows($wynik)) {
                while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
                    $ean = $wiersz["tk_plu"];
                    $produkty[$ean][] = zaokraglij_do_drugiego_miejsca($wiersz["tk_cena"]);
                }
            }

            foreach ($produkty as $ean => $cenyProduktow) {
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
                    $cenyFiltr = array_filter($cenyProduktow, function ($cena) use ($mediana, $odchylenieStandardowe) {
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

                // Zaokrąglanie wartości do dwóch miejsc po przecinku
                $sredniaCenaProduktow = round($sredniaCenaProduktow, 2);
                $medianaFiltr = round($medianaFiltr, 2);
                $pierwszaDominanta = round($pierwszaDominanta, 2);

                // Sprawdzanie, czy istnieją już obliczenia dla danego EAN
                $checkQuery = "SELECT tk_srednia_cena, tk_mediana, tk_dominanta 
                               FROM dbo.tw_konkurencja_obliczenia 
                               WHERE tk_plu = ?";
                $checkParams = array($ean);
                $checkResult = sqlsrv_query($conn, $checkQuery, $checkParams);

                if ($checkResult === false) {
                    echo "Błąd podczas sprawdzania istniejących danych dla EAN $ean: " . print_r(sqlsrv_errors(), true);
                    continue;
                }

                if (sqlsrv_has_rows($checkResult)) {
                    $existingRow = sqlsrv_fetch_array($checkResult, SQLSRV_FETCH_ASSOC);

                    // Sprawdzanie, czy wartości są różne
                    if ($existingRow['tk_srednia_cena'] != $sredniaCenaProduktow || $existingRow['tk_mediana'] != $medianaFiltr || $existingRow['tk_dominanta'] != $pierwszaDominanta) {
                        // Aktualizacja rekordu
                        $updateQuery = "UPDATE dbo.tw_konkurencja_obliczenia 
                                        SET tk_ilosc_wystapien = ?, tk_srednia_cena = ?, tk_mediana = ?, tk_dominanta = ?, tk_zaktualizowano = GETDATE() 
                                        WHERE tk_plu = ?";
                        $updateParams = array($liczbaProduktowFiltr, $sredniaCenaProduktow, $medianaFiltr, $pierwszaDominanta, $ean);
                        $updateResult = sqlsrv_query($conn, $updateQuery, $updateParams);

                        if ($updateResult === false) {
                            echo "Błąd podczas aktualizacji danych dla EAN $ean: " . print_r(sqlsrv_errors(), true);
                        }
                    }
                } else {
                    // Wstawianie nowych wyników do bazy danych
                    $insertQuery = "INSERT INTO dbo.tw_konkurencja_obliczenia (
                                        tk_plu, tk_ilosc_wystapien, tk_srednia_cena, tk_mediana, tk_dominanta, tk_zaktualizowano
                                        ) VALUES (
                                        ?, ?, ?, ?, ?, GETDATE()
                                        )";
                    $insertParams = array($ean, $liczbaProduktowFiltr, $sredniaCenaProduktow, $medianaFiltr, $pierwszaDominanta);
                    $insertResult = sqlsrv_query($conn, $insertQuery, $insertParams);

                    if ($insertResult === false) {
                        echo "Błąd podczas dodawania danych dla EAN $ean: " . print_r(sqlsrv_errors(), true);
                    }
                }
            }
        } else {
            echo "Brak rekordów do przetworzenia.";
        }
    }
    ?>
</body>
</html>
