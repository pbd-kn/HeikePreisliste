<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

$inputFields = [
    'materialpreis_metall', 'arbeitszeit', 'verlust',
    'fixkosten_r', 'fixkosten_s', 'zusatz_q',
    'aa_multiplikator'
];

$optionalTextFields = [
    'galvanik', 'furnituren_au_750_333', 'furnituren_ag_925',
    'colorit', 'schnur', 'verschluesse_gg_wg', 'verschluesse_925',
    'verschluesse_edelstahl', 'stein_typ', 'stein_faktor',
    'perle_typ', 'perle_faktor', 'furnituren_wg',
    'sonstiges_t', 'reparaturen', 'reparaturpreis', 'kalkulation_w',
    'spalte_ac', 'spalte_ad', 'spalte_ae', 'spalte_af', 'spalte_ag',
    'spalte_ah', 'spalte_ai', 'spalte_aj', 'spalte_ak', 'spalte_al',
    'spalte_am', 'spalte_an'
];

$message = '';
$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach (array_merge($inputFields, $optionalTextFields) as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }

    $preview = calcCatalog($data);
    $data = array_merge($data, $preview);
    $data['spalte_ai'] = (string)$preview['spalte_ai'];
    $data['spalte_aj'] = (string)$preview['spalte_aj'];

    $insertCols = array_merge(
        $inputFields,
        $optionalTextFields,
        ['x_basis','y_aufgerundet','ab_vk_aufgerundet']
    );

    // aa_multiplikator wird als berechneter AA-Wert gespeichert, nicht als reiner Faktor
    $data['aa_multiplikator'] = $preview['aa_multiplikator'];

    $sql = "INSERT INTO catalog_items (" . implode(',', $insertCols) . ") VALUES (" . implode(',', array_fill(0, count($insertCols), '?')) . ")";
    $st = $pdo->prepare($sql);
    $st->execute(array_map(fn($c) => $data[$c] ?? '', $insertCols));

    $message = 'Datensatz gespeichert. ID: ' . $pdo->lastInsertId();
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Neuen Catalog-Satz anlegen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3">
  <h4>Neuen Satz in catalog_items anlegen</h4>
  <div class="mb-2">
    <a class="btn btn-outline-primary btn-sm" href="catalog.php">Katalog</a>
    <a class="btn btn-outline-secondary btn-sm" href="list.php">Liste</a>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <?php if ($preview): ?>
    <div class="alert alert-info">
      Berechnet (wie ODS-abhängige Felder):
      X = <b><?= number_format((float)$preview['x_basis'], 2, ',', '.') ?></b>,
      Y = <b><?= number_format((float)$preview['y_aufgerundet'], 2, ',', '.') ?></b>,
      AA = <b><?= number_format((float)$preview['aa_multiplikator'], 2, ',', '.') ?></b>,
      AB = <b><?= number_format((float)$preview['ab_vk_aufgerundet'], 2, ',', '.') ?></b>,
      AI = <b><?= number_format((float)$preview['spalte_ai'], 2, ',', '.') ?></b>,
      AJ = <b><?= number_format((float)$preview['spalte_aj'], 2, ',', '.') ?></b>
    </div>
  <?php endif; ?>

  <form method="post" class="card card-body">
    <h6>Pflichteingaben (werden für abhängige Spalten berechnet)</h6>
    <div class="row g-2 mb-3">
      <?php foreach ($inputFields as $f): ?>
        <div class="col-md-4">
          <label class="form-label"><?= htmlspecialchars($f) ?></label>
          <input class="form-control" name="<?= htmlspecialchars($f) ?>" value="<?= htmlspecialchars($_POST[$f] ?? '') ?>" required>
        </div>
      <?php endforeach; ?>
    </div>

    <h6>Optionale Zusatzfelder</h6>
    <div class="row g-2">
      <?php foreach ($optionalTextFields as $f): ?>
        <div class="col-md-6">
          <label class="form-label"><?= htmlspecialchars($f) ?></label>
          <input class="form-control" name="<?= htmlspecialchars($f) ?>" value="<?= htmlspecialchars($_POST[$f] ?? '') ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Speichern + Berechnen</button>
    </div>
  </form>
</div>
</body>
</html>
