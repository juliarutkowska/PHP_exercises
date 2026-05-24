<?php

// =========================
// WŁASNA IMPLEMENTACJA STOSU
// =========================

// Funkcja dodaje element na szczyt stosu
// Nie używamy array_push(), zgodnie z treścią zadania
function s_push(array &$stos, $val): void {
    array_splice($stos, count($stos), 0, [$val]);
}

// Funkcja zdejmuje element ze szczytu stosu
// Nie używamy array_pop(), zgodnie z treścią zadania
function s_pop(array &$stos) {
    // Jeśli stos jest pusty, zwracamy null
    if (count($stos) === 0) {
        return null;
    }

    // Pobieramy ostatni element
    $top = $stos[count($stos) - 1];

    // Usuwamy ostatni element ze stosu
    array_splice($stos, -1, 1);

    return $top;
}

// Funkcja podejrzenia szczytu stosu bez usuwania
function s_peek(array $stos) {
    if (count($stos) === 0) {
        return null;
    }

    return $stos[count($stos) - 1];
}

// =========================
// WALIDACJA NAWIASÓW
// =========================

// Funkcja sprawdza, czy nawiasy w napisie są poprawnie zagnieżdżone
function walidujNawiasy(string $tekst): bool {
    $stos = [];

    // Tablica mówi, jaki nawias otwierający pasuje do zamykającego
    $pary = [
        ')' => '(',
        ']' => '[',
        '}' => '{'
    ];

    // Lista nawiasów otwierających i zamykających
    $otwierajace = ['(', '[', '{'];
    $zamykajace = [')', ']', '}'];

    // Rozbijamy napis na pojedyncze znaki
    $znaki = str_split($tekst);

    foreach ($znaki as $znak) {
        // Jeśli nawias otwierający, odkładamy go na stos
        if (in_array($znak, $otwierajace, true)) {
            s_push($stos, $znak);
        }
        // Jeśli nawias zamykający, sprawdzamy zgodność
        elseif (in_array($znak, $zamykajace, true)) {
            // Jeśli stos pusty, to znaczy, że zamknięcie nie ma pary
            if (count($stos) === 0) {
                return false;
            }

            // Zdejmujemy ostatni nawias otwierający
            $top = s_pop($stos);

            // Sprawdzamy, czy pasuje do zamykającego
            if ($top !== $pary[$znak]) {
                return false;
            }
        }
    }

    // Po przejściu całego tekstu stos musi być pusty
    return count($stos) === 0;
}

// =========================
// KALKULATOR ONP
// =========================

// Funkcja oblicza wynik wyrażenia w Odwrotnej Notacji Polskiej
function obliczONP(string $wyrazenie) {
    $stos = [];

    // Dzielimy wyrażenie na tokeny po spacjach
    $tokeny = explode(' ', $wyrazenie);

    foreach ($tokeny as $token) {
        // Jeśli token jest liczbą, wrzucamy ją na stos
        if (is_numeric($token)) {
            s_push($stos, (float)$token);
        }
        // Jeśli token jest operatorem, zdejmujemy 2 liczby i wykonujemy działanie
        elseif (in_array($token, ['+', '-', '*', '/'], true)) {
            // Bardzo ważna kolejność:
            // najpierw zdejmujemy prawy argument, potem lewy
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

    // Na końcu na stosie zostaje tylko wynik
    return s_pop($stos);
}

// =========================
// FORMATOWANIE WYNIKU
// =========================

// Funkcja ładnie wypisuje wynik:
// - jeśli liczba jest całkowita, wypisujemy bez .0
// - jeśli jest zmiennoprzecinkowa, zostawiamy np. 6.6
function formatujWynik($wynik): string {
    if ((int)$wynik == $wynik) {
        return (string)(int)$wynik;
    }

    return rtrim(rtrim(number_format($wynik, 1, '.', ''), '0'), '.');
}

// =========================
// DANE WEJŚCIOWE
// =========================

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

// =========================
// BUFOR CYKLICZNY
// =========================

// Tworzymy bufor o stałym rozmiarze 5
$bufor = array_fill(0, 5, null);

// Pozycja zapisu
$pos = 0;

// =========================
// GŁÓWNA PĘTLA PROGRAMU
// =========================

for ($i = 0; $i < count($wyrazenia_ONP); $i++) {
    $nawiasy = $napisy_nawiasy[$i];
    $wyrazenie = $wyrazenia_ONP[$i];

    // Sprawdzamy poprawność nawiasów
    $czyPoprawne = walidujNawiasy($nawiasy);

    // Obliczamy wartość wyrażenia ONP
    $wynik = obliczONP($wyrazenie);

    // Zapisujemy wynik do bufora cyklicznego
    $bufor[$pos % 5] = $wynik;
    $pos++;

    // Tekst do wypisania: OK albo BŁĄD
    $status = $czyPoprawne ? "OK" : "BŁĄD";

    echo "[" . ($i + 1) . "] Nawiasy \"$nawiasy\": $status | ONP \"$wyrazenie\" = " . formatujWynik($wynik) . "\n";
}


// WYPISANIE BUFORA

$buforDoWypisania = [];

foreach ($bufor as $wartosc) {
    $buforDoWypisania[] = formatujWynik($wartosc);
}

echo "\nBufor cykliczny (ostatnie 5 wyników): [" . implode(", ", $buforDoWypisania) . "]\n";