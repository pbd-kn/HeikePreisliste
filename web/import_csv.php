<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

$columns = catalogColumns();
$message = '';
$isError = false;
$globalParams = loadGlobalParams($pdo);

function normalizeHeader(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
    $value = str_replace([' ', '.', '/', '-', '(', ')', '*', '€'], '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
}

function csvRowToCatalogData(array $line, array $columns, ?array $headerMap): array
{
    $data = [];
    foreach ($columns as $idx => $column) {
        if ($headerMap !== null) {
            $pos = $headerMap[$column] ?? null;
            $data[$column] = ($pos === null) ? '' : trim((string)($line[$pos] ?? ''));
        } else {
            $data[$column] = trim((string)($line[$idx] ?? ''));
        }
    }
    return $data;
}

function matchingHeaderCount(array $line, array $columns): int
{
    $aliases = catalogColumnAliases();
    $knownHeaders = array_map('normalizeHeader', array_merge($columns, array_keys($aliases)));
    $normalizedLine = array_map(fn ($v) => normalizeHeader((string)$v), $line);
    return count(array_intersect($normalizedLine, $knownHeaders));
}

function buildImportHeaderMap(array $header, array $columns): array
{
    if (count($header) >= count($columns) - 4) {
        return array_combine($columns, array_keys($columns));
    }

    $normalizedHeader = array_map(fn ($v) => normalizeHeader((string)$v), $header);
    $aliases = catalogColumnAliases();
    $headerMap = [];

    foreach ($columns as $column) {
        $pos = array_search(normalizeHeader($column), $normalizedHeader, true);
        if ($pos === false) {
            $aliasNames = array_keys($aliases, $column, true);
            foreach ($aliasNames as $aliasName) {
                $pos = array_search(normalizeHeader($aliasName), $normalizedHeader, true);
                if ($pos !== false) {
                    break;
                }
            }
        }
        $headerMap[$column] = ($pos === false) ? null : $pos;
    }

    return $headerMap;
}

function applyArticleCodeIndex(array $row, array &$usedArticleCodes): array
{
    $articleCode = trim((string)($row['artikel_code'] ?? ''));
    if ($articleCode === '') {
        return $row;
    }

    $candidate = $articleCode;
    $index = 1;
    while (isset($usedArticleCodes[$candidate])) {
        $index++;
        $candidate = $articleCode . '-' . $index;
    }

    $usedArticleCodes[$candidate] = true;
    $row['artikel_code'] = $candidate;
    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    try {
        if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload-Fehler.');
        }

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$handle) {
            throw new RuntimeException('Datei konnte nicht geöffnet werden.');
        }

        $firstLine = fgetcsv($handle, 0, ';', '"', '\\');
        if ($firstLine === false) {
            throw new RuntimeException('CSV ist leer.');
        }

        $headerMap = null;
        $hasHeader = false;
        $line = $firstLine;
        while ($line !== false) {
            if (matchingHeaderCount($line, $columns) >= 5) {
                $headerMap = buildImportHeaderMap($line, $columns);
                $hasHeader = true;
                break;
            }

            $line = fgetcsv($handle, 0, ';', '"', '\\');
        }

        if (!$hasHeader) {
            rewind($handle);
        }

        if (!empty($_POST['truncate'])) {
            $pdo->exec("DELETE FROM catalog_items");
        }

        $sql =
            "INSERT INTO catalog_items ("
            . implode(',', $columns)
            . ") VALUES ("
            . implode(',', array_fill(0, count($columns), '?'))
            . ")";
        $st = $pdo->prepare($sql);

        $imported = 0;
        $usedArticleCodes = [];
        while (($line = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if (count(array_filter($line, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $data = csvRowToCatalogData($line, $columns, $headerMap);
            if (trim((string)($data['artikel_code'] ?? '')) === '') {
                continue;
            }
            $data = applyArticleCodeIndex($data, $usedArticleCodes);
            $formulaRules = loadCatalogFormulaRules($pdo, $data['artikel_code']);
            $data = array_merge($data, calcCatalog($data, $globalParams, $formulaRules));
            $st->execute(array_map(fn ($c) => $data[$c] ?? '', $columns));
            $imported++;
        }

        fclose($handle);
        $message = "Import fertig. Zeilen importiert: {$imported}";
    } catch (Throwable $e) {
        $isError = true;
        $message = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>CSV Importer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4>CSV Import in catalog_items</h4>
  <?php if ($message): ?>
    <div class="alert alert-<?= $isError ? 'danger' : 'info' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card card-body">
    <div class="mb-3">
      <label class="form-label">CSV-Datei</label>
      <input type="file" name="csv" class="form-control" accept=".csv" required>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="truncate" id="truncate">
      <label class="form-check-label" for="truncate">Tabelle vorher leeren</label>
    </div>
    <button class="btn btn-primary">Import starten</button>
    <a class="btn btn-outline-secondary" href="catalog.php">Zum Katalog</a>
  </form>
</div>
</body>
</html>
