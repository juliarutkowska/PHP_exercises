<?php

$dane = [];
$historia = [];

function pokaz($dane) {
    echo "[" . implode(", ", $dane) . "]\n";
}

function stats($dane) {
    if (count($dane) === 0) {
        echo "Brak danych\n";
        return;
    }

    $suma = 0;
    $min = $dane[0];
    $max = $dane[0];

    foreach ($dane as $v) {
        $suma += $v;

        if ($v < $min) $min = $v;
        if ($v > $max) $max = $v;
    }

    $avg = $suma / count($dane);

    echo "Suma: $suma | Średnia: $avg | Min: $min | Max: $max\n";
}

function dodajHistorie(&$historia, $linia) {
    $historia[] = $linia;
    $historia = array_slice($historia, -10);
}

function help() {
    echo "Dostępne polecenia:\n";
    echo "push <v>, pop, insert <idx> <v>, delete <idx>\n";
    echo "sort, rsort, filter <op> <v>, unique, reverse\n";
    echo "chunk <n>, slice <od> <ile>, stats, show\n";
    echo "reset, save, history, help, exit\n";
}

while (true) {
    $linia = readline(">> ");

    if ($linia === false || trim($linia) === '') continue;

    $czesci = explode(' ', trim($linia), 3);
    $polecenie = strtolower($czesci[0]);

    switch ($polecenie) {

        case 'push':
            if (!isset($czesci[1])) {
                echo "Brak argumentu dla: push\n";
                break;
            }
            $dane[] = (float)$czesci[1];
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'pop':
            if (count($dane) === 0) {
                echo "Pusta tablica\n";
                break;
            }
            $val = array_pop($dane);
            echo "Usunięto: $val\n";
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'insert':
            if (!isset($czesci[1]) || !isset($czesci[2])) {
                echo "Brak argumentu dla: insert\n";
                break;
            }
            $idx = (int)$czesci[1];
            $val = (float)$czesci[2];
            array_splice($dane, $idx, 0, [$val]);
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'delete':
            if (!isset($czesci[1])) {
                echo "Brak argumentu dla: delete\n";
                break;
            }
            $idx = (int)$czesci[1];
            array_splice($dane, $idx, 1);
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'sort':
            sort($dane);
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'rsort':
            rsort($dane);
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'filter':
            if (!isset($czesci[1]) || !isset($czesci[2])) {
                echo "Brak argumentu dla: filter\n";
                break;
            }

            $op = $czesci[1];
            $val = (float)$czesci[2];

            $dane = array_values(array_filter($dane, function ($x) use ($op, $val) {
                switch ($op) {
                    case '>': return $x > $val;
                    case '<': return $x < $val;
                    case '>=': return $x >= $val;
                    case '<=': return $x <= $val;
                    case '==': return $x == $val;
                    default: return false;
                }
            }));

            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'unique':
            $dane = array_values(array_unique($dane));
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'reverse':
            $dane = array_reverse($dane);
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'chunk':
            if (!isset($czesci[1])) {
                echo "Brak argumentu dla: chunk\n";
                break;
            }
            $n = (int)$czesci[1];
            $chunks = array_chunk($dane, $n);

            foreach ($chunks as $i => $chunk) {
                echo "Chunk " . ($i + 1) . ": [" . implode(", ", $chunk) . "]\n";
            }
            dodajHistorie($historia, $linia);
            break;

        case 'slice':
            if (!isset($czesci[1]) || !isset($czesci[2])) {
                echo "Brak argumentu dla: slice\n";
                break;
            }
            $od = (int)$czesci[1];
            $ile = (int)$czesci[2];

            $slice = array_slice($dane, $od, $ile);
            echo "[" . implode(", ", $slice) . "]\n";
            dodajHistorie($historia, $linia);
            break;

        case 'stats':
            stats($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'show':
            pokaz($dane);
            dodajHistorie($historia, $linia);
            break;

        case 'reset':
            $dane = [];
            echo "Tablica wyczyszczona\n";
            dodajHistorie($historia, $linia);
            break;

        case 'save':
            echo json_encode(["dane" => $dane]) . "\n";
            dodajHistorie($historia, $linia);
            break;

        case 'history':
            foreach ($historia as $i => $cmd) {
                echo ($i + 1) . ": $cmd\n";
            }
            break;

        case 'help':
            help();
            break;

        case 'exit':
            echo "Do widzenia!\n";
            exit;

        default:
            echo "Nieznane polecenie: $polecenie\n";
    }
}