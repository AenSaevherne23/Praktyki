<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wysyłanie danych do tabeli</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>

<form action="wyslij_do_bazy.php" method="POST">
    <button type="submit" name="wyslij_do_bazy_btn">Wyślij dane do tabeli w bazie</button>
</form>
<?php
    if(isset($_GET['sukces'])){
        echo('<p style="color: green">'.$_GET['sukces'].'</p>');
    }
    // Obsługa komunikatu błędu
    if(isset($_GET['error'])){
        echo('<p style="color: red">'.$_GET['error'].'</p>');
    }
?>
<div class="table-container">
    <?php
    require_once("generujTabele.php");
    require_once("db_query.php");

    // Wywołanie funkcji generującej tabelę
    generujTabele($stmt);
    ?>
</div>

</body>
</html>
