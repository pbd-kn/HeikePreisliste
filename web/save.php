<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

$columns = catalogColumns();
$message = '';
$isError = false;
$newId = null;
$globalParams = loadGlobalParams($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [];
        foreach ($columns as $column) {
            $data[$column] = trim($_POST[$column] ?? '');
        }

        if ($data['artikel_code'] === '') {
            throw new RuntimeException('artikel_code ist Pflicht.');
        }
        if ($data['zeit_in_h'] === '') {
            throw new RuntimeException('zeit_in_h ist Pflicht.');
        }

        $formulaRules = loadCatalogFormulaRules($pdo, $data['artikel_code']);
        $data = array_merge($data, calcCatalog($data, $globalParams, $formulaRules));
        $sql =
            "INSERT INTO catalog_items ("
            . implode(',', $columns)
            . ") VALUES ("
            . implode(',', array_fill(0, count($columns), '?'))
            . ")";
        $st = $pdo->prepare($sql);
        $st->execute(array_map(fn ($column) => $data[$column] ?? '', $columns));

        $newId = (int)$pdo->lastInsertId();
        $message = 'Datensatz gespeichert. ID: ' . $newId;
    } catch (PDOException $e) {
        $isError = true;
        if (($e->errorInfo[1] ?? 0) == 1062) {
            $message = 'Fehler: artikel_code bereits vorhanden.';
        } else {
            $message = 'DB-Fehler: ' . $e->getMessage();
        }
    } catch (Throwable $e) {
        $isError = true;
        $message = $e->getMessage();
    }
} else {
    $isError = true;
    $message = 'Diese Seite speichert nur gesendete Formulardaten. Bitte nutze "Neuen Satz anlegen".';
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Speichern</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
  <a class="btn btn-primary" href="catalog_create.php">Neuen Satz anlegen</a>
  <a class="btn btn-outline-secondary" href="catalog.php<?= $newId ? '#row-' . $newId : '' ?>">Zum Katalog</a>
</div>
</body>
</html>
