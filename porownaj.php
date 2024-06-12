<?php
require_once("config.php");

// Zapytanie SQL
$sql = "
SELECT 
    t.tw_id,
    t.tw_GT,
    p.plu_kod,
    t.tw_nazwa,
    t.tw_VAT,
    t.tw_cena_sprz,
    t.tw_c_zak,
    t.tw_war_zak,
    t.tw_pamp,
    ppmi.tk_ppmi,
    ppmo.tk_ppmo,
    ko.tk_srednia_cena AS srednia_cena_konkurencja
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
WHERE 
    ppmi.tk_ppmi IS NOT NULL
    OR ppmo.tk_ppmo IS NOT NULL;
";

// Wykonanie zapytania
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Wyświetlenie wyników
echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>GT</th>
        <th>PLU</th>
        <th>Nazwa</th>
        <th>VAT</th>
        <th>Cena Sprzedaży</th>
        <th>Cena Zakupu</th>
        <th>Wartość Zakupu</th>
        <th>Pamp</th>
        <th>Cena PPMI</th>
        <th>Cena PPMO</th>
        <th>Średnia Cena Konkurencja</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['tw_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_GT']) . "</td>";
    echo "<td>" . htmlspecialchars($row['plu_kod']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_nazwa']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_VAT']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_cena_sprz']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_c_zak']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_war_zak']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_pamp']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tk_ppmi']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tk_ppmo']) . "</td>";
    echo "<td>" . htmlspecialchars($row['srednia_cena_konkurencja']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Zamknięcie połączenia
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
