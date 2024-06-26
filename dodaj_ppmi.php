<?php
require_once("config.php");
require_once("Excel.php");

function process_file($conn) {
    echo "<!DOCTYPE html>
    <html lang='pl'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Dodaj PPMI</title>
        <link rel='stylesheet' href='styles.css'>
    </head>
    <body>
    <main class='container'>
        <header>
            <h1>Wybierz plik CSV/Exel z danymi PPMI</h1>
        </header>
        <div class='form-container'>
            <form method='post' enctype='multipart/form-data' target='_self' id='form_wczytaj_z_pliku'>
                <label for='file'>Wybierz plik:</label>
                <input type='file' id='file' name='file' style='margin-right: 2px' required>
                <button type='submit'>Wyślij</button>
            </form>
        </div>";

    if (!empty($_FILES['file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext == 'xlsx' || $ext == 'xls') {
            // Pobieranie danych z pliku Excel
            $excel = new Excel($_FILES['file']['tmp_name']);
            $data = $excel->getSpreadsheetValues();

            // Automatyczne wykrywanie nagłówka
            $headerRow = 0;
            $headerFound = false;
            foreach ($data as $rowIndex => $row) {
                if (in_array('PLU', $row) && in_array('PPMI', $row)) {
                    $headerRow = $rowIndex;
                    $headerFound = true;
                    break;
                }
            }

            if (!$headerFound) {
                die("<div class='error'>Nie znaleziono nagłówków 'PLU' i 'PPMI' w pliku.</div>");
            }

            $data = array_slice($data, $headerRow + 1); // Pobieramy dane od wiersza poniżej nagłówka
        } elseif (in_array($ext, ['csv','txt'])) { 
            // Pobieranie danych z pliku CSV lub TXT
            $data = file($_FILES['file']['tmp_name']);
            $headerFound = false;
            foreach ($data as $rowIndex => $line) {
                $row = explode(',', $line);
                if (in_array('PLU', $row) && in_array('PPMI', $row)) {
                    $headerFound = true;
                    break;
                }
            }

            if (!$headerFound) {
                die("<div class='error'>Nie znaleziono nagłówków 'PLU' i 'PPMI' w pliku.</div>");
            }

            $data = array_slice($data, $rowIndex + 1); // Pobieramy dane od wiersza poniżej nagłówka
            foreach ($data as &$line) {
                $line = explode(',', $line);
            }
        } else {
            die("<div class='error'>Nieprawidłowy format pliku. Dozwolone formaty to XLSX, XLS, CSV, TXT.</div>");
        }

        // Pętla przetwarzająca dane z pliku
        foreach ($data as $row) {
            $plu = substr($row[3], 0, 20);  // Ograniczamy do 20 znaków
            $ppmi = str_replace(",", ".", $row[6]);

            // Sprawdzenie, czy PLU nie jest puste
            if (!empty($plu)) {
                // Sprawdzenie, czy rekord już istnieje
                $sql_check = "SELECT tk_id, tk_ppmi FROM dbo.tw_towar_ppmi WHERE tk_plu = ?";
                $params_check = array($plu);
                $query_check = sqlsrv_query($conn, $sql_check, $params_check);
                if ($query_check === false) {
                    die("<div class='error'>Błąd podczas sprawdzania istnienia rekordu: " . print_r(sqlsrv_errors(), true) . "</div>");
                }
                $row_check = sqlsrv_fetch_array($query_check, SQLSRV_FETCH_ASSOC);

                if ($row_check) { 
                    // Rekord istnieje, sprawdzamy czy wartość się zmieniła
                    $ppmi_db = $row_check['tk_ppmi'];

                    if ($ppmi != $ppmi_db) {
                        // Wartości się różnią, aktualizujemy rekord
                        $tk_id = $row_check['tk_id'];
                        $sql_update = "UPDATE dbo.tw_towar_ppmi SET tk_ppmi = ?, tk_zaktualizowano = CURRENT_TIMESTAMP WHERE tk_id = ?";
                        $params_update = array($ppmi, $tk_id);
                        $query_update = sqlsrv_query($conn, $sql_update, $params_update);
                        if ($query_update === false) {
                            die("<div class='error'>Błąd podczas aktualizacji rekordu: " . print_r(sqlsrv_errors(), true) . "</div>");
                        }
                    }
                } else {
                    // Rekord nie istnieje, dodajemy nowy
                    $sql_insert = "INSERT INTO dbo.tw_towar_ppmi (tk_plu, tk_ppmi, tk_zaktualizowano) VALUES (?, ?, CURRENT_TIMESTAMP)";
                    $params_insert = array($plu, $ppmi);
                    $query_insert = sqlsrv_query($conn, $sql_insert, $params_insert);
                    if ($query_insert === false) {
                        die("<div class='error'>Błąd podczas dodawania nowego rekordu: " . print_r(sqlsrv_errors(), true) . "</div>");
                    }
                }
            }
        }
        echo "<div class='result'>Dane zostały przetworzone i dodane do bazy.</div>";
    }

    echo "</main>
    </body>
    </html>";
}

process_file($conn);
?>
