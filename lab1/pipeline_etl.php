<?php

$rekordy = [
    ["id"=>1,  "imie"=>"anna",    "wiek"=>"25",  "email"=>"anna@test.com",   "wynik"=>92.5],
    ["id"=>2,  "imie"=>"Bartosz", "wiek"=>"abc", "email"=>"bartosz@test.com","wynik"=>78.0],
    ["id"=>3,  "imie"=>"celina",  "wiek"=>"31",  "email"=>"celina@test.com", "wynik"=>105.0],
    ["id"=>4,  "imie"=>"Dawid",   "wiek"=>"45",  "email"=>"",                "wynik"=>66.5],
    ["id"=>5,  "imie"=>"EWA",     "wiek"=>"28",  "email"=>"ewa@test.com",    "wynik"=>88.0],
    ["id"=>6,  "imie"=>"filip",   "wiek"=>"130", "email"=>"filip@test.com",  "wynik"=>74.0],
    ["id"=>7,  "imie"=>"Grażyna", "wiek"=>"52",  "email"=>"anna@test.com",   "wynik"=>91.0],
    ["id"=>8,  "imie"=>"Henryk",  "wiek"=>"19",  "email"=>"henryk@test.com", "wynik"=>-5.0],
    ["id"=>9,  "imie"=>"irena",   "wiek"=>"37",  "email"=>"irena@test.com",  "wynik"=>83.5],
    ["id"=>10, "imie"=>"JANEK",   "wiek"=>"22",  "email"=>"janek@test.com",  "wynik"=>55.0],
    ["id"=>11, "imie"=>"Kasia",   "wiek"=>"29",  "email"=>"kasia@test.com",  "wynik"=>97.0],
    ["id"=>12, "imie"=>"Leon",    "wiek"=>"41",  "email"=>"leon@test.com",   "wynik"=>62.0],
    ["id"=>13, "imie"=>"Marta",   "wiek"=>"0",   "email"=>"marta@test.com",  "wynik"=>79.5],
    ["id"=>14, "imie"=>"norbert", "wiek"=>"33",  "email"=>"norbert@test.com","wynik"=>86.0],
    ["id"=>15, "imie"=>"Ola",     "wiek"=>"26",  "email"=>"ola@test.com",    "wynik"=>91.0],
];

function waliduj(array $dane): array
{
    $valid = [];
    $rejected = [];

    foreach ($dane as $rekord) {
        $wiekRaw = $rekord['wiek'];
        $emailRaw = trim($rekord['email']);
        $wynikRaw = $rekord['wynik'];

        if (filter_var($wiekRaw, FILTER_VALIDATE_INT) === false) {
            $rekord['powod'] = "nieprawidłowy wiek '{$wiekRaw}'";
            $rejected[] = $rekord;
            continue;
        }

        $wiek = (int)$wiekRaw;
        if ($wiek < 1 || $wiek > 120) {
            $rekord['powod'] = "nieprawidłowy wiek '{$wiekRaw}'";
            $rejected[] = $rekord;
            continue;
        }

        if (!is_numeric($wynikRaw) || $wynikRaw < 0.0 || $wynikRaw > 100.0) {
            $rekord['powod'] = "wynik poza zakresem [0–100]: {$wynikRaw}";
            $rejected[] = $rekord;
            continue;
        }

        if ($emailRaw === '') {
            $rekord['powod'] = "pusty email";
            $rejected[] = $rekord;
            continue;
        }

        $valid[] = $rekord;
    }

    return [
        'valid' => $valid,
        'rejected' => $rejected,
    ];
}

function transformuj(array $dane): array
{
    $wynik = [];
    $seenEmails = [];

    foreach ($dane as $rekord) {
        $email = trim($rekord['email']);

        if (isset($seenEmails[$email])) {
            continue;
        }

        $seenEmails[$email] = true;

        $nowy = $rekord;
        $nowy['imie'] = ucfirst(strtolower($rekord['imie']));
        $nowy['wiek'] = (int)$rekord['wiek'];
        $nowy['wynik'] = (float)$rekord['wynik'];
        $nowy['email'] = $email;

        $wynik[] = $nowy;
    }

    return $wynik;
}

function znajdzDuplikaty(array $dane): array
{
    $seen = [];
    $duplikaty = [];

    foreach ($dane as $rekord) {
        $email = trim($rekord['email']);

        if ($email === '') {
            continue;
        }

        if (isset($seen[$email])) {
            $rekord['powod'] = "duplikat email '{$email}'";
            $duplikaty[] = $rekord;
        } else {
            $seen[$email] = true;
        }
    }

    return $duplikaty;
}

function ocenaLiterowa(float $wynik): string
{
    if ($wynik >= 90) {
        return 'A';
    }
    if ($wynik >= 75) {
        return 'B';
    }
    if ($wynik >= 60) {
        return 'C';
    }
    return 'D';
}

$etapE = waliduj($rekordy);
$validPoWalidacji = $etapE['valid'];
$odrzucone = $etapE['rejected'];

$duplikaty = znajdzDuplikaty($validPoWalidacji);
$odrzucone = array_merge($odrzucone, $duplikaty);

$finalnaBaza = transformuj($validPoWalidacji);

echo "=== Etap E: Walidacja ===\n";
echo "Odrzucone rekordy (" . count($odrzucone) . "):\n";

foreach ($odrzucone as $r) {
    printf(
        "  - ID %-2d (%s): %s\n",
        $r['id'],
        $r['imie'],
        $r['powod']
    );
}

echo "\n=== Etap L: Finalna baza (" . count($finalnaBaza) . " rekordów) ===\n";
echo "Imię         | Wiek | Email                     | Wynik | Ocena\n";
echo "-----------------------------------------------------------------\n";

$statystyki = [
    'A' => ['count' => 0, 'sum' => 0.0],
    'B' => ['count' => 0, 'sum' => 0.0],
    'C' => ['count' => 0, 'sum' => 0.0],
    'D' => ['count' => 0, 'sum' => 0.0],
];

foreach ($finalnaBaza as $r) {
    $ocena = ocenaLiterowa($r['wynik']);

    printf(
        "%-12s | %4d | %-25s | %5.1f | %s\n",
        $r['imie'],
        $r['wiek'],
        $r['email'],
        $r['wynik'],
        $ocena
    );

    $statystyki[$ocena]['count']++;
    $statystyki[$ocena]['sum'] += $r['wynik'];
}

echo "\nRozkład ocen:\n";

foreach ($statystyki as $litera => $dane) {
    if ($dane['count'] > 0) {
        $srednia = $dane['sum'] / $dane['count'];
        printf(
            "  %s: %d studentów, średnia: %.1f%%\n",
            $litera,
            $dane['count'],
            $srednia
        );
    }
}