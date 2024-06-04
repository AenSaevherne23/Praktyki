<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj produkt</title>
</head>
<body>
<form action="dodaj.php" method="POST">
    <label for="id">Podaj nr ID:</label>
    <input type="number" id="id" name="id" />
    <br />
    <label for="czas">Podaj datę:</label>
    <input type="date" id="czas" name="czas" />
    <br />
    <label for="plu">Podaj nr EAN:</label>
    <input type="text" id="plu" name="plu" />
    <br />
    <label for="ilosc">Podaj ilość wystąpień:</label>
    <input type="text" id="ilosc" name="ilosc" />
    <br />
    <label for="srednia">Podaj średnią cenę:</label>
    <input type="text" id="srednia" name="srednia" />
    <br />
    <label for="mediana">Podaj medianę:</label>
    <input type="text" id="mediana" name="mediana" />
    <br />
    <label for="dominanta">Podaj dominantę:</label>
    <input type="text" id="dominanta" name="dominanta" />
    <button type="submit">Dodaj</button>
</form>

<?php
require_once("config.php");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $czas = $_POST['czas'];
    $plu = $_POST['plu'];
    $ilosc = $_POST['ilosc'];
    $srednia = $_POST['srednia'];
    $mediana = $_POST['mediana'];
    $dominanta = $_POST['dominanta'];

    // Zapytanie SQL do wstawienia danych
    $query = "INSERT INTO dbo.tw_konkurencja_obliczenia (
                tk_id, tk_data, tk_plu, tk_ilosc_wystapien, tk_srednia_cena, tk_mediana, tk_dominanta
              ) VALUES (
                '$id', '$czas', '$plu', '$ilosc', '$srednia', '$mediana', '$dominanta'
              )";

    $insertResult = sqlsrv_query($conn, $query);

    if ($insertResult === false) {
        echo "Błąd podczas dodawania danych: " . print_r(sqlsrv_errors(), true);
    } else {
        echo "Dane zostały pomyślnie dodane do bazy danych.";
    }
}


?>
</body>
</html>