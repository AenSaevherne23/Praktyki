<?php
require_once("config.php");

// Zapytanie SQL
$sql = "
SELECT 
    sprz.ts_twid,
    plu.plu_kod,
    SUM(sprz.ts_ilosc) AS suma_ilosci,
    SUM(sprz.ts_wartsprzbrut) AS suma_brutto
FROM [leclerc].[dbo].[tw_sprzedaz] AS sprz
LEFT JOIN tw_plu AS plu
ON plu.plu_twid = sprz.ts_twid
GROUP BY sprz.ts_twid, plu.plu_kod
HAVING SUM(sprz.ts_ilosc) >= 1
AND plu.plu_kod IS NOT NULL;
";

// Wykonanie zapytania
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Przygotowanie danych do wstawienia
$insertData = array();
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $tw_plu = $row['plu_kod'];
    $suma_ilosci = $row['suma_ilosci'];
    $suma_brutto = $row['suma_brutto'];
    $srednia_brutto = $suma_brutto / $suma_ilosci;

    $insertData[] = array($tw_plu, $suma_ilosci, $srednia_brutto);
}

// Zamknięcie zapytania
sqlsrv_free_stmt($stmt);

// Wstawianie danych do nowej tabeli
$insertSql = "INSERT INTO dbo.tw_srednia_sprzedaz (tw_plu, tw_suma_ilosci, tw_srednia_brutto) VALUES (?, ?, ?)";
foreach ($insertData as $data) {
    $params = array($data[0], $data[1], $data[2]);
    $insertStmt = sqlsrv_query($conn, $insertSql, $params);
    if ($insertStmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

// Zamknięcie połączenia z bazą danych
sqlsrv_close($conn);

echo "Dane zostały pomyślnie wstawione do tabeli dbo.tw_srednia_sprzedaz.";
?>
