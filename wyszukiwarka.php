<?php

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

$stopWords = ['i', 'w', 'na', 'do', 'z', 'są', 'lub', 'być', 'może', 'jest', 'się'];

function normalizeText(string $text): array
{
    global $stopWords;

    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);
    $words = preg_split('/\s+/', trim($text));

    $result = [];

    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }

        if (mb_strlen($word, 'UTF-8') < 3) {
            continue;
        }

        if (in_array($word, $stopWords, true)) {
            continue;
        }

        $result[] = $word;
    }

    return $result;
}

function buildIndex(array $documents): array
{
    $index = [];
    $globalCounts = [];

    foreach ($documents as $docId => $text) {
        $words = normalizeText($text);

        foreach ($words as $word) {
            if (!isset($index[$word])) {
                $index[$word] = [];
            }

            if (!isset($index[$word][$docId])) {
                $index[$word][$docId] = 0;
            }

            $index[$word][$docId]++;

            if (!isset($globalCounts[$word])) {
                $globalCounts[$word] = 0;
            }

            $globalCounts[$word]++;
        }
    }

    arsort($globalCounts);

    return [$index, $globalCounts];
}

function searchAND(array $queryWords, array $index): array
{
    $docLists = [];

    foreach ($queryWords as $word) {
        $word = mb_strtolower($word, 'UTF-8');

        if (!isset($index[$word])) {
            return [];
        }

        $docLists[] = array_keys($index[$word]);
    }

    $matchingDocs = call_user_func_array('array_intersect', $docLists);

    $results = [];

    foreach ($matchingDocs as $docId) {
        $score = 0;
        $details = [];

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

    usort($results, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $results;
}

function searchOR(array $queryWords, array $index): array
{
    $docLists = [];

    foreach ($queryWords as $word) {
        $word = mb_strtolower($word, 'UTF-8');

        if (isset($index[$word])) {
            $docLists[] = array_keys($index[$word]);
        }
    }

    if (empty($docLists)) {
        return [];
    }

    $matchingDocs = array_unique(array_merge(...$docLists));

    $results = [];

    foreach ($matchingDocs as $docId) {
        $score = 0;
        $details = [];

        foreach ($queryWords as $word) {
            $word = mb_strtolower($word, 'UTF-8');
            $tf = $index[$word][$docId] ?? 0;

            if ($tf > 0) {
                $details[$word] = $tf;
            }

            $score += $tf;
        }

        $results[] = [
            'doc_id' => $docId,
            'score' => $score,
            'details' => $details
        ];
    }

    usort($results, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $results;
}

function formatDetails(array $details): string
{
    $parts = [];

    foreach ($details as $word => $count) {
        $parts[] = $word . ':' . $count;
    }

    return implode(', ', $parts);
}

[$index, $globalCounts] = buildIndex($dokumenty);

echo "Top 5 najczęstszych słów:\n";

$top5 = array_slice($globalCounts, 0, 5, true);
foreach ($top5 as $word => $count) {
    echo "  '$word': {$count}x\n";
}

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