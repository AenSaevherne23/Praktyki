<?php
require_once("config.php");

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
    ppmi.tk_ppmi IS NOT NULL
    OR ppmo.tk_ppmo IS NOT NULL 
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
        <th>Dominanta</th>
        <th>Ilość Wystąpień</th>
        <th>GT Marża</th>
        <th>Minimalna Kwota</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $vat = (float)$row['tw_VAT'] / 100;
    $marza = (float)$row['gt_marza'] / 100;
    $cena_zakupu_netto = (float)$row['tw_c_zak'];
    
    // Sprawdzenie czy cena zakupu wynosi 0 i zastąpienie jej wartością pamp
    if ($cena_zakupu_netto == 0) {
        $cena_zakupu_netto = (float)$row['tw_pamp'];
    }
    
    // Obliczenie minimalnej kwoty
    $minimalna_kwota = ($cena_zakupu_netto * (1 + $vat)) / (1 - $marza);
    
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
    echo "<td>" . ($row['tk_dominanta'] == 0 ? 'BRAK DANYCH' : number_format((float)$row['tk_dominanta'], 2, '.', '')) . "</td>";
    echo "<td>" . htmlspecialchars($row['tk_ilosc_wystapien'] == 0 ? 'BRAK DANYCH' : $row['tk_ilosc_wystapien']) . "</td>";
    echo "<td>" . htmlspecialchars($row['gt_marza']) . "</td>";
    echo "<td>" . number_format($minimalna_kwota, 2, '.', '') . "</td>";
    echo "</tr>";
}

echo "</table>";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
