<?php

$zadania = [
    ["id"=>1,  "nazwa"=>"T01", "start"=>480,  "koniec"=>600],
    ["id"=>2,  "nazwa"=>"T02", "start"=>510,  "koniec"=>720],
    ["id"=>3,  "nazwa"=>"T03", "start"=>540,  "koniec"=>660],
    ["id"=>4,  "nazwa"=>"T04", "start"=>600,  "koniec"=>690],
    ["id"=>5,  "nazwa"=>"T05", "start"=>660,  "koniec"=>780],
    ["id"=>6,  "nazwa"=>"T06", "start"=>690,  "koniec"=>840],
    ["id"=>7,  "nazwa"=>"T07", "start"=>720,  "koniec"=>810],
    ["id"=>8,  "nazwa"=>"T08", "start"=>780,  "koniec"=>900],
    ["id"=>9,  "nazwa"=>"T09", "start"=>840,  "koniec"=>960],
    ["id"=>10, "nazwa"=>"T10", "start"=>480,  "koniec"=>540],
    ["id"=>11, "nazwa"=>"T11", "start"=>570,  "koniec"=>630],
    ["id"=>12, "nazwa"=>"T12", "start"=>750,  "koniec"=>870],
    ["id"=>13, "nazwa"=>"T13", "start"=>900,  "koniec"=>990],
    ["id"=>14, "nazwa"=>"T14", "start"=>495,  "koniec"=>555],
    ["id"=>15, "nazwa"=>"T15", "start"=>870,  "koniec"=>930],
];

function minutyNaCzas(int $m): string {
    $h = intdiv($m, 60);
    $min = $m % 60;
    return $h . ":" . str_pad((string)$min, 2, "0", STR_PAD_LEFT);
}

function formatujZadanie(array $z): string {
    return $z['nazwa'] . "(" . minutyNaCzas($z['start']) . "–" . minutyNaCzas($z['koniec']) . ")";
}

function koliduje(array $a, array $b): bool {
    return max($a['start'], $b['start']) < min($a['koniec'], $b['koniec']);
}

// 1. Jedna sala — algorytm zachłanny
$poKoncu = $zadania;
usort($poKoncu, function ($a, $b) {
    return $a['koniec'] <=> $b['koniec'];
});

$wybrane = [];
$ostatniKoniec = -1;

foreach ($poKoncu as $z) {
    if ($z['start'] >= $ostatniKoniec) {
        $wybrane[] = $z;
        $ostatniKoniec = $z['koniec'];
    }
}

echo "Algorytm zachłanny (jedna sala):\n";
echo "  Wybrane zadania (" . count($wybrane) . "): ";
echo implode(", ", array_column($wybrane, 'nazwa')) . "\n";

$decyzje = [];
foreach ($wybrane as $z) {
    $decyzje[] = formatujZadanie($z);
}
echo "  Kolejność decyzji: " . implode(" → ", $decyzje) . "\n\n";

// 2. Konflikty
$konflikty = [];

foreach ($zadania as $i => $a) {
    $licznik = 0;

    foreach ($zadania as $j => $b) {
        if ($i === $j) {
            continue;
        }

        if (koliduje($a, $b)) {
            $licznik++;
        }
    }

    $konflikty[] = [
        'nazwa' => $a['nazwa'],
        'liczba' => $licznik
    ];
}

usort($konflikty, function ($a, $b) {
    return $b['liczba'] <=> $a['liczba'];
});

$maxKolizji = $konflikty[0]['liczba'];
$najbardziej = array_filter($konflikty, function ($x) use ($maxKolizji) {
    return $x['liczba'] === $maxKolizji;
});

echo "Konflikty:\n";
foreach ($najbardziej as $k) {
    echo "  Najbardziej konfliktowe: " . $k['nazwa'] . " (" . $k['liczba'] . " kolizji z innymi zadaniami)\n";
}
echo "\n";

// 3. Minimalna liczba sal
$poStarcie = $zadania;
usort($poStarcie, function ($a, $b) {
    if ($a['start'] === $b['start']) {
        return $a['koniec'] <=> $b['koniec'];
    }
    return $a['start'] <=> $b['start'];
});

$sale = [];

foreach ($poStarcie as $z) {
    $przypisano = false;

    for ($i = 0; $i < count($sale); $i++) {
        $ostatnie = $sale[$i][count($sale[$i]) - 1];

        if ($ostatnie['koniec'] <= $z['start']) {
            $sale[$i][] = $z;
            $przypisano = true;
            break;
        }
    }

    if (!$przypisano) {
        $sale[] = [$z];
    }
}

echo "Minimalna liczba sal: " . count($sale) . "\n";

foreach ($sale as $nr => $listaZadan) {
    $opis = [];

    foreach ($listaZadan as $z) {
        $opis[] = formatujZadanie($z);
    }

    echo "  Sala " . ($nr + 1) . ": " . implode(", ", $opis) . "\n";
}