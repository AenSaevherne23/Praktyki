<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uruchom skrypt</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <main>
            <div class="container">
                <h1>Uruchom skrypt PHP</h1>
                <form action="" method="post">
                    <button type="submit">Uruchom skrypt</button>
                </form>
            </div>
    </main>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_once("config.php");

        // Funkcja zaokraglająca cenę do drugiego miejsca po przecinku
        function zaokraglij_do_drugiego_miejsca($cena)
        {
            return round($cena, 1) - 0.01;
        }

        // Zapytanie SQL dla unikalnych numerów EAN z tabeli
        $zapytanie_eany = "SELECT DISTINCT [tk_plu] 
                           FROM [leclerc].[dbo].[tw_konkurencja_nowa] 
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
            $zapytanie = "SELECT [tk_id], [tk_siec], [tk_plu], [tk_cena] 
                          FROM [leclerc].[dbo].[tw_konkurencja_nowa] 
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

            // Pobranie istniejących obliczeń dla wszystkich EAN-ów
            $zapytanie_obliczenia = "SELECT tk_plu, tk_srednia_cena, tk_mediana, tk_dominanta, tk_cena_max, tk_cena_min, tk_ilosc_wys_min 
                                     FROM dbo.tw_konkurencja_obliczenia 
                                     WHERE tk_plu IN ('$eany_string')";
            $wynik_obliczenia = sqlsrv_query($conn, $zapytanie_obliczenia);

            if ($wynik_obliczenia === false) {
                echo "Błąd wykonania zapytania: ";
                die(print_r(sqlsrv_errors(), true));
            }

            // Zbieranie istniejących obliczeń do tablicy
            $obliczenia = [];
            if (sqlsrv_has_rows($wynik_obliczenia)) {
                while ($wiersz_obliczenia = sqlsrv_fetch_array($wynik_obliczenia, SQLSRV_FETCH_ASSOC)) {
                    $obliczenia[$wiersz_obliczenia["tk_plu"]] = $wiersz_obliczenia;
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
                $dominanty = array_keys($dominanta, $maxOccurrences);
                $pierwszaDominanta = null;

                // Sprawdzenie, czy są wartości z największą ilością wystąpień
                if ($maxOccurrences > 1) {
                    if (count($dominanty) > 1) {
                        $najblizszaDominanta = null;
                        $minimalnaRoznica = PHP_INT_MAX;
                        foreach ($dominanty as $dominanta) {
                            $roznica = abs($dominanta - $medianaFiltr);
                            if ($roznica < $minimalnaRoznica) {
                                $minimalnaRoznica = $roznica;
                                $najblizszaDominanta = $dominanta;
                            }
                        }
                        $pierwszaDominanta = $najblizszaDominanta;
                    } else {
                        $pierwszaDominanta = $dominanty[0];
                    }
                }

                // Obliczenie minimalnej i maksymalnej ceny
                $cenaMin = min($cenyProduktow);
                $cenaMax = max($cenyProduktow);

                // Obliczenie ilości wystąpień minimalnej ceny
                $iloscWysMin = count(array_filter($cenyProduktow, function ($cena) use ($cenaMin) {
                    return $cena == $cenaMin;
                }));

                // Zaokrąglanie wartości do dwóch miejsc po przecinku
                $sredniaCenaProduktow = round($sredniaCenaProduktow, 2);
                $medianaFiltr = round($medianaFiltr, 2);
                $pierwszaDominanta = is_null($pierwszaDominanta) ? null : round($pierwszaDominanta, 2);
                $cenaMin = round($cenaMin, 2);
                $cenaMax = round($cenaMax, 2);

                // Sprawdzenie, czy istnieją już obliczenia dla danego EAN
                if (isset($obliczenia[$ean])) {
                    $existingRow = $obliczenia[$ean];

                    // Sprawdzanie, czy wartości są różne
                    if ($existingRow['tk_srednia_cena'] != $sredniaCenaProduktow || $existingRow['tk_mediana'] != $medianaFiltr || $existingRow['tk_dominanta'] != $pierwszaDominanta || $existingRow['tk_cena_min'] != $cenaMin || $existingRow['tk_cena_max'] != $cenaMax || $existingRow['tk_ilosc_wys_min'] != $iloscWysMin) {
                        // Aktualizacja rekordu
                        $updateQuery = "UPDATE dbo.tw_konkurencja_obliczenia
                                        SET tk_ilosc_wystapien = ?, tk_srednia_cena = ?, tk_mediana = ?, tk_dominanta = ?, tk_cena_max = ?, tk_cena_min = ?, tk_ilosc_wys_min = ?, tk_zaktualizowano = GETDATE() 
                                        WHERE tk_plu = ?";
                        $updateParams = array($liczbaProduktowFiltr, $sredniaCenaProduktow, $medianaFiltr, $pierwszaDominanta, $cenaMax, $cenaMin, $iloscWysMin, $ean);
                        $updateResult = sqlsrv_query($conn, $updateQuery, $updateParams);

                        if ($updateResult === false) {
                            echo "Błąd podczas aktualizacji danych dla EAN $ean: " . print_r(sqlsrv_errors(), true);
                        }
                    }
                } else {
                    // Wstawianie nowych wyników do bazy danych
                    $insertQuery = "INSERT INTO dbo.tw_konkurencja_obliczenia (
                                        tk_plu, tk_ilosc_wystapien, tk_srednia_cena, tk_mediana, tk_dominanta, tk_cena_max, tk_cena_min, tk_ilosc_wys_min, tk_zaktualizowano
                                        ) VALUES (
                                        ?, ?, ?, ?, ?, ?, ?, ?, GETDATE()
                                        )";
                    $insertParams = array($ean, $liczbaProduktowFiltr, $sredniaCenaProduktow, $medianaFiltr, $pierwszaDominanta, $cenaMax, $cenaMin, $iloscWysMin);
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
