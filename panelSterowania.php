<?php
// Ustawienie katalogu projektu
$projectDir = './';

// Lista wybranych plików PHP z niestandardowymi nazwami
$allowedFiles = [
    'dodaj.php' => 'Zaktualizuj dane statystyczne konkurencji',
    'index.php' => 'Sprawdź dane statystyczne konkurencji',
    'dodaj_ppmi.php' => 'Dodaj PPMI',
    'dodaj_ppmo.php' => 'Dodaj PPMO',
    'algorytmWyznaczania.php' => 'Algorytm wyznaczania optymalnej ceny sprzedaży'

];

// Obsługa przekierowania
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $selectedFile = $_GET['file'];
    if (array_key_exists($selectedFile, $allowedFiles)) {
        $filePath = $projectDir . $selectedFile;
        if (file_exists($filePath) && is_file($filePath)) {
            header("Location: $selectedFile");
            exit;
        }
    }
    $error = "Błąd: Nie można otworzyć wybranego pliku.";
}

// Zliczenie liczby dostępnych plików
$fileCount = count($allowedFiles);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel sterowania - Analiza konkurencji</title>
    <style>
        :root {
            --primary-color: #bb86fc;
            --secondary-color: #BE5CD9;
            --background-color: #121212;
            --surface-color: #1e1e1e;
            --on-surface-color: #ffffff;
            --error-color: #cf6679;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--on-surface-color);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background-color: var(--surface-color);
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        main {
            flex-grow: 1;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 0;
        }
        h2 {
            color: var(--secondary-color);
        }
        .button-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
        }
        .button {
            display: block;
            padding: 15px 20px;
            background-color: #121212;
            color: var(--on-surface-color);
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        .button:hover {
            background-color: var(--primary-color);
            color: var(--background-color);
        }
        .single-button {
            grid-column: span 2;
        }
        .error {
            color: var(--error-color);
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Panel sterowania</h1>
        <p>Analiza konkurencji i optymalizacja cen</p>
    </header>
    
    <main>
        <h2>Dostępne funkcje</h2>
        <div class="button-container">
            <?php
            $counter = 0;
            foreach ($allowedFiles as $file => $name):
                $counter++;
                // Przypisanie klasy 'single-button' ostatniemu przyciskowi jeśli liczba przycisków jest nieparzysta
                $isLastSingle = ($counter === $fileCount && $fileCount % 2 !== 0) ? 'single-button' : '';
            ?>
                <a href="?file=<?= urlencode($file) ?>" target="_blank" class="button <?= $isLastSingle ?>">
                    <?= htmlspecialchars($name) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
