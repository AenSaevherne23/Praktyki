<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprawdź Produkt</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .included { color: lightgreen; }
        .excluded { color: red; }
    </style>
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
        require_once("config.php");

        function zaokraglij_do_drugiego_miejsca($cena) {
            return round($cena, 1) - 0.01;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST["ean"])) {
                $ean = htmlspecialchars($_POST["ean"]);
                echo "Podany nr EAN: " . $ean . "<br />";

                $zapytanie = "SELECT TOP (100) [tk_id], [tk_data], [tk_siec], [tk_plu], [tk_cena] 
                              FROM [leclerc].[dbo].[tw_konkurencja] 
                              WHERE [tk_plu] = ?";
                
                $params = array($ean);
                $wynik = sqlsrv_query($conn, $zapytanie, $params);

                if ($wynik === false) {
                    echo "Błąd wykonania zapytania: ";
                    die(print_r(sqlsrv_errors(), true));
                }

                $wszystkieCeny = array();
                $cenyZRodzajem = array();

                if (sqlsrv_has_rows($wynik)) {
                    while ($wiersz = sqlsrv_fetch_array($wynik, SQLSRV_FETCH_ASSOC)) {
                        $tk_id = $wiersz["tk_id"];
                        $tk_data = $wiersz["tk_data"] instanceof DateTime ? $wiersz["tk_data"]->format('Y-m-d') : $wiersz["tk_data"];
                        $tk_siec = $wiersz["tk_siec"];
                        $tk_plu = $wiersz["tk_plu"];
                        $tk_cena = number_format($wiersz["tk_cena"], 2, '.', '');

                        $zaokraglonaCena = zaokraglij_do_drugiego_miejsca($wiersz["tk_cena"]);
                        $wszystkieCeny[] = $zaokraglonaCena;
                        $cenyZRodzajem[] = [
                            "id" => $tk_id,
                            "data" => $tk_data,
                            "siec" => $tk_siec,
                            "ean" => $tk_plu,
                            "cena" => $zaokraglonaCena,
                            "wzieta" => true
                        ];
                    }

                    $czyFiltruj = true;

                    while ($czyFiltruj) {
                        sort($wszystkieCeny);
                        $liczbaCen = count($wszystkieCeny);
                        $mediana = 0;

                        if ($liczbaCen % 2 == 0) {
                            $mediana = ($wszystkieCeny[$liczbaCen / 2 - 1] + $wszystkieCeny[$liczbaCen / 2]) / 2;
                        } else {
                            $mediana = $wszystkieCeny[($liczbaCen - 1) / 2];
                        }

                        $sumaOdchylen = 0;
                        foreach ($wszystkieCeny as $cena) {
                            $sumaOdchylen += pow($cena - $mediana, 2);
                        }
                        $odchylenieStandardowe = sqrt($sumaOdchylen / $liczbaCen);

                        $cenyFiltr = array_filter($wszystkieCeny, function($cena) use ($mediana, $odchylenieStandardowe) {
                            return $cena >= $mediana - 2.3 * $odchylenieStandardowe && $cena <= $mediana + 2.3 * $odchylenieStandardowe;
                        });

                        if (count($cenyFiltr) == count($wszystkieCeny)) {
                            $czyFiltruj = false;
                        } else {
                            foreach ($cenyZRodzajem as &$cenaInfo) {
                                if (!in_array($cenaInfo["cena"], $cenyFiltr)) {
                                    $cenaInfo["wzieta"] = false;
                                }
                            }
                            $wszystkieCeny = $cenyFiltr;
                        }
                    }

                    $liczbaWszystkichCen = count($cenyZRodzajem);
                    $liczbaCenWzietych = count($wszystkieCeny);
                    $liczbaCenOdrzuconych = $liczbaWszystkichCen - $liczbaCenWzietych;

                    echo "<table border='1'>";
                    echo "<tr><th>ID</th><th>Data</th><th>Sieć</th><th>EAN</th><th>Cena</th><th>Status</th></tr>";
                    foreach ($cenyZRodzajem as $cenaInfo) {
                        $statusClass = $cenaInfo["wzieta"] ? "included" : "excluded";
                        $statusText = $cenaInfo["wzieta"] ? "Tak" : "Nie";
                        echo "<tr>
                                <td>{$cenaInfo['id']}</td>
                                <td>{$cenaInfo['data']}</td>
                                <td>{$cenaInfo['siec']}</td>
                                <td>{$cenaInfo['ean']}</td>
                                <td class='{$statusClass}'>" . number_format($cenaInfo['cena'], 2, '.', '') . "</td>
                                <td class='{$statusClass}'>{$statusText}</td>
                              </tr>";
                    }
                    echo "</table>";

                    $sumaCen = array_sum($wszystkieCeny);
                    $liczbaProduktow = count($wszystkieCeny);
                    $sredniaCena = $liczbaProduktow ? $sumaCen / $liczbaProduktow : 0;
                    echo "<div class='result'><span>Średnia cena:</span> <span class='average'>" . number_format($sredniaCena, 2, '.', '') . "</span></div>";

                    sort($wszystkieCeny);
                    $liczbaCenFiltr = count($wszystkieCeny);
                    $medianaFiltr = 0;

                    if ($liczbaCenFiltr % 2 == 0) {
                        $medianaFiltr = ($wszystkieCeny[$liczbaCenFiltr / 2 - 1] + $wszystkieCeny[$liczbaCenFiltr / 2]) / 2;
                    } else {
                        $medianaFiltr = $wszystkieCeny[($liczbaCenFiltr - 1) / 2];
                    }
                    echo "<div class='result'><span>Mediana:</span> <span class='median'>" . number_format($medianaFiltr, 2, '.', '') . "</span></div>";

                    $dominanta = array_count_values(array_map('strval', $wszystkieCeny));
                    $maxOccurrences = max($dominanta);
                    $pierwszaDominanta = array_search($maxOccurrences, $dominanta);

                    echo "<div class='result'><span>Dominanta:</span> <span class='mode'>" . number_format($pierwszaDominanta, 2, '.', '') . "</span></div>";
                    echo "<div class='result'><span>Liczba cen wziętych do obliczeń:</span> <span class='count'>" . $liczbaCenFiltr . "</span></div>";
                    echo "<div class='result'><span>Liczba wszystkich cen:</span> <span class='total'>" . $liczbaWszystkichCen . "</span></div>";
                    echo "<div class='result'><span>Liczba odrzuconych cen:</span> <span class='rejected'>" . $liczbaCenOdrzuconych . "</span></div>";
                } else {
                    echo "Brak wyników.";
                }

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
