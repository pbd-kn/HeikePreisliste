<?php
$links = [
    ['href' => 'globals.php', 'title' => 'Globale Variablen', 'desc' => 'Materialpreise, Faktoren und feste Werte zentral pflegen.'],
    ['href' => 'formula_rules.php', 'title' => 'Formelregeln', 'desc' => 'Default-Formeln und artikel_code-spezifische Regeln pflegen.'],
    ['href' => 'calc_explain.php', 'title' => 'Berechnung anzeigen', 'desc' => 'Artikel suchen und EK-/Preisformel nachvollziehen.'],
    ['href' => 'catalog.php', 'title' => 'Katalogansicht', 'desc' => 'Alle Katalogdaten anzeigen, filtern und Spalten wählen.'],
    ['href' => 'catalog_create.php', 'title' => 'Neuen Satz anlegen', 'desc' => 'Neuen Datensatz in catalog_items erfassen und berechnen.'],
    ['href'  => 'catalog_edit.php', 'title' => 'Satz ändern', 'desc'  => 'Vorhandene Datensätze laden und bearbeiten.'],
    ['href'  => 'catalog_delete.php', 'title' => 'Satz löschen', 'desc'  => 'Datensätze anhand ID oder artikel_code löschen.'],
    ['href' => 'list.php', 'title' => 'Liste (neueste 500)', 'desc' => 'Kompakte Übersicht der letzten Einträge.'],
    ['href' => 'print_catalog.php', 'title' => 'Druckansicht', 'desc' => 'Druckfreundliche Ansicht des Katalogs.'],
    ['href' => 'reimport_catalog.php', 'title' => 'Reimport ODS/CSV', 'desc' => 'Geänderte ODS als CSV erneut importieren (Staging + optional Live ersetzen).'],
    ['href' => 'catalog_columns_rename.sql', 'title' => 'SQL: Spalten umbenennen', 'desc' => 'SQL-Datei für phpMyAdmin (col_a..col_an -> neue Namen).']
];
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Preisliste – Start</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Preisliste / Catalog – Startmenü</h3>
  <p class="text-muted">Wähle die gewünschte Funktion:</p>

  <div class="row g-3">
    <?php foreach ($links as $item): ?>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($item['title']) ?></h5>
            <p class="card-text text-muted"><?= htmlspecialchars($item['desc']) ?></p>
            <a class="btn btn-primary" href="<?= htmlspecialchars($item['href']) ?>">Öffnen</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
