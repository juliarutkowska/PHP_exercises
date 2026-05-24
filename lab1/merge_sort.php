<?php

// Funkcja mergeSort dzieli tablicę na coraz mniejsze części,
// a potem scala je z powrotem w odpowiedniej kolejności.
function mergeSort(array $arr, int &$comparisons): array {
    // Pobieramy liczbę elementów tablicy
    $n = count($arr);

    // Warunek kończący rekurencję:
    // jeśli tablica ma 0 lub 1 element, to jest już posortowana
    if ($n <= 1) {
        return $arr;
    }

    // Obliczamy środek tablicy
    $mid = (int)($n / 2);

    // Dzielimy tablicę na lewą i prawą połowę
    $left = array_slice($arr, 0, $mid);
    $right = array_slice($arr, $mid);

    // Rekurencyjnie sortujemy lewą połowę
    $left = mergeSort($left, $comparisons);

    // Rekurencyjnie sortujemy prawą połowę
    $right = mergeSort($right, $comparisons);

    // Scalanie dwóch już posortowanych połówek
    return merge($left, $right, $comparisons);
}

// Funkcja merge scala dwie posortowane tablice w jedną posortowaną
function merge(array $left, array $right, int &$comparisons): array {
    // Tutaj będziemy budować wynik
    $result = [];

    // Dopóki obie tablice mają elementy,
    // porównujemy ich pierwsze elementy
    while (count($left) > 0 && count($right) > 0) {
        // Zliczamy każde porównanie
        $comparisons++;

        // Jeśli pierwszy element lewej tablicy jest mniejszy lub równy,
        // dodajemy go do wyniku
        if ($left[0] <= $right[0]) {
            $result[] = array_shift($left);
        } else {
            // W przeciwnym razie dodajemy pierwszy element prawej tablicy
            $result[] = array_shift($right);
        }
    }

    // Gdy jedna tablica się skończy,
    // dokładamy resztę drugiej do wyniku
    return array_merge($result, $left, $right);
}

// Tablice testowe z treści zadania
$tablice = [
    [5, 3, 8, 1, 9, 2],
    [38, 27, 43, 3, 9, 82, 10, 15],
    [64, 25, 12, 22, 11, 90, 3, 47, 71, 38, 55, 8],
    [25, 24, 23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
];

// Przechodzimy po każdej tablicy i testujemy algorytm
foreach ($tablice as $arr) {
    // Na początku liczba porównań wynosi 0
    $comparisons = 0;

    // Liczba elementów w danej tablicy
    $n = count($arr);

    // Sortujemy tablicę
    $sorted = mergeSort($arr, $comparisons);

    // Obliczamy współczynnik K ze wzoru:
    // K = comparisons / (n * log2(n))
    $k = $comparisons / ($n * log($n, 2));

    // Wypisujemy dane wejściowe
    echo "n=$n | Wejście: [" . implode(", ", $arr) . "]\n";

    // Wypisujemy tablicę po sortowaniu
    echo "     | Wyjście: [" . implode(", ", $sorted) . "]\n";

    // Wypisujemy liczbę porównań i współczynnik K
    echo "     | Porównania: $comparisons | K: " . number_format($k, 3) . "\n\n";
}

// Dodatkowa weryfikacja działania przy użyciu wbudowanej funkcji sort()
$test = $tablice[1];

// Zerujemy licznik porównań
$comparisons = 0;

// Sortowanie własnym algorytmem
$mergeSorted = mergeSort($test, $comparisons);

// Kopiujemy tablicę do osobnej zmiennej
$phpSorted = $test;

// Sortowanie wbudowaną funkcją PHP
sort($phpSorted);

// Porównujemy oba wyniki
if ($mergeSorted === $phpSorted) {
    echo "Weryfikacja z sort(): ZGODNA\n";
} else {
    echo "Weryfikacja z sort(): NIEZGODNA\n";
}