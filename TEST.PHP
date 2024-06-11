<?php

function test($conn) { 
    require_once('C:/xampp/htdocs/class/excel.php');
    require_once('C:/xampp/htdocs/class/TAC.php');
    require_once('C:/xampp/htdocs/class/Kontrahenci.php');



    echo "<form method=\"post\" enctype=\"multipart/form-data\" target=\"_self\" id='form_wczytaj_z_pliku'>";
    echo "Wybierz plik:<input type=\"file\" id=\"file\" name=\"file\" style='margin-right: 2px' required>";
    echo "<button type='submit'>Wyśllij</button><br/>";
    echo "</form>";
    $i = 0;
    if (!empty($_FILES['file']['tmp_name'])) {
        //Nielsen
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext == 'xlsx' || $ext == 'xls') {
            //TAC centralny
            if  (strpos($_FILES['file']['name'], 'TAC')) {
                $raport = TAC::czytaj_TAC($_FILES['file']['tmp_name']);
                $GT = implode ("', '", array_unique($raport));
                $sql = "delete from tw_tac where tct_gt in ('$GT')";
                sqlsrv_query($conn, $sql);
                $i = 0;
                while ($i < floor(count($raport)/1000) + 1) {
                    $dane2 = array_slice ($raport, $i*1000, 1000, true);
                    $sql = "INSERT INTO tw_tac (tct_ean, tct_gt, tct_centralne) VALUES ";
                    foreach ($dane2 as $ean=>$gt) {
                        $sql .= " ('$ean', '$gt', 1), ";
                    }
                    $sql = rtrim($sql, ', ');
                    sqlsrv_query($conn, $sql);
                    $i++;
                }
            } elseif (strpos($_FILES['file']['name'], 'Nielsen')) {
                //Nielsen
                $raport = TAC::czytaj_Nielsen($_FILES['file']['tmp_name']);
                $sql = "delete from tw_nielsen";
                sqlsrv_query($conn, $sql);
                $i = 0;
                while ($i < floor(count($raport)/1000) + 1) {
                    $dane2 = array_slice ($raport, $i*1000, 1000, true);
                    $sql = "INSERT INTO tw_nielsen (tn_ean, tn_kategoria, tn_ilosc) VALUES ";
                    foreach ($dane2 as $key=>$val) {
                        $ean = substr($val[0],0,13);
                        $kategoria = $val[1];
                        $ilosc = str_replace(",", "", $val[2]);
                        $sql .= " ('$ean', '$kategoria', $ilosc), ";
                    }
                    $sql = rtrim($sql, ', ');
                    sqlsrv_query($conn, $sql);
                    $i++;
                }
                $sql = "WITH RankedCte AS (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY tn_ean ORDER BY tn_ilosc) AS rn
                    FROM tw_nielsen
                )
                DELETE FROM RankedCte
                WHERE rn > 1";
                sqlsrv_query($conn, $sql);
                $sql = "delete from tw_nielsen where len(tn_ean)<8;
                update tw_nielsen set tn_gt = (select tw_gt from tw__towar inner join tw_plu on plu_twid=tw_id where tn_ean=plu_kod)";
                sqlsrv_query($conn, $sql);
            }
        }
        //BaGaPol
        elseif (in_array($ext, ['csv','txt'])) { 
            $raport = TAC::czytaj_BaGaPol($_FILES['file']['tmp_name']);
            $sql = "delete from tw_bagapol";
            sqlsrv_query($conn, $sql);
            $i = 0;
            while ($i < floor(count($raport)/1000) + 1) {
                $dane2 = array_slice ($raport, $i*1000, 1000, true);
                $sql = "INSERT INTO tw_bagapol (tb_ean, tb_kategoria, tb_ilosc, tb_gt) VALUES ";
                foreach ($dane2 as $key=>$val) {
                    $ean = substr($val[0],0,13);
                    $kategoria = $val[1];
                    $ilosc = str_replace(",", "", $val[2]);
                    $sql .= " ('$ean', '$kategoria', $ilosc, '$kategoria'), ";
                }
                $sql = rtrim($sql, ', ');
                sqlsrv_query($conn, $sql);
                $i++;
            }
            $sql = "WITH RankedCte AS (
                SELECT *, ROW_NUMBER() OVER (PARTITION BY tb_ean ORDER BY tb_ilosc) AS rn
                FROM tw_bagapol
            )
            DELETE FROM RankedCte
            WHERE rn > 1";
            sqlsrv_query($conn, $sql);
            $sql = "delete from tw_bagapol where len(tb_ean)<8";
            sqlsrv_query($conn, $sql);
        }
    }
}

?>
