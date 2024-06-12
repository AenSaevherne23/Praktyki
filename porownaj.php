<?php
require_once("config.php");

// Parametry paginacji
$pageNumber = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = 100;
$offset = ($pageNumber - 1) * $rowsPerPage;

// Zapytanie SQL z paginacją
$sql = "
DECLARE @PageNumber AS INT = $pageNumber;
DECLARE @RowsPerPage AS INT = $rowsPerPage;

WITH PagedResults AS (
    SELECT 
        t.tw_id,
        t.tw_GT,
        LEFT(t.tw_GT, 3) AS gt_nr_3_cyfry,
        COALESCE(gt.gt_marza, 0) AS gt_marza,
        p.plu_kod,
        t.tw_nazwa,
        t.tw_VAT,
        t.tw_cena_sprz,
        t.tw_c_zak,
        t.tw_war_zak,
        t.tw_pamp,
        ppmi.tk_ppmi,
        ppmo.tk_ppmo,
        ko.tk_srednia_cena AS srednia_cena_konkurencja,
        ROW_NUMBER() OVER (ORDER BY t.tw_id) AS RowNum
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
    LEFT JOIN 
        (
            SELECT 
                LEFT(gt_nr, 3) AS gt_nr_3_cyfry,
                gt_marza
            FROM 
                [leclerc].[dbo].[sl_grupa_tw]
            WHERE 
                gt_poziom = 2
        ) AS gt ON gt.gt_nr_3_cyfry = LEFT(t.tw_GT, 3)
    WHERE 
        ppmi.tk_ppmi IS NOT NULL
        OR ppmo.tk_ppmo IS NOT NULL
)
SELECT *,
       CASE 
           WHEN tw_cena_sprz > tk_ppmi THEN 'Cena za wysoka'
           WHEN tw_cena_sprz < tk_ppmo THEN 'Cena za niska'
           ELSE 'Cena OK'
       END AS cena_status
FROM PagedResults
WHERE RowNum BETWEEN (@PageNumber - 1) * @RowsPerPage + 1 AND @PageNumber * @RowsPerPage;
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
        <th>GT Marża</th>
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
        <th>Status Ceny</th>
      </tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['tw_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tw_GT']) . "</td>";
    echo "<td>" . htmlspecialchars($row['gt_marza']) . "</td>";
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
    echo "<td>" . htmlspecialchars($row['cena_status']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Wyświetlenie linków paginacji
echo "<div>";
if ($pageNumber > 1) {
    echo "<a href=\"?page=" . ($pageNumber - 1) . "\">Poprzednia</a> ";
}
echo "<a href=\"?page=" . ($pageNumber + 1) . "\">Następna</a>";
echo "</div>";

// Zamknięcie połączenia
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
