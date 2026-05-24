<?php

// Tablica dokumentów z treści zadania
$dokumenty = [
    0 => "PHP jest językiem skryptowym używanym do tworzenia stron internetowych",
    1 => "Tablice w PHP mogą być indeksowane lub asocjacyjne i bardzo przydatne",
    2 => "Funkcje array_map i array_filter ułatwiają przetwarzanie tablic w PHP",
    3 => "PHP obsługuje tablice wielowymiarowe i zagnieżdżone struktury danych",
    4 => "Serwer Apache współpracuje z PHP do obsługi żądań HTTP i połączeń",
    5 => "Bazy danych MySQL są często używane razem z PHP do przechowywania",
    6 => "Funkcja usort sortuje tablice w PHP według różnych kryteriów i warunków",
    7 => "JavaScript i PHP razem tworzą dynamiczne aplikacje internetowe i serwisy",
    8 => "PHP posiada wbudowane funkcje do pracy z plikami tablicami i bazami",
    9 => "Bezpieczeństwo aplikacji PHP wymaga walidacji danych wejściowych i filtrów",
];

// Lista stop-words, które pomijamy przy budowie indeksu
$stopWords = ['i', 'w', 'na', 'do', 'z', 'są', 'lub', 'być', 'może', 'jest', 'się'];

// Funkcja normalizuje tekst dokumentu:
// - zamienia litery na małe
// - usuwa znaki specjalne
// - dzieli tekst na słowa
// - usuwa stop-words
// - usuwa słowa krótsze niż 3 znaki
function normalizeText(string $text): array
{
    global $stopWords;

    // Zamiana na małe litery
    $text = mb_strtolower($text, 'UTF-8');

    // Zostawiamy tylko litery i spacje
    $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);

    // Dzielimy tekst na słowa po spacjach
    $words = preg_split('/\s+/', trim($text));

    $result = [];

    foreach ($words as $word) {
        // Pomijamy puste elementy
        if ($word === '') {
            continue;
        }

        // Pomijamy słowa krótsze niż 3 znaki
        if (mb_strlen($word, 'UTF-8') < 3) {
            continue;
        }

        // Pomijamy stop-words
        if (in_array($word, $stopWords, true)) {
            continue;
        }

        // Jeśli słowo przeszło wszystkie filtry, dodajemy je do wyniku
        $result[] = $word;
    }

    return $result;
}

// Funkcja buduje odwrócony indeks
// index[słowo][docId] = liczba wystąpień słowa w danym dokumencie
// Dodatkowo liczy globalną częstość słów w całym zbiorze
function buildIndex(array $documents): array
{
    $index = [];
    $globalCounts = [];

    foreach ($documents as $docId => $text) {
        // Normalizujemy tekst dokumentu
        $words = normalizeText($text);

        foreach ($words as $word) {
            // Jeśli słowo nie istnieje jeszcze w indeksie, tworzymy je
            if (!isset($index[$word])) {
                $index[$word] = [];
            }

            // Jeśli słowo nie wystąpiło jeszcze w tym dokumencie, ustawiamy licznik na 0
            if (!isset($index[$word][$docId])) {
                $index[$word][$docId] = 0;
            }

            // Zwiększamy liczbę wystąpień słowa w danym dokumencie
            $index[$word][$docId]++;

            // Liczymy też częstość globalną w całym zbiorze
            if (!isset($globalCounts[$word])) {
                $globalCounts[$word] = 0;
            }

            $globalCounts[$word]++;
        }
    }

    // Sortujemy globalne liczniki malejąco
    arsort($globalCounts);

    return [$index, $globalCounts];
}

// Funkcja wyszukuje dokumenty metodą AND
// Zwraca tylko dokumenty, które zawierają wszystkie słowa zapytania
function searchAND(array $queryWords, array $index): array
{
    $docLists = [];

    foreach ($queryWords as $word) {
        $word = mb_strtolower($word, 'UTF-8');

        // Jeśli któregoś słowa nie ma w indeksie, wynik jest pusty
        if (!isset($index[$word])) {
            return [];
        }

        // Pobieramy listę dokumentów zawierających dane słowo
        $docLists[] = array_keys($index[$word]);
    }

    // Część wspólna list dokumentów = dokumenty zawierające wszystkie słowa
    $matchingDocs = call_user_func_array('array_intersect', $docLists);

    $results = [];

    foreach ($matchingDocs as $docId) {
        $score = 0;
        $details = [];

        // Liczymy ranking TF jako sumę częstości słów zapytania
        foreach ($queryWords as $word) {
            $tf = $index[$word][$docId] ?? 0;
            $score += $tf;
            $details[$word] = $tf;
        }

        $results[] = [
            'doc_id' => $docId,
            'score' => $score,
            'details' => $details
        ];
    }

    // Sortujemy wyniki malejąco po score
    usort($results, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $results;
}

// Funkcja wyszukuje dokumenty metodą OR
// Zwraca dokumenty, które zawierają przynajmniej jedno słowo z zapytania
function searchOR(array $queryWords, array $index): array
{
    $docLists = [];

    foreach ($queryWords as $word) {
        $word = mb_strtolower($word, 'UTF-8');

        // Jeśli słowo istnieje w indeksie, pobieramy dokumenty z tym słowem
        if (isset($index[$word])) {
            $docLists[] = array_keys($index[$word]);
        }
    }

    // Jeśli nic nie znaleziono, zwracamy pusty wynik
    if (empty($docLists)) {
        return [];
    }

    // Łączymy listy dokumentów i usuwamy duplikaty
    $matchingDocs = array_unique(array_merge(...$docLists));

    $results = [];

    foreach ($matchingDocs as $docId) {
        $score = 0;
        $details = [];

        foreach ($queryWords as $word) {
            $word = mb_strtolower($word, 'UTF-8');
            $tf = $index[$word][$docId] ?? 0;

            // Jeśli słowo występuje, zapisujemy szczegóły
            if ($tf > 0) {
                $details[$word] = $tf;
            }

            // Dodajemy do łącznego rankingu
            $score += $tf;
        }

        $results[] = [
            'doc_id' => $docId,
            'score' => $score,
            'details' => $details
        ];
    }

    // Sortowanie wyników malejąco po score
    usort($results, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $results;
}

// Funkcja pomocnicza do ładnego wypisania szczegółów typu php:1, tablice:1
function formatDetails(array $details): string
{
    $parts = [];

    foreach ($details as $word => $count) {
        $parts[] = $word . ':' . $count;
    }

    return implode(', ', $parts);
}

// Budujemy indeks i liczniki globalne
[$index, $globalCounts] = buildIndex($dokumenty);

// ========================
// 1. Top 5 najczęstszych słów
// ========================

echo "Top 5 najczęstszych słów:\n";

// Pobieramy 5 pierwszych elementów z globalnych liczników
$top5 = array_slice($globalCounts, 0, 5, true);

foreach ($top5 as $word => $count) {
    echo "  '$word': {$count}x\n";
}


// 2. Wyszukiwanie AND
echo "\nWyniki dla (php AND tablice):\n";

$andResults = searchAND(['php', 'tablice'], $index);

if (empty($andResults)) {
    echo "  Brak wyników\n";
} else {
    foreach ($andResults as $i => $result) {
        $nr = $i + 1;
        echo "  {$nr}. Dokument ID:" . $result['doc_id']
            . " | Score:" . $result['score']
            . " (" . formatDetails($result['details']) . ")\n";
    }
}


// 3. Wyszukiwanie OR
echo "\nWyniki dla (mysql OR javascript):\n";

$orResults = searchOR(['mysql', 'javascript'], $index);

if (empty($orResults)) {
    echo "  Brak wyników\n";
} else {
    foreach ($orResults as $i => $result) {
        $nr = $i + 1;
        echo "  {$nr}. Dokument ID:" . $result['doc_id']
            . " | Score:" . $result['score']
            . " (" . formatDetails($result['details']) . ")\n";
    }
}