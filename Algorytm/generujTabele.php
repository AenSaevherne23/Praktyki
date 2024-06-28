<?php
// Zdefiniowanie funkcji generującej tabelę HTML
function generujTabele($stmt) {
    echo "<table>";
    echo "<tr>
            <th>PLU</th>
            <th>VAT</th>
            <th>Aktualna Cena Sprzedaży</th>
            <th>Cena Zakupu NETTO</th>
            <th>Pamp NETTO</th>
            <th>Cena PPMI</th>
            <th>Cena PPMO</th>
            <th>Średnia Cena Konkurencja</th>
            <th>Mediana</th>
            <th>Cena Max</th>
            <th>Cena Min</th>
            <th>Ilość wystąpień minimalnej</th>
            <th>Dominanta</th>
            <th>Ilość Wystąpień</th>
            <th>GT Marża</th>
            <th>Domyślna cena sprzedaży</th>
            <th>OCS</th>
            <th>Komunikat</th>
          </tr>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vat = (float)$row['tw_VAT'] / 100;
        $marza = (float)$row['gt_marza'] / 100;
        $cena_zakupu_netto = (float)$row['tw_c_zak'];
        
        // Sprawdzenie czy cena zakupu wynosi 0 i zastąpienie jej wartością pamp
        if ($cena_zakupu_netto < 0.1) {
            $cena_zakupu_netto = (float)$row['tw_pamp'];
        }
        
        // Obliczenie minimalnej kwoty
        $cs_domyslna = ($cena_zakupu_netto * (1 + $vat)) / (1 - $marza);
        
        // Zmienne
        $ocs = null;
        $komunikat = "";
        $aktualna_cena_sprzedazy = $row['tw_cena_sprz'];
        $srednia_cena_konkurencja = $row['srednia_cena_konkurencja'];
        $mediana = $row['tk_mediana']; 
        $cena_max = $row['tk_cena_max'];
        $cena_min = $row['tk_cena_min'];
        $dominanta = $row['tk_dominanta'];
        $ilosc_wys = $row['tk_ilosc_wystapien'];
        $czb = $cena_zakupu_netto * (1 + $vat);
        $ilosc_wys_min = $row['tk_ilosc_wys_min'];
        $dop_marza_uj = 0.05; //dopuszczalna marza ujemna
        
        //sprawdzenie ppmi/ppmo i dodanie ich do zmiennych
        if (!empty($row['tpi_cena'])) {
            $ppmi = $row['tpi_cena'];
        } else {
            $ppmi = null;
        }

        if (!empty($row['tpo_cena'])) {
            $ppmo = $row['tpo_cena'];
        } else {
            $ppmo = null;
        }

        switch (true) {
            case ($ppmi !== null && $ppmi <= $cena_min && $czb < $ppmi):
                if (($czb *(1-$dop_marza_uj)) > $ppmi) {
                    $ocs = $czb;
                    $komunikat = "OCS policzone jako CZB (ppmi)";
                } else {
                    $ocs = $ppmi;
                    $komunikat = "OCS policzone jako PPMI";
                }
                break;
            case ($ppmo !== null && $ppmo < $dominanta && $ppmo < $srednia_cena_konkurencja && $ppmo < $mediana):
                if (($czb *(1-$dop_marza_uj)) > $ppmo) {
                    $ocs = $czb;
                    $komunikat = "OCS policzone jako CZB (ppmo)";
                } else {
                    $ocs = $ppmo;
                    $komunikat = "OCS policzone jako PPMO";
                }
                break;
            default:
                $ocs = oblicz_ocs($cs_domyslna, $cena_min, $dominanta, $srednia_cena_konkurencja, $mediana, $cena_max, $ilosc_wys, $komunikat, $czb, $ilosc_wys_min);
        }    

        // Zaokrąglenie $ocs tak, aby część dziesiętna zawsze była 0.09
        $integerPart = floor($ocs); // Część całkowita liczby
        $decimalPart = $ocs - $integerPart; // Część dziesiętna liczby

        if ($decimalPart > 0) {
            $ocs = $integerPart + ceil($decimalPart / 0.1) * 0.1 - 0.01;
        } else {
            $ocs = $integerPart + 0.09;
        }
     
        // Wypisanie wiersza tabeli
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['plu_kod']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tw_VAT']) . "</td>";
        echo "<td>" . number_format((float)$row['tw_cena_sprz'], 2, '.', '') . "</td>";
        echo "<td>" . number_format((float)$row['tw_c_zak'], 2, '.', '') . "</td>";
        echo "<td>" . number_format((float)$row['tw_pamp'], 2, '.', '') . "</td>";
        echo "<td>" . ($row['tpi_cena'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tpi_cena'], 2, '.', '')) . "</td>";
        echo "<td>" . ($row['tpo_cena'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tpo_cena'], 2, '.', '')) . "</td>";
        echo "<td>" . ($row['srednia_cena_konkurencja'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['srednia_cena_konkurencja'], 2, '.', '')) . "</td>";
        echo "<td>" . ($row['tk_mediana'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_mediana'], 2, '.', '')) . "</td>";
        echo "<td>" . ($row['tk_cena_max'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_cena_max'], 2, '.', '')) . "</td>";
        echo "<td>" . ($row['tk_cena_min'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_cena_min'], 2, '.', '')) . "</td>";
        echo "<td>" . htmlspecialchars($row['tk_ilosc_wys_min'] == 0 ? 'BRAK DANYCH' : $row['tk_ilosc_wys_min']) . "</td>";
        echo "<td>" . ($row['tk_dominanta'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_dominanta'], 2, '.', '')) . "</td>";
        echo "<td>" . htmlspecialchars($row['tk_ilosc_wystapien'] == 0 ? 'BRAK DANYCH' : $row['tk_ilosc_wystapien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['gt_marza']) . "</td>";
        echo "<td>" . number_format($cs_domyslna, 2, '.', '') . "</td>";
        echo "<td>" . number_format($ocs, 2, '.', '') . "</td>";
        echo "<td>" . htmlspecialchars($komunikat) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Zwolnienie wyniku i zamknięcie połączenia
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>
