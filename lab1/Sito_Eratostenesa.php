<?php

// Funkcja sito(int $n): array
// Zwraca wszystkie liczby pierwsze od 2 do n
function sito(int $n): array
{
    // Jeśli n < 2, to nie ma liczb pierwszych
    if ($n < 2) {
        return [];
    }

    // Tworzymy tablicę boolowską długości n+1
    // Na początku zakładamy, że każda liczba jest pierwsza
    $A = array_fill(0, $n + 1, true);

    // 0 i 1 nie są liczbami pierwszymi
    $A[0] = false;
    $A[1] = false;

    // Przechodzimy od 2 do sqrt(n)
    // Jeśli A[i] jest true, to wykreślamy wszystkie wielokrotności i
    for ($i = 2; $i <= sqrt($n); $i++) {
        if ($A[$i]) {
            // Zaczynamy od i*i, bo mniejsze wielokrotności
            // zostały już wykreślone wcześniej
            for ($j = $i * $i; $j <= $n; $j += $i) {
                $A[$j] = false;
            }
        }
    }

    // Zbieramy indeksy, które nadal mają wartość true
    // To właśnie liczby pierwsze
    $primes = [];
    for ($i = 2; $i <= $n; $i++) {
        if ($A[$i] === true) {
            $primes[] = $i;
        }
    }

    return $primes;
}

// Funkcja wybiera liczby pierwsze z konkretnego przedziału [a, b]
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

// Funkcja znajduje wszystkie pary Goldbacha dla danej liczby parzystej n
// Korzystamy z tablicy asocjacyjnej $primeSet dla szybkiego sprawdzania
function goldbachPairs(int $n, array $primeSet): array
{
    $pairs = [];

    // Sprawdzamy tylko do n/2,
    // żeby nie powtarzać tych samych par w odwrotnej kolejności
    for ($p = 2; $p <= $n / 2; $p++) {
        $q = $n - $p;

        // Jeśli p i q są pierwsze, to mamy parę Goldbacha
        if (isset($primeSet[$p]) && isset($primeSet[$q])) {
            $pairs[] = [$p, $q];
        }
    }

    return $pairs;
}

// Liczby pierwsze do 500 będą potrzebne do dalszych obliczeń
$primesTo500 = sito(500);

// array_flip tworzy tablicę asocjacyjną:
// np. [2,3,5] zamienia na [2=>0, 3=>1, 5=>2]
// Dzięki temu isset($primeSet[liczba]) działa szybko
$primeSet = array_flip($primesTo500);

// ========================
// 1. Liczby pierwsze do 100
// ========================

echo "Liczby pierwsze [1-100] (bloki po 10):\n";

// Pobieramy liczby pierwsze do 100
$primesTo100 = sito(100);

// Dzielimy wynik na bloki po 10 elementów
$chunks = array_chunk($primesTo100, 10);

// Wypisujemy każdy blok osobno
foreach ($chunks as $chunk) {
    echo "[" . implode(", ", $chunk) . "]\n";
}

// ========================
// 2. Gęstość liczb pierwszych
// ========================

echo "\nGęstość liczb pierwszych:\n";

// Przedziały z treści zadania
$ranges = [
    [1, 100],
    [101, 200],
    [201, 300],
    [301, 400],
    [401, 500],
];

foreach ($ranges as [$a, $b]) {
    // Bierzemy liczby pierwsze tylko z danego przedziału
    $primesInThisRange = primesInRange($primesTo500, $a, $b);

    // Liczba liczb pierwszych w przedziale
    $count = count($primesInThisRange);

    // Środek przedziału
    $middle = ($a + $b) / 2;

    // Gęstość teoretyczna ze wzoru:
    // (b-a) / ln(środek)
    $theoretical = ($b - $a) / log($middle);

    echo "Przedział [$a-$b]: $count (teoretycznie: ~" . number_format($theoretical, 1) . ")\n";
}

echo "\n";

// ========================
// 3. Hipoteza Goldbacha
// ========================

// Zmienna do zapamiętania liczby z największą liczbą par
$maxNumber = 0;

// Największa liczba znalezionych par
$maxPairsCount = 0;

// Dla każdej liczby parzystej od 4 do 200
for ($n = 4; $n <= 200; $n += 2) {
    $pairs = goldbachPairs($n, $primeSet);
    $countPairs = count($pairs);

    // Jeśli dla tej liczby znaleźliśmy więcej par,
    // zapamiętujemy ją jako nowego lidera
    if ($countPairs > $maxPairsCount) {
        $maxPairsCount = $countPairs;
        $maxNumber = $n;
    }
}

// Wypisujemy liczbę z największą liczbą par Goldbacha
echo "Goldbach — najwięcej par w [4, 200]: Liczba $maxNumber ($maxPairsCount par)\n";

// Szukamy wszystkich par Goldbacha dla liczby 30
$pairs30 = goldbachPairs(30, $primeSet);

echo "Pary Goldbacha dla 30: ";

// Zamieniamy pary na tekst w formacie [7+23]
$formattedPairs = [];
foreach ($pairs30 as [$p, $q]) {
    $formattedPairs[] = "[$p+$q]";
}

echo implode(", ", $formattedPairs) . "\n";