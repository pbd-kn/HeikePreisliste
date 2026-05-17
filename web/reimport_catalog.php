<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

ini_set('upload_max_filesize', ini_get('upload_max_filesize'));
ini_set('post_max_size', ini_get('post_max_size'));

$message = '';
$error = '';
$columns = catalogColumns();
$globalParams = loadGlobalParams($pdo);

function normalizeReimportHeader(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
    $value = str_replace([' ', '.', '/', '-', '(', ')', '*', '€'], '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
}

function buildHeaderMap(array $header, array $columns): array
{
    if (count($header) >= count($columns) - 4) {
        return array_combine($columns, array_keys($columns));
    }

    $normalizedHeader = array_map(fn ($v) => normalizeReimportHeader((string)$v), $header);
    $aliases = catalogColumnAliases();
    $map = [];

    foreach ($columns as $column) {
        $pos = array_search(normalizeReimportHeader($column), $normalizedHeader, true);
        if ($pos === false) {
            $aliasNames = array_keys($aliases, $column, true);
            foreach ($aliasNames as $aliasName) {
                $pos = array_search(normalizeReimportHeader($aliasName), $normalizedHeader, true);
                if ($pos !== false) {
                    break;
                }
            }
        }
        $map[$column] = ($pos === false) ? null : $pos;
    }

    return $map;
}

function matchingReimportHeaderCount(array $line, array $columns): int
{
    $aliases = catalogColumnAliases();
    $knownHeaders = array_map('normalizeReimportHeader', array_merge($columns, array_keys($aliases)));
    $normalizedLine = array_map(fn ($v) => normalizeReimportHeader((string)$v), $line);
    return count(array_intersect($normalizedLine, $knownHeaders));
}

function mapCsvLine(array $line, array $columns, ?array $headerMap): array
{
    $row = [];
    foreach ($columns as $idx => $column) {
        if ($headerMap !== null) {
            $pos = $headerMap[$column] ?? null;
            $row[$column] = ($pos === null) ? '' : trim((string)($line[$pos] ?? ''));
        } else {
            $row[$column] = trim((string)($line[$idx] ?? ''));
        }
    }
    return $row;
}

function applyReimportArticleCodeIndex(array $row, array &$usedArticleCodes): array
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fh = null;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS catalog_items_import LIKE catalog_items");
        if (!empty($_POST['truncate_import'])) {
            $pdo->exec("DELETE FROM catalog_items_import");
        }

        $uploadField = $APP_UPLOAD_FIELD ?? 'csv';
        $upload = $_FILES[$uploadField] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $code = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            throw new RuntimeException('CSV-Upload fehlgeschlagen (Feld: ' . $uploadField . ', ErrorCode: ' . $code . ').');
        }

        $fh = fopen($upload['tmp_name'], 'r');
        if (!$fh) {
            throw new RuntimeException('CSV konnte nicht geöffnet werden.');
        }

        $firstLine = fgetcsv($fh, 0, ';', '"', '\\');
        if ($firstLine === false) {
            throw new RuntimeException('CSV ist leer.');
        }

        $headerMode = $_POST['header_mode'] ?? 'auto';
        $headerMap = null;
        $isHeader = false;

        if ($headerMode === 'yes') {
            $headerMap = buildHeaderMap($firstLine, $columns);
            $isHeader = true;
        } elseif ($headerMode === 'auto') {
            $line = $firstLine;
            while ($line !== false) {
                if (matchingReimportHeaderCount($line, $columns) >= 5) {
                    $headerMap = buildHeaderMap($line, $columns);
                    $isHeader = true;
                    break;
                }

                $line = fgetcsv($fh, 0, ';', '"', '\\');
            }
        }

        if (!$isHeader) {
            rewind($fh);
        }

        $insertSql =
            "INSERT INTO catalog_items_import ("
            . implode(',', $columns)
            . ") VALUES ("
            . implode(',', array_fill(0, count($columns), '?'))
            . ")";
        $insert = $pdo->prepare($insertSql);

        $pdo->beginTransaction();
        $rows = 0;
        $usedArticleCodes = [];
        while (($line = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
            if (count(array_filter($line, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $row = mapCsvLine($line, $columns, $headerMap);
            if (trim((string)($row['artikel_code'] ?? '')) === '') {
                continue;
            }
            $row = applyReimportArticleCodeIndex($row, $usedArticleCodes);
            $formulaRules = loadCatalogFormulaRules($pdo, $row['artikel_code']);
            $row = array_merge($row, calcCatalog($row, $globalParams, $formulaRules));
            $insert->execute(array_map(fn ($column) => $row[$column] ?? '', $columns));
            $rows++;
        }

        fclose($fh);
        $fh = null;

        if (!empty($_POST['replace_live'])) {
            $pdo->exec("DELETE FROM catalog_items");
            $pdo->exec(
                "INSERT INTO catalog_items (" . implode(',', $columns) . ") "
                . "SELECT " . implode(',', $columns) . " FROM catalog_items_import"
            );
        }

        $pdo->commit();
        $message = "Import erfolgreich. Zeilen: {$rows}";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (is_resource($fh)) {
            fclose($fh);
        }
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Reimport ODS/CSV</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4>Reimport aus geänderter ODS/CSV</h4>
  <p class="text-muted">Unterstützt CSV mit Spaltennamen oder ohne Header. Upload-Feld: <code><?= htmlspecialchars($APP_UPLOAD_FIELD ?? 'csv') ?></code></p>
  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card card-body">
    <div class="mb-3">
      <label class="form-label">CSV-Datei</label>
      <input class="form-control" type="file" name="<?= htmlspecialchars($APP_UPLOAD_FIELD ?? 'csv') ?>" accept=".csv" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Header-Modus</label>
      <select class="form-select" name="header_mode">
        <option value="auto">Auto erkennen (empfohlen)</option>
        <option value="yes">CSV hat Header</option>
        <option value="no">CSV ohne Header (nur Reihenfolge)</option>
      </select>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="truncate_import" id="truncate_import" checked>
      <label class="form-check-label" for="truncate_import">Staging-Tabelle vorab leeren (catalog_items_import)</label>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="replace_live" id="replace_live">
      <label class="form-check-label" for="replace_live">Produktive Tabelle ersetzen (catalog_items)</label>
    </div>
    <button class="btn btn-primary">Import starten</button>
    <a class="btn btn-outline-secondary" href="index.php">Zurück</a>
  </form>
</div>
</body>
</html>
