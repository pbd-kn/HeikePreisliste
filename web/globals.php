<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';

$message = '';
$isError = false;
$editKey = normalizeParamKey($_GET['edit'] ?? '');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS global_params (
        param_key VARCHAR(64) NOT NULL PRIMARY KEY,
        label VARCHAR(120) NOT NULL,
        param_value DECIMAL(12,4) NOT NULL,
        unit VARCHAR(20) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

try {
    $pdo->exec("ALTER TABLE global_params MODIFY param_key VARCHAR(64) NOT NULL");
    $pdo->exec("ALTER TABLE global_params MODIFY param_value DECIMAL(12,4) NOT NULL");
} catch (Throwable $e) {
}

function normalizeParamKey(string $key): string
{
    return strtoupper(trim($key));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'save';
        $paramKey = normalizeParamKey($_POST['param_key'] ?? '');

        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $paramKey)) {
            throw new RuntimeException('Variablenname ist ungueltig. Erlaubt: Buchstaben, Zahlen und Unterstrich; Beginn mit Buchstabe oder Unterstrich.');
        }

        if ($action === 'delete') {
            $st = $pdo->prepare("DELETE FROM global_params WHERE param_key = ?");
            $st->execute([$paramKey]);
            $message = 'Variable geloescht: ' . $paramKey;
        } else {
            $label = trim($_POST['label'] ?? '');
            $unit = trim($_POST['unit'] ?? '');
            $value = parseEur($_POST['param_value'] ?? '');

            if ($label === '') {
                $label = $paramKey;
            }

            $st = $pdo->prepare("
                INSERT INTO global_params (param_key, label, param_value, unit)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    label = VALUES(label),
                    param_value = VALUES(param_value),
                    unit = VALUES(unit)
            ");
            $st->execute([$paramKey, $label, $value, $unit !== '' ? $unit : null]);
            $message = 'Variable gespeichert: ' . $paramKey;
        }
    } catch (Throwable $e) {
        $isError = true;
        $message = $e->getMessage();
    }
}

$rows = $pdo
    ->query("SELECT param_key, label, param_value, unit, updated_at FROM global_params ORDER BY param_key")
    ->fetchAll(PDO::FETCH_ASSOC);

$editRow = null;
if ($editKey !== '') {
    $st = $pdo->prepare("SELECT param_key, label, param_value, unit FROM global_params WHERE param_key = ?");
    $st->execute([$editKey]);
    $editRow = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Globale Variablen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3>Globale Variablen</h3>
  <div class="mb-3">
    <a class="btn btn-outline-primary btn-sm" href="index.php">Startmenue</a>
    <a class="btn btn-outline-secondary btn-sm" href="catalog.php">Katalog</a>
    <a class="btn btn-outline-secondary btn-sm" href="formula_rules.php">Formelregeln</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="alert alert-info">
    Neue Variablen werden erst verwendet, wenn ihr Name in einer Formelregel steht.
    Beispiel: Variable <code>MEIN_ZUSCHLAG</code> speichern und danach in den Formelregeln
    die EK-Formel um <code>+ MEIN_ZUSCHLAG</code> erweitern.
  </div>

  <form method="post" class="card card-body mb-4">
    <input type="hidden" name="action" value="save">
    <h5 class="mb-3"><?= $editRow ? 'Variable bearbeiten' : 'Neue globale Variable anlegen' ?></h5>
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Formelname</label>
        <input
          class="form-control"
          name="param_key"
          value="<?= htmlspecialchars((string)($editRow['param_key'] ?? '')) ?>"
          placeholder="z.B. MEIN_ZUSCHLAG"
          required
        >
        <div class="form-text">Diesen Namen exakt in Formelregeln verwenden.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Beschreibung</label>
        <input
          class="form-control"
          name="label"
          value="<?= htmlspecialchars((string)($editRow['label'] ?? '')) ?>"
          placeholder="z.B. eigener Zuschlag"
        >
      </div>
      <div class="col-md-3">
        <label class="form-label">Wert</label>
        <input
          class="form-control"
          name="param_value"
          value="<?= htmlspecialchars((string)($editRow['param_value'] ?? '')) ?>"
          placeholder="z.B. 7,30"
          required
        >
      </div>
      <div class="col-md-2">
        <label class="form-label">Einheit</label>
        <input
          class="form-control"
          name="unit"
          value="<?= htmlspecialchars((string)($editRow['unit'] ?? '')) ?>"
          placeholder="z.B. EUR"
        >
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Speichern</button>
      <?php if ($editRow): ?>
        <a class="btn btn-outline-secondary" href="globals.php">Neue Variable</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead>
        <tr>
          <th>Formelname</th>
          <th>Beschreibung</th>
          <th>Wert</th>
          <th>Einheit</th>
          <th>Geaendert</th>
          <th>In Formelregeln verwenden als</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><code><?= htmlspecialchars($row['param_key']) ?></code></td>
            <td><?= htmlspecialchars($row['label']) ?></td>
            <td><?= htmlspecialchars((string)$row['param_value']) ?></td>
            <td><?= htmlspecialchars((string)($row['unit'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)$row['updated_at']) ?></td>
            <td><code>+ <?= htmlspecialchars($row['param_key']) ?></code></td>
            <td class="text-end">
              <div class="d-flex gap-2 justify-content-end">
                <a class="btn btn-outline-primary btn-sm" href="?edit=<?= urlencode($row['param_key']) ?>">Bearbeiten</a>
                <form method="post" onsubmit="return confirm('Variable wirklich loeschen?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="param_key" value="<?= htmlspecialchars($row['param_key']) ?>">
                  <button class="btn btn-outline-danger btn-sm">Loeschen</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
