<?php
require_once("config.php");
require_once("functions.php");

$sql = "
SELECT 
    p.plu_kod,
    t.tw_VAT,
    t.tw_cena_sprz,
    t.tw_c_zak,
    t.tw_pamp,
    ppmi.tpi_cena,
    ppmo.tpo_cena,
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
    [leclerc].[dbo].[tw_ppmi] AS ppmi ON ppmi.tpi_ean = p.plu_kod
LEFT JOIN 
    [leclerc].[dbo].[tw_konkurencja_obliczenia] AS ko ON ko.tk_plu = p.plu_kod
LEFT JOIN 
    [leclerc].[dbo].[tw_ppmo] AS ppmo ON ppmo.tpo_ean = p.plu_kod
CROSS APPLY
    (
        SELECT TOP 1 sg.gt_marza 
        FROM [leclerc].[dbo].[sl_grupa_tw] AS sg 
        WHERE LEFT(t.tw_GT, 3) = LEFT(sg.gt_nr, 3)
    ) AS sg
WHERE 
    ppmi.tpi_cena IS NOT NULL OR ppmo.tpo_cena IS NOT NULL
ORDER BY t.tw_cena_sprz DESC;
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>
