<?php
//require 'C:\xampp\htdocs\Praktyki\vendor\autoload.php';
require_once("Excel.php");

// Ścieżka do pliku Excel
$file = 'C:\\Users\\Len\\Desktop\\Pliki\\NOWE\\PPMI_raport_wdrozenia_06.05-09.06.2024.xlsx';

// Sprawdź, czy plik istnieje
if (!file_exists($file)) {
    die("Plik nie istnieje: $file");
}

// Inicjalizacja obiektu Excel z obsługą błędów
try {
    $excel = new Excel($file);
} catch (Exception $e) {
    die('Błąd podczas ładowania pliku Excel: ' . $e->getMessage());
}

// Pobierz wartości z arkusza
$values = $excel->getSpreadsheetValues();

// Funkcja do wyświetlania tabeli
function displayTable($data) {
    echo '<table border="1">';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odczyt danych z pliku Excel</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Dane z pliku Excel</h1>
    <?php 
    if ($values !== false) {
        displayTable($values);
    } else {
        echo "<p>Brak danych do wyświetlenia.</p>";
    }
    ?>
</body>
</html>
