<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista produktów</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>Lista produktów</h2>

<table>
    <thead>
        <tr>
            <th>GT</th>
            <th>GT Marża</th>
            <th>PLU</th>
            <th>Nazwa</th>
            <th>VAT</th>
            <th>Cena Sprzedaży</th>
            <th>Cena Zakupu</th>
            <th>Pamp</th>
            <th>Cena PPMI</th>
            <th>Cena PPMO</th>
            <th>Średnia Cena Konkurencja</th>
            <th>Status Ceny</th>
        </tr>
    </thead>
    <tbody>

    <?php
    // Wczytanie danych z pliku CSV
    $file = fopen('dane_z_bazy.csv', 'r');

    // Odczytanie nagłówka
    $header = fgetcsv($file);

    // Pętla do odczytu i wyświetlenia danych
    while (($line = fgetcsv($file)) !== false) {
        echo "<tr>";
        foreach ($line as $key => $cell) {
            // Sprawdzenie, czy istnieje odpowiadająca komórka w nagłówku
            $header_value = isset($header[$key]) ? $header[$key] : '';
            echo "<td>{$cell}</td>";
        }
        echo "</tr>";
    }
    fclose($file);
    ?>

    </tbody>
</table>

</body>
</html>
