<?php
require_once("config.php");
require_once("Excel.php");

function process_file($conn) {
    echo "<form method=\"post\" enctype=\"multipart/form-data\" target=\"_self\" id='form_wczytaj_z_pliku'>";
    echo "Wybierz plik:<input type=\"file\" id=\"file\" name=\"file\" style='margin-right: 2px' required>";
    echo "<button type='submit'>Wyślij</button><br/>";
    echo "</form>";

    if (!empty($_FILES['file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext == 'xlsx' || $ext == 'xls') {
            $excel = new Excel($_FILES['file']['tmp_name']);
            $data = $excel->getSpreadsheetValues(2); // Zaczynamy od trzeciego wiersza
        } elseif (in_array($ext, ['csv','txt'])) { 
            $data = file($_FILES['file']['tmp_name']);
            $data = array_slice($data, 2); // Zaczynamy od trzeciego wiersza
            foreach ($data as &$line) {
                $line = explode(',', $line);
            }
        } else {
            die("Nieprawidłowy format pliku. Dozwolone formaty to XLSX, XLS, CSV, TXT.");
        }

        // Pętla przetwarzająca dane z pliku
        foreach ($data as $row) {
            $plu = substr($row[1], 0, 20);  // Ograniczamy do 20 znaków
            $ppmo = str_replace(",", ".", $row[4]);  // Indeks 6 to kolumna PPMO

            // Sprawdzenie czy PLU nie jest puste
            if (!empty($plu)) {
                // Sprawdzenie czy rekord już istnieje
                $sql_check = "SELECT tk_id, tk_ppmo FROM dbo.tw_towar_ppmo WHERE tk_plu = ?";
                $params_check = array($plu);
                $query_check = sqlsrv_query($conn, $sql_check, $params_check);
                if($query_check === false) {
                    die("Błąd podczas sprawdzania istnienia rekordu: " . print_r(sqlsrv_errors(), true));
                }
                $row_check = sqlsrv_fetch_array($query_check, SQLSRV_FETCH_ASSOC);

                if($row_check) {
                    // Rekord istnieje, sprawdzamy czy wartość się zmieniła
                    $ppmo_db = $row_check['tk_ppmo'];

                    if($ppmo != $ppmo_db) {
                        // Wartości się różnią, aktualizujemy rekord
                        $tk_id = $row_check['tk_id'];
                        $sql_update = "UPDATE dbo.tw_towar_ppmo SET tk_ppmo = ?, tk_zaktualizowano = CURRENT_TIMESTAMP WHERE tk_id = ?";
                        $params_update = array($ppmo, $tk_id);
                        $query_update = sqlsrv_query($conn, $sql_update, $params_update);
                        if($query_update === false) {
                            die("Błąd podczas aktualizacji rekordu: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                } else {
                    // Rekord nie istnieje, dodajemy nowy
                    $sql_insert = "INSERT INTO dbo.tw_towar_ppmo (tk_plu, tk_ppmo, tk_zaktualizowano) VALUES (?, ?, CURRENT_TIMESTAMP)";
                    $params_insert = array($plu, $ppmo);
                    $query_insert = sqlsrv_query($conn, $sql_insert, $params_insert);
                    if($query_insert === false) {
                        die("Błąd podczas dodawania nowego rekordu: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }
        }
        echo "Dane zostały przetworzone i dodane do bazy.";
    }
}

process_file($conn);
?>
