<?php

function s_push(array &$stos, $val): void {
    array_splice($stos, count($stos), 0, [$val]);
}

function s_pop(array &$stos) {
    if (count($stos) === 0) {
        return null;
    }

    $top = $stos[count($stos) - 1];
    array_splice($stos, -1, 1);
    return $top;
}

function s_peek(array $stos) {
    if (count($stos) === 0) {
        return null;
    }

    return $stos[count($stos) - 1];
}

function walidujNawiasy(string $tekst): bool {
    $stos = [];
    $pary = [
        ')' => '(',
        ']' => '[',
        '}' => '{'
    ];

    $otwierajace = ['(', '[', '{'];
    $zamykajace = [')', ']', '}'];

    $znaki = str_split($tekst);

    foreach ($znaki as $znak) {
        if (in_array($znak, $otwierajace, true)) {
            s_push($stos, $znak);
        } elseif (in_array($znak, $zamykajace, true)) {
            if (count($stos) === 0) {
                return false;
            }

            $top = s_pop($stos);

            if ($top !== $pary[$znak]) {
                return false;
            }
        }
    }

    return count($stos) === 0;
}

function obliczONP(string $wyrazenie) {
    $stos = [];
    $tokeny = explode(' ', $wyrazenie);

    foreach ($tokeny as $token) {
        if (is_numeric($token)) {
            s_push($stos, (float)$token);
        } elseif (in_array($token, ['+', '-', '*', '/'], true)) {
            $b = s_pop($stos);
            $a = s_pop($stos);

            switch ($token) {
                case '+':
                    s_push($stos, $a + $b);
                    break;
                case '-':
                    s_push($stos, $a - $b);
                    break;
                case '*':
                    s_push($stos, $a * $b);
                    break;
                case '/':
                    s_push($stos, $a / $b);
                    break;
            }
        }
    }

    return s_pop($stos);
}

function formatujWynik($wynik): string {
    if ((int)$wynik == $wynik) {
        return (string)(int)$wynik;
    }

    return rtrim(rtrim(number_format($wynik, 1, '.', ''), '0'), '.');
}

$wyrazenia_ONP = [
    "5 2 + 3 *",
    "15 7 1 1 + - / 3 * 2 1 1 + + -",
    "4 13 5 / +",
    "2 3 + 4 * 5 -",
    "100 50 25 / -",
];

$napisy_nawiasy = [
    "[({()})]",
    "((())",
    "{[()]}",
    "([)]",
    "",
];

$bufor = array_fill(0, 5, null);
$pos = 0;

for ($i = 0; $i < count($wyrazenia_ONP); $i++) {
    $nawiasy = $napisy_nawiasy[$i];
    $wyrazenie = $wyrazenia_ONP[$i];

    $czyPoprawne = walidujNawiasy($nawiasy);
    $wynik = obliczONP($wyrazenie);

    $bufor[$pos % 5] = $wynik;
    $pos++;

    $status = $czyPoprawne ? "OK" : "BŁĄD";

    echo "[" . ($i + 1) . "] Nawiasy \"$nawiasy\": $status | ONP \"$wyrazenie\" = " . formatujWynik($wynik) . "\n";
}

$buforDoWypisania = [];
foreach ($bufor as $wartosc) {
    $buforDoWypisania[] = formatujWynik($wartosc);
}

echo "\nBufor cykliczny (ostatnie 5 wyników): [" . implode(", ", $buforDoWypisania) . "]\n";