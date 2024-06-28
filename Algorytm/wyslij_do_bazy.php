<?php
require_once("generujOcs.php");
require_once("db_query.php");
require_once("config.php");

// Wywołanie funkcji generującej i uzyskanie tablicy $ocs_array
$ocs_array = generujOcs($stmt);

// Przygotowanie zapytania SQL do aktualizacji
$sql = "UPDATE tw_srednia_sprzedaz
        SET tw_ocs = t.ocs
        FROM tw_srednia_sprzedaz ts
        JOIN (
            VALUES ";

// Przygotowanie wartości dla zapytania masowego
$values = [];
$params = [];
$index = 0;
foreach ($ocs_array as $plu_kod => $ocs) {
    $index++;
    $values[] = "(?, ?)";
    $params[] = $plu_kod;
    $params[] = $ocs;

    // Dodanie parametrów do zapytania SQL co 500 wartości (maksymalna liczba parametrów SQL Server)
    if ($index % 500 == 0) {
        $sql .= implode(", ", $values);
        $sql .= ") AS t(plu_kod, ocs) ON ts.tw_plu = t.plu_kod";

        // Wykonanie zapytania SQL
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $result = sqlsrv_execute($stmt);
        if ($result === false) {
            // Błąd podczas wykonania zapytania SQL
            $message = "Błąd podczas aktualizacji danych: " . print_r(sqlsrv_errors(), true);
            header("Location: http://localhost/praktyki/Algorytm/algorytmWyznaczania.php?error=" . urlencode($message));
            exit;
        }

        // Resetowanie zmiennych do kolejnej partii zapytania
        $sql = "UPDATE tw_srednia_sprzedaz
                SET tw_ocs = t.ocs
                FROM tw_srednia_sprzedaz ts
                JOIN (
                    VALUES ";
        $values = [];
        $params = [];
    }
}

// Wykonanie ostatniej partii, jeśli istnieje
if (!empty($values)) {
    $sql .= implode(", ", $values);
    $sql .= ") AS t(plu_kod, ocs) ON ts.tw_plu = t.plu_kod";

    // Wykonanie zapytania SQL
    $stmt = sqlsrv_prepare($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        // Błąd podczas wykonania zapytania SQL
        $message = "Błąd podczas aktualizacji danych: " . print_r(sqlsrv_errors(), true);
        header("Location: http://localhost/praktyki/Algorytm/algorytmWyznaczania.php?error=" . urlencode($message));
        exit;
    }
}

// Sukces: Przekierowanie z odpowiednim komunikatem
header("Location: http://localhost/praktyki/Algorytm/algorytmWyznaczania.php?sukces=Pomyslnie%20dodano%20do%20bazy");
exit;

?>
