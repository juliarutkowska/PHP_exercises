<?php
date_default_timezone_set('Europe/Warsaw');

$file = __DIR__ . '/tasks.json';
$uploadDir = __DIR__ . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

$tasks = json_decode(file_get_contents($file), true);
if (!is_array($tasks)) {
    $tasks = [];
}

$errors = [];
$success = '';
$info = '';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function saveTasks(string $file, array $tasks): void {
    file_put_contents(
            $file,
            json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
    );
}

function old(string $key, string $default = ''): string {
    return e($_POST[$key] ?? $default);
}

function oldSelected(string $key, string $value): string {
    return (($_POST[$key] ?? '') === $value) ? 'selected' : '';
}

function isOverdue(array $task): bool {
    if (($task['status'] ?? '') === 'Zakończone') {
        return false;
    }

    return strtotime($task['due_date']) < strtotime('today');
}

// Usuwanie jednego zadania
if (isset($_POST['delete_task'])) {
    $index = (int)($_POST['delete_index'] ?? -1);

    if (isset($tasks[$index])) {
        if (!empty($tasks[$index]['attachment'])) {
            $path = $GLOBALS['uploadDir'] . $tasks[$index]['attachment'];

            if (file_exists($path)) {
                unlink($path);
            }
        }

        array_splice($tasks, $index, 1);
        saveTasks($file, $tasks);
        $info = 'Zadanie zostało usunięte.';
    }
}

// Usuwanie wszystkich zadań
if (isset($_POST['clear_all'])) {
    foreach ($tasks as $task) {
        if (!empty($task['attachment'])) {
            $path = $uploadDir . $task['attachment'];

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    $tasks = [];
    saveTasks($file, $tasks);
    $info = 'Wszystkie zadania zostały usunięte.';
}

// Dodawanie nowego zadania
if (isset($_POST['save_task'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? '');

    if ($title === '') {
        $errors[] = 'Tytuł jest wymagany.';
    }

    if ($category === '') {
        $errors[] = 'Kategoria jest wymagana.';
    }

    if ($priority === '') {
        $errors[] = 'Priorytet jest wymagany.';
    }

    if ($dueDate === '') {
        $errors[] = 'Data wykonania jest wymagana.';
    }

    $attachmentName = '';
    $attachmentOriginal = '';

    if (empty($errors)) {
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $originalName = basename($_FILES['attachment']['name']);
            $tmp = $_FILES['attachment']['tmp_name'];

            $uniqueName = time() . '_' . uniqid() . '_' . $originalName;

            if (move_uploaded_file($tmp, $uploadDir . $uniqueName)) {
                $attachmentName = $uniqueName;
                $attachmentOriginal = $originalName;
            } else {
                $errors[] = 'Nie udało się przesłać załącznika.';
            }
        } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Wystąpił błąd podczas przesyłania pliku.';
        }
    }

    if (empty($errors)) {
        $tasks[] = [
                'title' => $title,
                'desc' => $desc,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'due_date' => $dueDate,
                'created_at' => time(),
                'attachment' => $attachmentName,
                'attachment_original' => $attachmentOriginal
        ];

        saveTasks($file, $tasks);

        $success = 'Zadanie zostało zapisane.';
        $_POST = [];
    }
}

// Statystyki
$stats = [
        'total' => count($tasks),
        'overdue' => 0,
        'priority' => [
                'Wysoki' => 0,
                'Średni' => 0,
                'Niski' => 0
        ],
        'status' => [
                'Nowe' => 0,
                'W toku' => 0,
                'Zakończone' => 0
        ]
];

foreach ($tasks as $task) {
    if (isOverdue($task)) {
        $stats['overdue']++;
    }

    if (isset($stats['priority'][$task['priority']])) {
        $stats['priority'][$task['priority']]++;
    }

    if (isset($stats['status'][$task['status']])) {
        $stats['status'][$task['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menedżer Zadań</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f6f8;
            font-family: Arial, Helvetica, sans-serif;
            color: #2f3f55;
        }

        .container {
            width: min(1320px, calc(100% - 32px));
            margin: 0 auto;
            padding-bottom: 40px;
        }

        header {
            text-align: center;
            padding-top: 10px;
        }

        header h1 {
            margin: 0;
            font-size: 54px;
            color: #314156;
        }

        .top-line {
            margin-top: 28px;
            height: 3px;
            background: #4e9af1;
        }

        main {
            margin-top: 36px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 36px;
            margin-bottom: 28px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        h2 {
            margin-top: 0;
            font-size: 32px;
            color: #314156;
        }

        .alert {
            padding: 18px 20px;
            border-left: 6px solid #d74d57;
            background: #f4d7d9;
            color: #7b2d32;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 18px;
        }

        .alert-success {
            background: #dff4e4;
            border-left-color: #3ca35c;
            color: #1f6d3a;
        }

        .alert-info {
            background: #ddeeff;
            border-left-color: #4e9af1;
            color: #245487;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .full {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #314156;
        }

        .required {
            color: #ef4444;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 2px solid #d8d8d8;
            border-radius: 7px;
            font-size: 18px;
            padding: 12px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #4e9af1;
            box-shadow: 0 0 0 4px rgba(78,154,241,0.15);
        }

        .buttons {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 14px;
        }

        button {
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 700;
            padding: 13px 20px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        .btn-submit {
            background: #4e9af1;
            color: white;
        }

        .btn-reset {
            background: #e4e7eb;
            color: #314156;
        }

        .btn-delete {
            background: #d74d57;
            color: white;
            padding: 9px 13px;
            font-size: 14px;
        }

        .btn-clear {
            background: transparent;
            border: 2px solid #d74d57;
            color: #d74d57;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-box {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #4e9af1;
        }

        .stat-list {
            line-height: 1.8;
        }

        .task-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        th {
            background: #4e9af1;
            color: white;
            text-align: left;
            padding: 13px;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #e9edf2;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }

        .overdue {
            color: #d74d57;
            font-weight: 800;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
        }

        .badge-high {
            background: #fde2e4;
            color: #b4232f;
        }

        .badge-medium {
            background: #fff3d6;
            color: #a46700;
        }

        .badge-low {
            background: #def7e5;
            color: #1f7a3d;
        }

        .empty {
            text-align: center;
            color: #7f8a96;
            font-size: 20px;
            padding: 30px;
        }

        footer {
            text-align: center;
            color: #6d7681;
            margin-top: 18px;
        }

        @media (max-width: 900px) {
            .form-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            header h1 {
                font-size: 34px;
            }

            .card {
                padding: 24px 16px;
            }

            .buttons {
                flex-direction: column;
            }

            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <header>
        <h1>Menedżer Zadań</h1>
        <div class="top-line"></div>
    </header>

    <main>

        <div class="card">
            <h2>Dodaj nowe zadanie</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert">
                    <strong>Popraw błędy:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label for="title">Tytuł <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?= old('title') ?>">
                </div>

                <div class="form-group">
                    <label for="category">Kategoria <span class="required">*</span></label>
                    <select id="category" name="category">
                        <option value="">Wybierz kategorię</option>
                        <option <?= oldSelected('category', 'Domowe') ?>>Domowe</option>
                        <option <?= oldSelected('category', 'Praca') ?>>Praca</option>
                        <option <?= oldSelected('category', 'Nauka') ?>>Nauka</option>
                        <option <?= oldSelected('category', 'Hobby') ?>>Hobby</option>
                        <option <?= oldSelected('category', 'Inne') ?>>Inne</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="desc">Opis</label>
                    <textarea id="desc" name="desc"><?= old('desc') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="priority">Priorytet <span class="required">*</span></label>
                    <select id="priority" name="priority">
                        <option value="">Wybierz priorytet</option>
                        <option <?= oldSelected('priority', 'Wysoki') ?>>Wysoki</option>
                        <option <?= oldSelected('priority', 'Średni') ?>>Średni</option>
                        <option <?= oldSelected('priority', 'Niski') ?>>Niski</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Wybierz status</option>
                        <option <?= oldSelected('status', 'Nowe') ?>>Nowe</option>
                        <option <?= oldSelected('status', 'W toku') ?>>W toku</option>
                        <option <?= oldSelected('status', 'Zakończone') ?>>Zakończone</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Data wykonania <span class="required">*</span></label>
                    <input type="date" id="due_date" name="due_date" value="<?= old('due_date') ?>">
                </div>

                <div class="form-group">
                    <label for="attachment">Załącznik</label>
                    <input type="file" id="attachment" name="attachment">
                </div>

                <div class="buttons">
                    <button type="reset" class="btn-reset">Wyczyść</button>
                    <button type="submit" name="save_task" class="btn-submit">Zapisz zadanie</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <h3>Łącznie zadań</h3>
                <div class="stat-number"><?= $stats['total'] ?></div>
            </div>

            <div class="stat-box">
                <h3>Po terminie</h3>
                <div class="stat-number"><?= $stats['overdue'] ?></div>
            </div>

            <div class="stat-box">
                <h3>Priorytet</h3>
                <div class="stat-list">
                    Wysoki: <strong><?= $stats['priority']['Wysoki'] ?></strong><br>
                    Średni: <strong><?= $stats['priority']['Średni'] ?></strong><br>
                    Niski: <strong><?= $stats['priority']['Niski'] ?></strong>
                </div>
            </div>

            <div class="stat-box">
                <h3>Status</h3>
                <div class="stat-list">
                    Nowe: <strong><?= $stats['status']['Nowe'] ?></strong><br>
                    W toku: <strong><?= $stats['status']['W toku'] ?></strong><br>
                    Zakończone: <strong><?= $stats['status']['Zakończone'] ?></strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="task-header-row">
                <h2>Lista zadań</h2>

                <?php if (!empty($tasks)): ?>
                    <form method="POST" action="index.php" onsubmit="return confirm('Czy na pewno usunąć wszystkie zadania?');">
                        <button type="submit" name="clear_all" class="btn-clear">Wyczyść wszystkie</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($info): ?>
                <div class="alert alert-info"><?= e($info) ?></div>
            <?php endif; ?>

            <?php if (empty($tasks)): ?>
                <div class="empty">Brak zadań. Dodaj pierwsze zadanie powyżej.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Tytuł</th>
                            <th>Kategoria</th>
                            <th>Priorytet</th>
                            <th>Status</th>
                            <th>Data wykonania</th>
                            <th>Utworzono</th>
                            <th>Załącznik</th>
                            <th>Akcja</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tasks as $index => $task): ?>
                            <?php
                            $overdue = isOverdue($task);

                            $badgeClass = '';
                            if ($task['priority'] === 'Wysoki') {
                                $badgeClass = 'badge-high';
                            } elseif ($task['priority'] === 'Średni') {
                                $badgeClass = 'badge-medium';
                            } elseif ($task['priority'] === 'Niski') {
                                $badgeClass = 'badge-low';
                            }
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= e($task['title']) ?></strong>
                                    <?php if (!empty($task['desc'])): ?>
                                        <br><small><?= e($task['desc']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($task['category']) ?></td>
                                <td><span class="badge <?= e($badgeClass) ?>"><?= e($task['priority']) ?></span></td>
                                <td><?= e($task['status']) ?></td>
                                <td class="<?= $overdue ? 'overdue' : '' ?>">
                                    <?= e($task['due_date']) ?>
                                    <?php if ($overdue): ?>
                                        <br>Po terminie!
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d.m.Y H:i', $task['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($task['attachment'])): ?>
                                        <a href="uploads/<?= e(rawurlencode($task['attachment'])) ?>" download>
                                            <?= e($task['attachment_original']) ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="index.php" onsubmit="return confirm('Usunąć to zadanie?');">
                                        <input type="hidden" name="delete_index" value="<?= $index ?>">
                                        <button type="submit" name="delete_task" class="btn-delete">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <footer>
        Menedżer Zadań — JSON + Upload
    </footer>

</div>
</body>
</html>