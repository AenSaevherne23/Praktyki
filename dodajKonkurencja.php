<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Import danych konkurencji</title>
</head>
<body>
    <h2>Import danych do tabeli tw_konkurencja_nowa</h2>
    <form action="dodajKonkurencja.php" method="post" enctype="multipart/form-data">
        Wybierz plik do importu: <input type="file" name="fileToImport" id="fileToImport"><br><br>
        Nazwa sklepu (tk_siec): <input type="text" name="networkName"><br><br>
        <input type="submit" value="Importuj dane" name="submit">
    </form>

    <?php
    require_once("config.php");

    if(isset($_POST["submit"])) {
        $networkName = $_POST["networkName"];

        // Obsługa pliku
        $file = $_FILES['fileToImport']['tmp_name'];

        $handle = fopen($file, "r");
        if ($handle) {
            // Prepare the insert statement
            $sql = "INSERT INTO tw_konkurencja_nowa (tk_siec, tk_plu, tk_cena) VALUES (?, ?, ?)";
            $stmt = sqlsrv_prepare($conn, $sql, array(&$networkName, &$tk_plu, &$tk_cena));

            if (!$stmt) {
                echo "Błąd przy przygotowywaniu zapytania: " . print_r(sqlsrv_errors(), true);
                exit;
            }

            while (($line = fgets($handle)) !== false) {
                $data = explode("\t", $line);
                $tk_plu = trim($data[0]);
                $tk_cena = trim($data[1]);

                // Execute the statement
                if (!sqlsrv_execute($stmt)) {
                    echo "Błąd przy wykonaniu zapytania: " . print_r(sqlsrv_errors(), true);
                    exit;
                }
            }

            fclose($handle);
            echo "<p>Import danych zakończony sukcesem.</p>";
        } else {
            echo "<p>Błąd podczas otwierania pliku.</p>";
        }
    }
    ?>
</body>
</html>
