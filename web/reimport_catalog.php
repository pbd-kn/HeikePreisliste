<?php
require __DIR__ . '/config.php';

// Optional local overrides (alternative: php.ini)
ini_set('upload_max_filesize', ini_get('upload_max_filesize'));
ini_set('post_max_size', ini_get('post_max_size'));

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // DDL (CREATE TABLE) and TRUNCATE can auto-commit on MySQL, so keep them outside transaction
        $pdo->exec("CREATE TABLE IF NOT EXISTS catalog_items_import LIKE catalog_items");

        if (!empty($_POST['truncate_import'])) {
            $pdo->exec("DELETE FROM catalog_items_import");
        }

        $pdo->beginTransaction();

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

        $columns = [
            'materialpreis_metall','arbeitszeit','verlust','galvanik','furnituren_au_750_333','furnituren_ag_925','colorit','schnur',
            'verschluesse_gg_wg','verschluesse_925','verschluesse_edelstahl','stein_typ','stein_faktor','perle_typ','perle_faktor',
            'furnituren_wg','zusatz_q','fixkosten_r','fixkosten_s','sonstiges_t','reparaturen','reparaturpreis','kalkulation_w',
            'x_basis','y_aufgerundet','zwischenwert_z','aa_multiplikator','ab_vk_aufgerundet',
            'spalte_ac','spalte_ad','spalte_ae','spalte_af','spalte_ag','spalte_ah','spalte_ai','spalte_aj','spalte_ak','spalte_al','spalte_am','spalte_an'
        ];

        $ins = $pdo->prepare(
            "INSERT INTO catalog_items_import (" . implode(',', $columns) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")"
        );

        $rows = 0;

        $headerMode = ($_POST['header_mode'] ?? 'auto'); // auto|yes|no
        $firstLine = fgetcsv($fh, 0, ';', '"', '\\');
        if ($firstLine === false) {
            throw new RuntimeException('CSV ist leer.');
        }

        $normalizedCols = array_map('strtolower', $columns);
        $headerMap = null;

        $normalizedFirst = array_map(fn($v) => strtolower(trim((string)$v)), $firstLine);
        $matchingHeaders = count(array_intersect($normalizedFirst, $normalizedCols));
        $isHeader = ($headerMode === 'yes') || ($headerMode === 'auto' && $matchingHeaders >= 5);

        if ($isHeader) {
            $headerMap = [];
            foreach ($columns as $idx => $colName) {
                $pos = array_search(strtolower($colName), $normalizedFirst, true);
                $headerMap[$idx] = ($pos === false) ? null : $pos;
            }
        } else {
            // first line is data -> process it below with positional mapping
            $headerMap = null;
            rewind($fh);
        }

        while (($line = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
            if (count(array_filter($line, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            if ($headerMap !== null) {
                $row = [];
                foreach ($columns as $idx => $colName) {
                    $srcPos = $headerMap[$idx];
                    $row[] = ($srcPos === null) ? null : ($line[$srcPos] ?? null);
                }
            } else {
                $row = array_pad($line, count($columns), null);
                $row = array_slice($row, 0, count($columns));
            }

            $ins->execute($row);
            $rows++;
        }
        fclose($fh);

        if (!empty($_POST['replace_live'])) {
            $pdo->exec("DELETE FROM catalog_items");
            $pdo->exec("INSERT INTO catalog_items (" . implode(',', $columns) . ") SELECT " . implode(',', $columns) . " FROM catalog_items_import");
        }

        $pdo->commit();
        $message = "Import erfolgreich. Zeilen: {$rows}";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
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
  <p class="text-muted">Unterstützt jetzt CSV mit Header (Spaltennamen) oder ohne Header. Upload-Feld: <code><?= htmlspecialchars($APP_UPLOAD_FIELD ?? 'csv') ?></code></p>

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
      <input class="form-check-input" type="checkbox" name="replace_live" id="replace_live" checked>
      <label class="form-check-label" for="replace_live">Produktive Tabelle ersetzen (catalog_items)</label>
    </div>
    <button class="btn btn-primary">Import starten</button>
    <a class="btn btn-outline-secondary" href="index.php">Zurück</a>
  </form>
</div>
</body>
</html>
