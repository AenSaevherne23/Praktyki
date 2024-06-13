<?php
// Dane wejściowe
$cenaZakupu = 100.0; // Cena zakupu
$vat = 0.23; // Stawka VAT (np. 23% w Polsce)
$marza = 0.2; // Marża (np. 20%)
$sredniaCenaKonkurencji = 130.0; // Średnia cena konkurencji
$ppmi = 140.0; // Maksymalna cena sprzedaży
$ppmo = 120.0; // Minimalna cena sprzedaży

// Cena zakupu netto
$cenaZakupuNetto = $cenaZakupu / (1 + $vat);

// Cena sprzedaży netto (uwzględniająca marżę)
$cenaSprzedazyNetto = $cenaZakupuNetto * (1 + $marza);

// Cena sprzedaży brutto (uwzględniająca VAT)
$cenaSprzedazyBrutto = $cenaSprzedazyNetto * (1 + $vat);

// Korekta ceny sprzedaży według średniej ceny konkurencji
if ($sredniaCenaKonkurencji < $cenaSprzedazyBrutto) {
    $cenaSprzedazyBrutto = $sredniaCenaKonkurencji - 0.01;
}

// Sprawdzenie warunków cenowych
if ($cenaSprzedazyBrutto > $ppmi) {
    $cenaSprzedazyBrutto = $ppmi;
}

if ($cenaSprzedazyBrutto < $ppmo) {
    $cenaSprzedazyBrutto = $ppmo;
}

// Wygenerowanie wyniku
echo "Ostateczna cena sprzedaży: " . number_format($cenaSprzedazyBrutto, 2, '.', '') . " PLN";
?>
