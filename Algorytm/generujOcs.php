<?php
// Zdefiniowanie funkcji generującej tablicę OCS i PLU
function generujOcs($stmt) {
    // Inicjalizacja tablicy na dane OCS i PLU
    $ocs_array = array();

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vat = (float)$row['tw_VAT'] / 100;
        $marza = (float)$row['gt_marza'] / 100;
        $cena_zakupu_netto = (float)$row['tw_c_zak'];
        
        // Sprawdzenie czy cena zakupu wynosi 0 i zastąpienie jej wartością pamp
        if ($cena_zakupu_netto < 0.1) {
            $cena_zakupu_netto = (float)$row['tw_pamp'];
        }
        
        // Obliczenie domyślnej ceny sprzedaży
        $cs_domyslna = ($cena_zakupu_netto * (1 + $vat)) / (1 - $marza);
        
        // Zmienne do obliczenia OCS
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
        $dop_marza_uj = 0.05; //dopuszczalna marża ujemna
        
        // Sprawdzenie ppmi/ppmo i dodanie ich do zmiennych
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

        // Logika obliczania OCS
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

        // Dodanie danych do tablicy OCS i PLU
        $ocs_array[$row['plu_kod']] = $ocs;
    }

    // Zwolnienie wyniku
    sqlsrv_free_stmt($stmt);

    // Zwrócenie tablicy z OCS i PLU
    return $ocs_array;
}
?>
