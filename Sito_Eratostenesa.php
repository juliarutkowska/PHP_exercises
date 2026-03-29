<?php

function sito(int $n): array
{
    if ($n < 2) {
        return [];
    }

    $A = array_fill(0, $n + 1, true);
    $A[0] = false;
    $A[1] = false;

    for ($i = 2; $i <= sqrt($n); $i++) {
        if ($A[$i]) {
            for ($j = $i * $i; $j <= $n; $j += $i) {
                $A[$j] = false;
            }
        }
    }

    $primes = [];
    for ($i = 2; $i <= $n; $i++) {
        if ($A[$i] === true) {
            $primes[] = $i;
        }
    }

    return $primes;
}

function primesInRange(array $primes, int $a, int $b): array
{
    $result = [];

    foreach ($primes as $p) {
        if ($p >= $a && $p <= $b) {
            $result[] = $p;
        }
    }

    return $result;
}

function goldbachPairs(int $n, array $primeSet): array
{
    $pairs = [];

    for ($p = 2; $p <= $n / 2; $p++) {
        $q = $n - $p;

        if (isset($primeSet[$p]) && isset($primeSet[$q])) {
            $pairs[] = [$p, $q];
        }
    }

    return $pairs;
}

$primesTo500 = sito(500);
$primeSet = array_flip($primesTo500);

echo "Liczby pierwsze [1-100] (bloki po 10):\n";
$primesTo100 = sito(100);
$chunks = array_chunk($primesTo100, 10);

foreach ($chunks as $chunk) {
    echo "[" . implode(", ", $chunk) . "]\n";
}

echo "\nGęstość liczb pierwszych:\n";

$ranges = [
    [1, 100],
    [101, 200],
    [201, 300],
    [301, 400],
    [401, 500],
];

foreach ($ranges as [$a, $b]) {
    $primesInThisRange = primesInRange($primesTo500, $a, $b);
    $count = count($primesInThisRange);

    $middle = ($a + $b) / 2;
    $theoretical = ($b - $a) / log($middle);

    echo "Przedział [$a-$b]: $count (teoretycznie: ~" . number_format($theoretical, 1) . ")\n";
}

echo "\n";

$maxNumber = 0;
$maxPairsCount = 0;
$allPairsForMax = [];

for ($n = 4; $n <= 200; $n += 2) {
    $pairs = goldbachPairs($n, $primeSet);
    $countPairs = count($pairs);

    if ($countPairs > $maxPairsCount) {
        $maxPairsCount = $countPairs;
        $maxNumber = $n;
        $allPairsForMax = $pairs;
    }
}

echo "Goldbach - najwięcej par w [4, 200]: Liczba $maxNumber ($maxPairsCount par)\n";

$pairs30 = goldbachPairs(30, $primeSet);
echo "Pary Goldbacha dla 30: ";

$formattedPairs = [];
foreach ($pairs30 as [$p, $q]) {
    $formattedPairs[] = "[$p+$q]";
}

echo implode(", ", $formattedPairs) . "\n";