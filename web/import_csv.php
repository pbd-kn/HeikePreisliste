<?php
require 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$cols = [
 'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t',
 'u','v','w','x','y','z','aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an'
];
$maxCols = count($cols); // 40

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $msg = "Upload-Fehler.";
    } else {
        $tmp = $_FILES['csv']['tmp_name'];

        // Optional: vorher leeren
        if (!empty($_POST['truncate'])) {
            $pdo->exec("TRUNCATE TABLE catalog_items");
        }

        $handle = fopen($tmp, 'r');
        if (!$handle) {
            $msg = "Datei konnte nicht geöffnet werden.";
        } else {
            // UTF-8 BOM entfernen (falls vorhanden)
            $firstBytes = fread($handle, 3);
            if ($firstBytes !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $insertCols = array_map(fn($c) => "col_$c", $cols);
            $sql = "INSERT INTO catalog_items (" . implode(',', $insertCols) . ", source_row) VALUES (" .
                   implode(',', array_fill(0, $maxCols + 1, '?')) . ")";
            $st = $pdo->prepare($sql);

            $rowNum = 0;
            $ok = 0;

            while (($row = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {
                $rowNum++;

                // Optional: leere Zeilen überspringen
                $allEmpty = true;
                foreach ($row as $v) {
                    if (trim((string)$v) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) continue;

                // auf 40 Spalten normalisieren
                if (count($row) < $maxCols) {
                    $row = array_pad($row, $maxCols, null);
                } elseif (count($row) > $maxCols) {
                    $row = array_slice($row, 0, $maxCols);
                }

                $params = $row;
                $params[] = $rowNum; // source_row
                $st->execute($params);
                $ok++;
            }

            fclose($handle);
            $msg = "Import fertig. Zeilen importiert: $ok";
        }
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
  <h4>CSV Import in catalog_items (robust)</h4>
  <?php if ($msg): ?>
    <div class="alert alert-info"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card card-body">
    <div class="mb-3">
      <label class="form-label">CSV-Datei</label>
      <input type="file" name="csv" class="form-control" accept=".csv" required>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="truncate" id="truncate" checked>
      <label class="form-check-label" for="truncate">Tabelle vorher leeren (TRUNCATE)</label>
    </div>
    <button class="btn btn-primary">Import starten</button>
    <a class="btn btn-outline-secondary" href="catalog.php">Zum Katalog</a>
  </form>
</div>
</body>
</html>