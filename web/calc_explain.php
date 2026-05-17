<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

$id = (int)($_GET['id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$row = null;
$searchResults = [];
$message = '';

if ($id > 0) {
    $st = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $message = 'Datensatz nicht gefunden.';
    }
} elseif ($q !== '') {
    $st = $pdo->prepare("
        SELECT id, artikel_code, artikel, kategorie, subkategorie, beschreibung
        FROM catalog_items
        WHERE artikel_code LIKE ?
        ORDER BY artikel_code, id
        LIMIT 50
    ");
    $st->execute(['%' . $q . '%']);
    $searchResults = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$searchResults) {
        $message = 'Keine Treffer gefunden.';
    }
}

$globalParams = $row ? loadGlobalParams($pdo) : [];
$formulaRules = $row ? loadCatalogFormulaRules($pdo, $row['artikel_code']) : [];
$parts = $row ? configuredCatalogCalculationParts($row, $globalParams, $formulaRules) : [];
$calc = $row ? calcCatalog($row, $globalParams, $formulaRules) : [];
$priceSteps = [];
$partSteps = [];
if ($row) {
    $globalMetaRows = $pdo
        ->query("SELECT param_key, label, unit FROM global_params")
        ->fetchAll(PDO::FETCH_ASSOC);
    $globalMeta = [];
    foreach ($globalMetaRows as $globalMetaRow) {
        $globalMeta[(string)$globalMetaRow['param_key']] = $globalMetaRow;
    }
    $describeGlobal = function (string $key) use ($globalMeta): string {
        $meta = $globalMeta[$key] ?? null;
        if (!$meta) {
            return $key;
        }
        $unit = trim((string)($meta['unit'] ?? ''));
        return $key . ' ' . $meta['label'] . ($unit !== '' ? ' (' . $unit . ')' : '');
    };

    $vars = array_merge(catalogFormulaVars($row, $globalParams), $parts);
    foreach (catalogCalculationParts($row, $globalParams) as $field => $value) {
        $vars['default_' . $field] = $value;
    }

    $ekRaw = evalCatalogFormula($formulaRules['ek'], $vars);
    $priceStueckEk = ceil($ekRaw);
    $vars['ek'] = $ekRaw;
    $vars['default_ek'] = $ekRaw;
    $vars['preis_stueck_ek'] = $priceStueckEk;
    $vars['default_preis_stueck_ek'] = $priceStueckEk;

    $factor25 = evalCatalogFormula($formulaRules['factor_25'], $vars);
    $vk25 = $priceStueckEk * $factor25;
    $preis25 = ceil($vk25);
    $vars['factor_25'] = $factor25;
    $vars['vkstk_ek_2_5_ungerundet'] = $vk25;
    $vars['default_vkstk_ek_2_5_ungerundet'] = $vk25;
    $vars['preis_stueck_2_5'] = $preis25;
    $vars['default_preis_stueck_2_5'] = $preis25;

    $factor23 = evalCatalogFormula($formulaRules['factor_23'], $vars);
    $vk23 = $priceStueckEk * $factor23;
    $preis23 = ceil($vk23);

    $priceSteps = [
        ['ek', $formulaRules['ek'], $ekRaw],
        ['preis_stueck_ek', 'ceil(ek)', $priceStueckEk],
        ['factor_25', $formulaRules['factor_25'], $factor25],
        ['vkstk_ek_2_5_ungerundet', 'preis_stueck_ek * factor_25 = ' . number_format($priceStueckEk, 2, '.', '') . ' * ' . number_format($factor25, 2, '.', ''), $vk25],
        ['preis_stueck_2_5', 'ceil(vkstk_ek_2_5_ungerundet)', $preis25],
        ['preis_paar_2_5', $formulaRules['preis_paar_2_5'] ?? 'preis_stueck_2_5 * 2', $calc['preis_paar_2_5']],
        ['factor_23', $formulaRules['factor_23'], $factor23],
        ['vkstk_ek_2_3_ungerundet', 'preis_stueck_ek * factor_23 = ' . number_format($priceStueckEk, 2, '.', '') . ' * ' . number_format($factor23, 2, '.', ''), $vk23],
        ['preis_stueck_2_3', 'ceil(vkstk_ek_2_3_ungerundet)', $preis23],
        ['preis_paar_2_3', $formulaRules['preis_paar_2_3'] ?? 'preis_stueck_2_3 * 2', $calc['preis_paar_2_3']]
    ];

    $agRate = globalParam($globalParams, 'A3');
    $auRate = globalParam($globalParams, 'A5');
    $wgRate = globalParam($globalParams, 'A7');
    $articleCodeUpper = strtoupper((string)$row['artikel_code']);
    $isGold = $parts['au_incl_verlust'] > 0;
    $isWg = $isGold && str_contains($articleCodeUpper, 'WG');
    $materialRateKey = $isWg ? 'A7' : ($isGold ? 'A5' : 'A3');
    $materialWeight = $isGold ? $parts['au_incl_verlust'] : $parts['ag_incl_verlust'];
    $materialRate = $isWg ? $wgRate : ($isGold ? $auRate : $agRate);
    $isOhrstecker = strtolower((string)($row['kategorie'] ?? '')) === 'ohrstecker';
    $stueck1 = parseFormulaOrEur($row['stueck_1'] ?? '0', $vars);
    $steineMesseRaw = trim((string)($row['steine_messe'] ?? ''));
    $steineMesse = $steineMesseRaw !== ''
        ? parseFormulaOrEur($steineMesseRaw, $vars)
        : parseFormulaOrEur($row['steine_perlen_ek'] ?? '0', $vars) * 1.5;
    $steineMesseText = $steineMesseRaw !== ''
        ? 'steine_messe'
        : 'steine_perlen_ek * 1.5';

    $stueck2 = parseFormulaOrEur($row['stueck_2'] ?? '0', $vars);
    $steineMesse2Raw = trim((string)($row['steine_messe_2'] ?? ''));
    $steineMesse2 = $steineMesse2Raw !== ''
        ? parseFormulaOrEur($steineMesse2Raw, $vars)
        : parseFormulaOrEur($row['furnituren_steine_ek'] ?? '0', $vars) * 1.5;
    $steineMesse2Text = $steineMesse2Raw !== ''
        ? 'steine_messe_2'
        : 'furnituren_steine_ek * 1.5';

    $partSteps = [
        ['ag_incl_verlust', 'ag_in_g * (1 + ' . $describeGlobal('C3') . ') = ' . number_format(parseEur($row['ag_in_g'] ?? '0'), 2, '.', '') . ' * (1 + ' . number_format(globalParam($globalParams, 'C3'), 4, '.', '') . ')', $parts['ag_incl_verlust']],
        ['au_incl_verlust', 'au_in_g * (1 + ' . $describeGlobal('C5') . ') = ' . number_format(parseEur($row['au_in_g'] ?? '0'), 2, '.', '') . ' * (1 + ' . number_format(globalParam($globalParams, 'C5'), 4, '.', '') . ')', $parts['au_incl_verlust']],
        ['material', $materialWeight > 0 ? $describeGlobal($materialRateKey) . ': ' . number_format($materialWeight, 2, '.', '') . ' * ' . number_format($materialRate, 4, '.', '') : 'kein Materialgewicht', $parts['material']],
        ['arbeitszeit', 'zeit_in_h * ' . $describeGlobal('B3') . ' = ' . number_format(parseWorkHours((string)($row['zeit_in_h'] ?? '0')), 2, '.', '') . ' * ' . number_format(globalParam($globalParams, 'B3'), 4, '.', ''), $parts['arbeitszeit']],
        ['stueck_1_messe', 'stueck_1 * ' . $steineMesseText . ' = ' . number_format($stueck1, 2, '.', '') . ' * ' . number_format($steineMesse, 2, '.', ''), $parts['stueck_1_messe']],
        ['stueck_2_messe', 'stueck_2 * ' . $steineMesse2Text . ' = ' . number_format($stueck2, 2, '.', '') . ' * ' . number_format($steineMesse2, 2, '.', ''), $parts['stueck_2_messe']],
        ['plattierung_oxidation', 'Artikelfeld plattierung_oxidation = ' . number_format(parseFormulaOrEur($row['plattierung_oxidation'] ?? '0', $vars), 2, '.', ''), $parts['plattierung_oxidation']],
        ['schnur_2', 'Artikelfeld schnur_2 = ' . number_format(parseFormulaOrEur($row['schnur_2'] ?? '0', $vars), 2, '.', ''), $parts['schnur_2']],
        ['fixkosten_r5', (!$isGold && !$isOhrstecker) ? $describeGlobal('R5') . ' = ' . number_format(globalParam($globalParams, 'R5'), 4, '.', '') : '0, weil Gold oder Ohrstecker', $parts['fixkosten_r5']],
        ['fixkosten_s', $isGold ? $describeGlobal('S9') . ' = ' . number_format(globalParam($globalParams, 'S9'), 4, '.', '') : ($isOhrstecker ? $describeGlobal('R3') . ' + ' . $describeGlobal('S3') . ' = ' . number_format(globalParam($globalParams, 'R3'), 4, '.', '') . ' + ' . number_format(globalParam($globalParams, 'S3'), 4, '.', '') : '0'), $parts['fixkosten_s']],
        ['metall_zuschlag_s', (!$isGold && !$isOhrstecker) ? 'ag_incl_verlust * ' . $describeGlobal('S7') . ' = ' . number_format($parts['ag_incl_verlust'], 2, '.', '') . ' * ' . number_format(globalParam($globalParams, 'S7'), 4, '.', '') : ($isGold && str_contains($articleCodeUpper, 'AH8') ? 'au_incl_verlust * ' . $describeGlobal('S11') : '0'), $parts['metall_zuschlag_s']]
    ];
}
$formulaFields = [
    'ag_incl_verlust',
    'au_incl_verlust',
    'material',
    'arbeitszeit',
    'stueck_1_messe',
    'stueck_2_messe',
    'plattierung_oxidation',
    'schnur_2',
    'fixkosten_r5',
    'fixkosten_s',
    'metall_zuschlag_s'
];
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Berechnung anzeigen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    details.calc-section summary {
      cursor: pointer;
      list-style: none;
    }
    details.calc-section summary::-webkit-details-marker {
      display: none;
    }
    details.calc-section summary::after {
      content: "v";
      float: right;
      color: #0d6efd;
      font-size: 1rem;
      font-weight: 700;
    }
    details.calc-section[open] summary::after {
      content: "^";
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <h3>Berechnung anzeigen</h3>
  <div class="mb-3">
    <a class="btn btn-outline-primary btn-sm" href="index.php">Startmenue</a>
    <?php if ($row): ?>
      <a class="btn btn-outline-secondary btn-sm" href="catalog_edit.php?id=<?= (int)$row['id'] ?>">Zum Datensatz</a>
      <a class="btn btn-outline-secondary btn-sm" href="formula_rules.php?artikel_code=<?= urlencode((string)$row['artikel_code']) ?>">Formelregeln bearbeiten</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary btn-sm" href="globals.php">Globale Variablen</a>
  </div>

  <form method="get" class="card card-body mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Nach ID laden</label>
        <input class="form-control" type="number" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">artikel_code suchen</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="z.B. AB1S oder SRO2">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100">Anzeigen</button>
      </div>
    </div>
  </form>

  <?php if ($message): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($searchResults): ?>
    <div class="card mb-4">
      <div class="card-header">Treffer: <?= count($searchResults) ?></div>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead>
          <tr>
            <th>ID</th>
            <th>artikel_code</th>
            <th>Artikel</th>
            <th>Kategorie</th>
            <th>Subkategorie</th>
            <th>Beschreibung</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($searchResults as $result): ?>
            <tr>
              <td><?= (int)$result['id'] ?></td>
              <td><?= htmlspecialchars((string)$result['artikel_code']) ?></td>
              <td><?= htmlspecialchars((string)$result['artikel']) ?></td>
              <td><?= htmlspecialchars((string)$result['kategorie']) ?></td>
              <td><?= htmlspecialchars((string)$result['subkategorie']) ?></td>
              <td><?= htmlspecialchars((string)$result['beschreibung']) ?></td>
              <td class="text-end">
                <a class="btn btn-primary btn-sm" href="?id=<?= (int)$result['id'] ?>">Anzeigen</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($row): ?>
    <div class="card card-body mb-4">
      <div><strong>ID:</strong> <?= (int)$row['id'] ?></div>
      <div><strong>artikel_code:</strong> <?= htmlspecialchars((string)$row['artikel_code']) ?></div>
      <div><strong>artikel:</strong> <?= htmlspecialchars((string)$row['artikel']) ?></div>
    </div>

    <details class="card calc-section mb-4">
      <summary class="card-header">Preisberechnung Schritt fuer Schritt</summary>
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
          <tr>
            <th>Feld</th>
            <th>Rechnung</th>
            <th>Wert</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($priceSteps as $step): ?>
            <tr>
              <td><code><?= htmlspecialchars($step[0]) ?></code></td>
              <td><code><?= htmlspecialchars((string)$step[1]) ?></code></td>
              <td><?= is_numeric($step[2]) ? number_format((float)$step[2], 2, ',', '.') : htmlspecialchars((string)$step[2]) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>

    <details class="card calc-section mb-4">
      <summary class="card-header">EK-Bausteine mit globalen Variablen</summary>
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
          <tr>
            <th>Baustein</th>
            <th>Rechnung</th>
            <th>Wert</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($partSteps as $step): ?>
            <tr>
              <td><code><?= htmlspecialchars($step[0]) ?></code></td>
              <td><code><?= htmlspecialchars((string)$step[1]) ?></code></td>
              <td><?= number_format((float)$step[2], 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>

    <details class="card calc-section mb-4">
      <summary class="card-header">Aktive Formelregeln</summary>
      <div class="card-body">
        <?php foreach ($formulaRules as $field => $formula): ?>
          <div class="mb-2">
            <code><?= htmlspecialchars($field) ?> = <?= htmlspecialchars($formula) ?></code>
          </div>
        <?php endforeach; ?>
      </div>
      <table class="table table-striped mb-0">
        <thead>
        <tr>
          <th>Feld</th>
          <th>Originalwert</th>
          <th>Berechneter Wert</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($formulaFields as $field): ?>
          <tr>
            <td><code><?= htmlspecialchars($field) ?></code></td>
            <td><?= htmlspecialchars((string)($row[$field] ?? '')) ?></td>
            <td><?= number_format($parts[$field], 2, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
          <th colspan="2">EK</th>
          <th><?= htmlspecialchars($calc['ek']) ?></th>
        </tr>
        <tr>
          <th colspan="2">Preis Stueck EK</th>
          <th><?= htmlspecialchars($calc['preis_stueck_ek']) ?></th>
        </tr>
        <tr>
          <th colspan="2">Preis Stueck 2.5</th>
          <th><?= htmlspecialchars($calc['preis_stueck_2_5']) ?></th>
        </tr>
        <tr>
          <th colspan="2">Preis Stueck 2.3</th>
          <th><?= htmlspecialchars($calc['preis_stueck_2_3']) ?></th>
        </tr>
        </tfoot>
      </table>
    </details>
  <?php endif; ?>
</div>
</body>
</html>
