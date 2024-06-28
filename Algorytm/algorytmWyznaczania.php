<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabela produktów</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>

<?php
require_once("db_query.php");
require_once("generujTabele.php");

// Wywołanie funkcji generującej tabelę
generujTabele($stmt);
?>

</body>
</html>
