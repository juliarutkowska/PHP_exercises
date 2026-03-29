<?php

$oceny = [
    "Anna"    => [5, 4, null, 2, null, 3, 4, 5],
    "Bartek"  => [4, 5, 3, null, 2, 4, null, 4],
    "Celina"  => [5, 3, null, 3, null, 4, 5, null],
    "Dawid"   => [2, null, 4, 5, 3, null, 2, 3],
    "Ewa"     => [null, 4, 3, null, 5, 3, 4, 2],
    "Filip"   => [3, 5, 4, 2, null, 5, null, 4],
    "Grażyna" => [5, null, 2, 4, 3, 2, 5, null],
];

$produkty = ["Laptop","Monitor","Klawiatura","Mysz","Słuchawki","Kamera","Tablet","Głośnik"];

function pearson(array $a, array $b): float
{
    $wspolneA = [];
    $wspolneB = [];

    for ($i = 0; $i < count($a); $i++) {
        if ($a[$i] !== null && $b[$i] !== null) {
            $wspolneA[] = $a[$i];
            $wspolneB[] = $b[$i];
        }
    }

    $n = count($wspolneA);

    if ($n < 2) {
        return 0.0;
    }

    $sredniaA = array_sum($wspolneA) / $n;
    $sredniaB = array_sum($wspolneB) / $n;

    $licznik = 0.0;
    $sumaKwA = 0.0;
    $sumaKwB = 0.0;

    for ($i = 0; $i < $n; $i++) {
        $da = $wspolneA[$i] - $sredniaA;
        $db = $wspolneB[$i] - $sredniaB;

        $licznik += $da * $db;
        $sumaKwA += $da * $da;
        $sumaKwB += $db * $db;
    }

    $mianownik = sqrt($sumaKwA * $sumaKwB);

    if ($mianownik == 0.0) {
        return 0.0;
    }

    return $licznik / $mianownik;
}

function podobienstwaDlaUzytkownika(string $cel, array $oceny): array
{
    $wyniki = [];

    foreach ($oceny as $uzytkownik => $ratingi) {
        if ($uzytkownik === $cel) {
            continue;
        }

        $sim = pearson($oceny[$cel], $ratingi);
        $wyniki[$uzytkownik] = $sim;
    }

    arsort($wyniki);

    return $wyniki;
}

function topK(array $podobienstwa, int $k): array
{
    return array_slice($podobienstwa, 0, $k, true);
}

function przewidzOcene(string $uzytkownik, int $produktIndex, array $oceny, array $sasiedzi): ?float
{
    $licznik = 0.0;
    $mianownik = 0.0;

    foreach ($sasiedzi as $sasiad => $sim) {
        $ocena = $oceny[$sasiad][$produktIndex];

        if ($ocena !== null) {
            $licznik += $sim * $ocena;
            $mianownik += abs($sim);
        }
    }

    if ($mianownik == 0.0) {
        return null;
    }

    return $licznik / $mianownik;
}

function srednieProduktow(array $oceny, array $produkty): array
{
    $wyniki = [];

    for ($i = 0; $i < count($produkty); $i++) {
        $suma = 0.0;
        $licznik = 0;

        foreach ($oceny as $uzytkownik => $ratingi) {
            if ($ratingi[$i] !== null) {
                $suma += $ratingi[$i];
                $licznik++;
            }
        }

        if ($licznik > 0) {
            $wyniki[$produkty[$i]] = $suma / $licznik;
        }
    }

    arsort($wyniki);

    return $wyniki;
}

echo "Podobieństwo Pearsona dla Anny:\n";
$podobienstwa = podobienstwaDlaUzytkownika("Anna", $oceny);

foreach ($podobienstwa as $uzytkownik => $sim) {
    printf("  %-8s %.4f\n", $uzytkownik . ":", $sim);
}

$k = 3;
$sasiedzi = topK($podobienstwa, $k);

echo "\nk=$k sąsiedzi Anny: ";
$listaSasiadow = [];
foreach ($sasiedzi as $uzytkownik => $sim) {
    $listaSasiadow[] = $uzytkownik . "(" . number_format($sim, 4, '.', '') . ")";
}
echo implode(", ", $listaSasiadow) . "\n";

$rekomendacje = [];
$ocenyAnny = $oceny["Anna"];

for ($i = 0; $i < count($ocenyAnny); $i++) {
    if ($ocenyAnny[$i] === null) {
        $pred = przewidzOcene("Anna", $i, $oceny, $sasiedzi);

        if ($pred !== null) {
            $rekomendacje[] = [
                'produkt' => $produkty[$i],
                'ocena' => $pred
            ];
        }
    }
}

usort($rekomendacje, function ($a, $b) {
    return $b['ocena'] <=> $a['ocena'];
});

echo "\nRekomendacje dla Anny (produkty nieocenione):\n";
foreach ($rekomendacje as $i => $r) {
    printf("  %d. %-12s - przewidywana ocena: %.2f\n", $i + 1, $r['produkt'], $r['ocena']);
}

echo "\nZimny start (Hania, 1 ocena):\n";
echo "  Za mało wspólnych ocen z innymi użytkownikami - brak wiarygodnych korelacji.\n";
echo "  Strategia: rekomenduj najpopularniejsze produkty (najwyższa średnia ocen wśród wszystkich).\n";

$popularne = srednieProduktow($oceny, $produkty);

echo "  Przykładowo najpopularniejsze produkty:\n";
$topPopularne = array_slice($popularne, 0, 3, true);
$lp = 1;
foreach ($topPopularne as $produkt => $srednia) {
    printf("    %d. %-12s - średnia ocena: %.2f\n", $lp, $produkt, $srednia);
    $lp++;
}