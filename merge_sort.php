<?php

function mergeSort(array $arr, int &$comparisons): array {
    $n = count($arr);

    if ($n <= 1) {
        return $arr;
    }

    $mid = (int)(count($arr) / 2);

    $left = array_slice($arr, 0, $mid);
    $right = array_slice($arr, $mid);

    $left = mergeSort($left, $comparisons);
    $right = mergeSort($right, $comparisons);

    return merge($left, $right, $comparisons);
}

function merge(array $left, array $right, int &$comparisons): array {
    $result = [];

    while (count($left) > 0 && count($right) > 0) {
        $comparisons++;

        if ($left[0] <= $right[0]) {
            $result[] = array_shift($left);
        } else {
            $result[] = array_shift($right);
        }
    }

    return array_merge($result, $left, $right);
}

$tablice = [
    [5, 3, 8, 1, 9, 2],
    [38, 27, 43, 3, 9, 82, 10, 15],
    [64, 25, 12, 22, 11, 90, 3, 47, 71, 38, 55, 8],
    [25, 24, 23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
];

foreach ($tablice as $arr) {
    $comparisons = 0;
    $n = count($arr);

    $sorted = mergeSort($arr, $comparisons);

    $k = $comparisons / ($n * log($n, 2));

    echo "n=$n | Wejście: [" . implode(", ", $arr) . "]\n";
    echo "     | Wyjście: [" . implode(", ", $sorted) . "]\n";
    echo "     | Porównania: $comparisons | K: " . number_format($k, 3) . "\n\n";
}

$test = $tablice[1];
$comparisons = 0;

$mergeSorted = mergeSort($test, $comparisons);

$phpSorted = $test;
sort($phpSorted);

if ($mergeSorted === $phpSorted) {
    echo "Weryfikacja z sort(): ZGODNA\n";
} else {
    echo "Weryfikacja z sort(): NIEZGODNA\n";
}