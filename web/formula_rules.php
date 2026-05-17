<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

ensureCatalogFormulaRules($pdo);

$targetLabels = array_intersect_key(catalogFormulaRuleLabels(), defaultCatalogFormulaRules());
$formulaHints = [
    'ag_incl_verlust' => 'Verwendet normalerweise ag_in_g und C3 (Verlust AG), wenn ag_incl_verlust leer ist.',
    'au_incl_verlust' => 'Verwendet normalerweise au_in_g und C5 (Verlust AU/WG), wenn au_incl_verlust leer ist.',
    'material' => 'Verwendet ag_incl_verlust/au_incl_verlust und je nach Artikel A3 (AG), A5 (AU) oder A7 (WG).',
    'arbeitszeit' => 'Verwendet zeit_in_h und B3 (Arbeitszeitpreis EUR/h).',
    'stueck_1_messe' => 'Verwendet stueck_1 und steine_messe; falls leer, steine_perlen_ek * 1.5.',
    'stueck_2_messe' => 'Verwendet stueck_2 und steine_messe_2; falls leer, furnituren_steine_ek * 1.5.',
    'plattierung_oxidation' => 'Kommt aus dem Artikelfeld plattierung_oxidation.',
    'schnur_2' => 'Kommt aus dem Artikelfeld schnur_2.',
    'fixkosten_r5' => 'Verwendet R5 bei Silber-Artikeln, ausser Ohrstecker und Gold.',
    'fixkosten_s' => 'Verwendet R3 + S3 bei Ohrsteckern oder S9 bei Gold.',
    'metall_zuschlag_s' => 'Verwendet S7 bei Silber oder S11 bei bestimmten Gold-Artikeln.',
    'ek' => 'Setzt die berechneten Bausteine zusammen. Die globalen Variablen stecken in diesen Bausteinen; eigene globale Variablen kannst du hier direkt addieren.',
    'factor_25' => 'Faktor fuer Preis Stueck 2.5. Kann auch eine Formel sein.',
    'factor_23' => 'Faktor fuer Preis Stueck 2.3. Kann auch eine Formel sein.',
    'preis_stueck_ek' => 'Standard ist aufgerundeter EK. Du kannst z.B. default_preis_stueck_ek + 1 verwenden.',
    'preis_paar_ek' => 'Standard ist preis_stueck_ek * 2.',
    'vkstk_ek_2_5_ungerundet' => 'Standard ist preis_stueck_ek * factor_25.',
    'preis_stueck_2_5' => 'Standard ist der aufgerundete VK-Stueckpreis 2.5.',
    'paarpreis_vk_2_5_ungerundet' => 'Standard ist vkstk_ek_2_5_ungerundet * 2.',
    'preis_paar_2_5' => 'Standard ist preis_stueck_2_5 * 2. Hier kannst du den Paarpreis 2.5 direkt ueberschreiben.',
    'vkstk_ek_2_3_ungerundet' => 'Standard ist preis_stueck_ek * factor_23.',
    'preis_stueck_2_3' => 'Standard ist der aufgerundete VK-Stueckpreis 2.3.',
    'vkpaar_ek_2_3_ungerundet' => 'Standard ist vkstk_ek_2_3_ungerundet * 2.',
    'preis_paar_2_3' => 'Standard ist preis_stueck_2_3 * 2. Hier kannst du den Paarpreis 2.3 direkt ueberschreiben.'
];
$formulaDetails = [
    'ek' => [
        'material' => 'AG: ag_incl_verlust * A3, AU: au_incl_verlust * A5, WG: au_incl_verlust * A7',
        'arbeitszeit' => 'zeit_in_h * B3',
        'fixkosten_r5' => 'R5 bei Silber, ausser Ohrstecker und Gold',
        'fixkosten_s' => 'R3 + S3 bei Ohrsteckern oder S9 bei Gold',
        'metall_zuschlag_s' => 'ag_incl_verlust * S7 oder au_incl_verlust * S11'
    ]
];
$formulaGlobals = [
    'ag_incl_verlust' => ['C3'],
    'au_incl_verlust' => ['C5'],
    'material' => ['A3', 'A5', 'A7'],
    'arbeitszeit' => ['B3'],
    'fixkosten_r5' => ['R5'],
    'fixkosten_s' => ['R3', 'S3', 'S9'],
    'metall_zuschlag_s' => ['S7', 'S11']
];
$availableVariables = [
    'material',
    'arbeitszeit',
    'stueck_1_messe',
    'stueck_2_messe',
    'plattierung_oxidation',
    'schnur_2',
    'fixkosten_r5',
    'fixkosten_s',
    'metall_zuschlag_s',
    'ag_incl_verlust',
    'au_incl_verlust',
    'ag_in_g',
    'au_in_g',
    'zeit_in_h',
    'stueck_1',
    'stueck_2',
    'steine_perlen_ek',
    'steine_messe',
    'furnituren_steine_ek',
    'steine_messe_2',
    'A3',
    'A5',
    'A7',
    'B3',
    'C3',
    'C5',
    'R3',
    'R5',
    'S3',
    'S7',
    'S9',
    'S11'
];
foreach (array_keys(catalogCalculationParts([], [])) as $partField) {
    $availableVariables[] = 'default_' . $partField;
}

$message = '';
$isError = false;
$articleCode = trim((string)($_POST['artikel_code'] ?? $_GET['artikel_code'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));
$searchResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['action'] ?? '') === 'save') {
            foreach (array_keys(defaultCatalogFormulaRules()) as $field) {
                saveCatalogFormulaRule(
                    $pdo,
                    $articleCode,
                    $field,
                    (string)($_POST['formula'][$field] ?? ''),
                    $targetLabels[$field] ?? $field
                );
            }
            $message = $articleCode === ''
                ? 'Default-Regeln gespeichert.'
                : 'Regeln fuer ' . $articleCode . ' gespeichert.';
        } elseif (($_POST['action'] ?? '') === 'delete_overrides') {
            foreach (array_keys(defaultCatalogFormulaRules()) as $field) {
                deleteCatalogFormulaRule($pdo, $articleCode, $field);
            }
            $message = 'Artikel-Regeln geloescht. Es gelten wieder die Defaults.';
        }
    } catch (Throwable $e) {
        $isError = true;
        $message = $e->getMessage();
    }
}

if ($search !== '') {
    $st = $pdo->prepare("
        SELECT id, artikel_code, artikel, kategorie, subkategorie
        FROM catalog_items
        WHERE artikel_code LIKE ?
        ORDER BY artikel_code, id
        LIMIT 50
    ");
    $st->execute(['%' . $search . '%']);
    $searchResults = $st->fetchAll(PDO::FETCH_ASSOC);
}

$defaultRules = loadCatalogFormulaRules($pdo, '');
$activeRules = loadCatalogFormulaRules($pdo, $articleCode);
$articleRows = $articleCode === '' ? [] : loadCatalogFormulaRuleRows($pdo, $articleCode);
$hasOverrides = count($articleRows) > 0;
$globalRows = $pdo
    ->query("SELECT param_key, label, param_value, unit FROM global_params ORDER BY param_key")
    ->fetchAll(PDO::FETCH_ASSOC);
$globalLookup = [];
foreach ($globalRows as $globalRow) {
    $globalLookup[(string)$globalRow['param_key']] = $globalRow;
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Formelregeln</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3>Formelregeln</h3>
  <div class="mb-3">
    <a class="btn btn-outline-primary btn-sm" href="index.php">Startmenue</a>
    <a class="btn btn-outline-secondary btn-sm" href="calc_explain.php<?= $articleCode !== '' ? '?q=' . urlencode($articleCode) : '' ?>">Berechnung anzeigen</a>
    <a class="btn btn-outline-secondary btn-sm" href="globals.php">Globale Variablen</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card card-body mb-4">
    <form method="get" class="row g-3">
      <div class="col-md-8">
        <label class="form-label">artikel_code suchen</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="z.B. AB1S">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100">Suchen</button>
      </div>
    </form>

    <?php if ($searchResults): ?>
      <div class="table-responsive mt-3">
        <table class="table table-sm table-striped mb-0">
          <thead>
          <tr>
            <th>ID</th>
            <th>artikel_code</th>
            <th>Artikel</th>
            <th>Kategorie</th>
            <th>Subkategorie</th>
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
              <td class="text-end">
                <a class="btn btn-primary btn-sm" href="?artikel_code=<?= urlencode((string)$result['artikel_code']) ?>">Regeln laden</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif ($search !== ''): ?>
      <div class="text-muted mt-3">Keine Treffer gefunden.</div>
    <?php endif; ?>
  </div>

  <form method="post" class="card card-body mb-4">
    <input type="hidden" name="action" value="save">
    <div class="row g-3 mb-3">
      <div class="col-md-8">
        <label class="form-label">artikel_code leer lassen fuer Default-Regeln</label>
        <input class="form-control" name="artikel_code" value="<?= htmlspecialchars($articleCode) ?>" placeholder="leer = gilt fuer alle Artikel">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary w-100">Regeln speichern</button>
      </div>
    </div>

    <?php if ($articleCode !== ''): ?>
      <div class="alert alert-info">
        Du bearbeitest Regeln nur fuer <strong><?= htmlspecialchars($articleCode) ?></strong>.
        <?= $hasOverrides ? 'Eigene Regeln sind vorhanden.' : 'Bisher gelten die Default-Regeln.' ?>
      </div>
    <?php endif; ?>

    <?php foreach ($targetLabels as $field => $label): ?>
      <div class="mb-3">
        <label class="form-label">
          <?= htmlspecialchars($label) ?> <code><?= htmlspecialchars($field) ?></code>
          <?php if (!empty($formulaGlobals[$field])): ?>
            <span class="text-muted">
              (
              <?php foreach ($formulaGlobals[$field] as $idx => $globalKey): ?>
                <?php $global = $globalLookup[$globalKey] ?? null; ?>
                <?= $idx > 0 ? ', ' : '' ?><code><?= htmlspecialchars($globalKey) ?></code><?php if ($global): ?> <?= htmlspecialchars((string)$global['param_value']) ?> <?= htmlspecialchars((string)($global['unit'] ?? '')) ?><?php endif; ?>
              <?php endforeach; ?>
              )
            </span>
          <?php endif; ?>
        </label>
        <textarea class="form-control font-monospace" name="formula[<?= htmlspecialchars($field) ?>]" rows="3"><?= htmlspecialchars((string)($activeRules[$field] ?? $defaultRules[$field] ?? '')) ?></textarea>
        <?php if (!empty($formulaHints[$field])): ?>
          <div class="form-text"><?= htmlspecialchars($formulaHints[$field]) ?></div>
        <?php endif; ?>
        <?php if (!empty($formulaDetails[$field])): ?>
          <div class="small text-muted mt-2">
            <div><strong>Bausteine ausgeschrieben:</strong></div>
            <?php foreach ($formulaDetails[$field] as $partName => $partText): ?>
              <div><code><?= htmlspecialchars($partName) ?></code> = <?= htmlspecialchars($partText) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($articleCode !== ''): ?>
          <div class="form-text">Default: <code><?= htmlspecialchars((string)($defaultRules[$field] ?? '')) ?></code></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </form>

  <?php if ($articleCode !== '' && $hasOverrides): ?>
    <form method="post" class="mb-4">
      <input type="hidden" name="action" value="delete_overrides">
      <input type="hidden" name="artikel_code" value="<?= htmlspecialchars($articleCode) ?>">
      <button class="btn btn-outline-danger">Eigene Regeln fuer <?= htmlspecialchars($articleCode) ?> loeschen</button>
    </form>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Verwendbare Variablen</div>
    <div class="card-body">
      <?php foreach ($availableVariables as $var): ?>
        <code class="me-2"><?= htmlspecialchars($var) ?></code>
      <?php endforeach; ?>
      <div class="text-muted mt-3">
        Erlaubt sind Zahlen, Variablen, Klammern und + - * /. Beispiel:
        <code>(material + arbeitszeit + fixkosten_r5) * 1.1</code>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header">Globale Variablen in Formeln</div>
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0">
        <thead>
        <tr>
          <th>Name</th>
          <th>Beschreibung</th>
          <th>Wert</th>
          <th>Einheit</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($globalRows as $globalRow): ?>
          <tr>
            <td><code><?= htmlspecialchars((string)$globalRow['param_key']) ?></code></td>
            <td><?= htmlspecialchars((string)$globalRow['label']) ?></td>
            <td><?= htmlspecialchars((string)$globalRow['param_value']) ?></td>
            <td><?= htmlspecialchars((string)($globalRow['unit'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
