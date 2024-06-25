<?php
require_once("config.php");

//funkcje
function oblicz_ocs($cs_domyslna, $cena_min, $dominanta, $srednia_cena_konkurencja, $mediana, $cena_max, $ilosc_wys, &$komunikat, $czb, $ilosc_wys_min) {
    if ($ilosc_wys == 0)
    {
        $ocs = $cs_domyslna * 1.1;
        $komunikat = "OCS policzone jako 110% domyślnej ceny sprzedaży";
    } 
    elseif ($ilosc_wys >= 1 && $ilosc_wys <= 3) {
        if ($cs_domyslna > $cena_min && $cs_domyslna < $cena_max)
        {
            $ocs = $cs_domyslna;
            $komunikat = "OCS policzone jako cena domyślna. Mieści się między min a max <1,3>";
        }
        elseif($cs_domyslna <= $cena_min)
        {
            $ocs = $cena_min;
            $komunikat = "OCS policzone jako cena_min <1,3>";
        }
        elseif($cs_domyslna >= $cena_max)
        {
            if($czb <= $cena_max)
            {
                $ocs = $cena_max;
                $komunikat = "OCS policzone jako cena max <1,3>";
            }
            else
            {
                $ocs = $czb;
                $komunikat = "OCS policzone jako czb <1,3>";
            }
        }
    }
    else {
        if ($dominanta !== null)
        {
            if($cs_domyslna <= $cena_min)
            {
                $prop_minimalnej = $ilosc_wys_min / $ilosc_wys;
                if($prop_minimalnej >= 0.3)
                {
                    $ocs = $cena_min;
                    $komunikat = "OCS policzone jako cena_min <4,∞> (dominanta)";
                }
                elseif($srednia_cena_konkurencja > $dominanta){
                    $ocs = $dominanta;
                    $komunikat = "OCS policzone jako dominanta <4,∞> (dominanta)";
                }
                else{
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                }  
            }
            elseif ($cs_domyslna <= $dominanta)
            {
                $ocs = $dominanta;
                $komunikat = "OCS policzone jako dominanta <4,∞> (dominanta)";
            }
            else
            {
                if($cs_domyslna <= $srednia_cena_konkurencja)
                {
                    if($srednia_cena_konkurencja <= $mediana)
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                    }
                    elseif($cs_domyslna >= $mediana)
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                    }
                    elseif($czb>=$mediana)
                    {
                        $ocs = $czb;
                        $komunikat = "OCS policzone jako CZB <4,∞> (dominanta)";
                    }
                    else
                    {
                        $ocs = $mediana;
                        $komunikat = "OCS policzone jako mediana <4,∞> (dominanta)";
                    }
                }
                else 
                {
                    //Czy o to chodziło?
                    if($czb <= $dominanta)
                    {
                        $ocs = $dominanta;
                        $komunikat = "OCS policzone jako dominanta (czb) <4,∞> (dominanta)";

                    }
                    elseif($czb <= $srednia_cena_konkurencja)
                    {
                        if($srednia_cena_konkurencja <= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (dominanta)";
                        }
                        elseif($czb >= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (dominanta)";
                        }
                        else
                        {
                            $ocs = $mediana;
                            $komunikat = "OCS policzone jako mediana (czb) <4,∞> (dominanta)";
                        }
                    }
                    elseif($czb <= $cena_max)
                    {
                        $ocs = $cena_max;
                        $komunikat = "OCS policzone jako cena_max (czb) <4,∞> (dominanta)";
                    }
                    else
                    {
                        $ocs = $czb;
                        $komunikat = "OCS policzone jako czb (czb) <4,∞> (dominanta)";
                    }
                }
            }
        }
        else //Nie ma dominanty
        {
            if($cs_domyslna <= $cena_min)
            {
                if($ilosc_wys_min > 1){
                    $ocs = $cena_min;
                    $komunikat = "OCS policzone jako cena_min <4,∞> (bez dominanty)";
                }
                elseif($mediana > $srednia_cena_konkurencja){
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                }
                else{
                    $ocs = $mediana;
                    $komunikat = "OCS policzone jako mediana <4,∞> (bez dominanty)";
                }  
            }
            elseif($cs_domyslna <= $srednia_cena_konkurencja)
            {
                if($cs_domyslna <= $mediana)
                {
                    if($srednia_cena_konkurencja >= $mediana)
                    {
                        $ocs = $mediana;
                        $komunikat = "OCS policzone jako mediana <4,∞> (bez dominanty)";
                    }
                    else
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                    }
                }
                else
                {
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                }
            }
            else
            {
                //Czy o to chodziło? v2
                if($czb <= $srednia_cena_konkurencja)
                    {
                        if($srednia_cena_konkurencja <= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (bez dominanty)";
                        }
                        elseif($czb >= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (bez dominanty)";
                        }
                        else
                        {
                            $ocs = $mediana;
                            $komunikat = "OCS policzone jako mediana (czb) <4,∞> (bez dominanty)";
                        }
                    }
                elseif($czb <= $cena_max)
                {
                    $ocs = $cena_max;
                    $komunikat = "OCS policzone jako cena_max (czb) <4,∞> (bez dominanty)";
                }
                else
                {
                    $ocs = $czb;
                    $komunikat = "OCS policzone jako czb (czb) <4,∞> (bez dominanty)";
                }
            }
        }
    }
    
    return $ocs;
} 

$sql = "
SELECT 
    p.plu_kod,
    t.tw_VAT,
    t.tw_cena_sprz,
    t.tw_c_zak,
    t.tw_pamp,
    ppmi.tk_ppmi,
    ppmo.tk_ppmo,
    ko.tk_srednia_cena AS srednia_cena_konkurencja,
    ko.tk_mediana,
    ko.tk_cena_max,
    ko.tk_cena_min,
    ko.tk_dominanta,
    ko.tk_ilosc_wystapien,
    ko.tk_ilosc_wys_min,
    sg.gt_marza
FROM 
    [leclerc].[dbo].[tw__towar] AS t
LEFT JOIN 
    [leclerc].[dbo].[tw_plu] AS p ON p.plu_twid = t.tw_id
LEFT JOIN 
    [leclerc].[dbo].[tw_towar_ppmi] AS ppmi ON ppmi.tk_plu = p.plu_kod
LEFT JOIN 
    [leclerc].[dbo].[tw_konkurencja_obliczenia] AS ko ON ko.tk_plu = p.plu_kod
LEFT JOIN 
    [leclerc].[dbo].[tw_towar_ppmo] AS ppmo ON ppmo.tk_plu = p.plu_kod
CROSS APPLY
    (
        SELECT TOP 1 sg.gt_marza 
        FROM [leclerc].[dbo].[sl_grupa_tw] AS sg 
        WHERE LEFT(t.tw_GT, 3) = LEFT(sg.gt_nr, 3)
    ) AS sg
WHERE 
    ppmi.tk_ppmi IS NOT NULL OR ppmo.tk_ppmo IS NOT NULL
ORDER BY t.tw_cena_sprz DESC;
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333333;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #666666;
            color: white;
            text-align: center;
            position: sticky; /* Ustawienie przylepania */
            top: 0; /* Ustawienie odległości od góry strony */
            z-index: 1; /* Z-index, aby przylegał nad innymi elementami */
        }
        tr:nth-child(even) {
            background-color: #cccccc;
        }
        tr:nth-child(odd) {
            background-color: #eeeeee;
        }
      </style>";

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
    if (!empty($row['tk_ppmi'])) 
    {
        $ppmi = $row['tk_ppmi'];
    }
    else
    {
        $ppmi = null;
    }

    if (!empty($row['tk_ppmo'])) 
    {
        $ppmo = $row['tk_ppmo'];
    }
    else
    {
        $ppmo = null;
    }

    
    switch (true) {
        case ($ppmi !== null && $ppmi <= $cena_min && $czb < $ppmi):
            if (($czb *(1-$dop_marza_uj)) > $ppmi) 
            {
                $ocs = $czb;
                $komunikat = "OCS policzone jako CZB (ppmi)"; //nie wystąpi przez wcześniejszy warunek
            }
            else
            {
                $ocs = $ppmi;
                $komunikat = "OCS policzone jako PPMI";
            }
           
            break;
        case ($ppmo !== null && $ppmo < $dominanta && $ppmo < $srednia_cena_konkurencja && $ppmo < $mediana):
            if (($czb *(1-$dop_marza_uj)) > $ppmo) 
            {
                $ocs = $czb;
                $komunikat = "OCS policzone jako CZB (ppmo)";
            }
            else
            {
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
 

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['plu_kod']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_VAT']) . "</td>";
    echo "<td>" . number_format((float)$row['tw_cena_sprz'], 2, '.', '') . "</td>";
    echo "<td>" . number_format((float)$row['tw_c_zak'], 2, '.', '') . "</td>";
    echo "<td>" . number_format((float)$row['tw_pamp'], 2, '.', '') . "</td>";
    echo "<td>" . ($row['tk_ppmi'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_ppmi'], 2, '.', '')) . "</td>";
    echo "<td>" . ($row['tk_ppmo'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_ppmo'], 2, '.', '')) . "</td>";
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

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
