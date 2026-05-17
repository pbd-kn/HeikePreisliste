<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';
$inputFields = catalogColumns();
/*
|--------------------------------------------------------------------------
| Pflichtfelder
|--------------------------------------------------------------------------
*/
$requiredFields = [
    'artikel_code',
    'zeit_in_h'
];
/*
|--------------------------------------------------------------------------
| Optional
|--------------------------------------------------------------------------
*/
$optionalTextFields = [];
/*
|--------------------------------------------------------------------------
| Meldungen
|--------------------------------------------------------------------------
*/
$message = '';
$isError = false;
$preview = null;
$globalParams = loadGlobalParams($pdo);
$templateResults = [];
$formData = [];

function nextAvailableArticleCode(PDO $pdo, string $baseCode): string
{
    $baseCode = trim($baseCode);
    if ($baseCode === '') {
        return '';
    }

    $candidate = $baseCode . '-NEU';
    $index = 1;
    $st = $pdo->prepare("SELECT 1 FROM catalog_items WHERE artikel_code = ? LIMIT 1");

    while (true) {
        $st->execute([$candidate]);
        if (!$st->fetchColumn()) {
            return $candidate;
        }

        $index++;
        $candidate = $baseCode . '-NEU-' . $index;
    }
}

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    && isset($_GET['template_query'])
    && trim($_GET['template_query']) !== ''
) {
    $query = trim($_GET['template_query']);
    $st = $pdo->prepare("
        SELECT id, artikel_code, artikel, kategorie, subkategorie, beschreibung
        FROM catalog_items
        WHERE artikel_code LIKE ?
        ORDER BY artikel_code, id
        LIMIT 50
    ");
    $st->execute(['%' . $query . '%']);
    $templateResults = $st->fetchAll(PDO::FETCH_ASSOC);
}

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    && isset($_GET['template_id'])
    && (int)$_GET['template_id'] > 0
) {
    $st = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
    $st->execute([(int)$_GET['template_id']]);
    $template = $st->fetch(PDO::FETCH_ASSOC);
    if ($template) {
        foreach ($inputFields as $field) {
            $formData[$field] = (string)($template[$field] ?? '');
        }
        $formData['artikel_code'] = nextAvailableArticleCode($pdo, (string)$template['artikel_code']);
        $formData['artikelnr'] = $formData['artikel_code'];
        $formData['artikel'] = $formData['artikel_code'];
        $message = 'Vorlage geladen: ' . $template['artikel_code'];
    }
}
/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach (
        array_merge(
            $inputFields,
            $optionalTextFields
        ) as $f
    ) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    /*
    |--------------------------------------------------------------------------
    | Pflichtfelder prüfen
    |--------------------------------------------------------------------------
    */
    foreach ($requiredFields as $field) {
        if (($data[$field] ?? '') === '') {
            $isError = true;
            $message =
                'Pflichtfeld fehlt: '
                . $field;
            break;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Nur speichern wenn kein Fehler
    |--------------------------------------------------------------------------
    */
    if (!$isError) {
        /*
        |--------------------------------------------------------------------------
        | Berechnung
        |--------------------------------------------------------------------------
        */
        $formulaRules = loadCatalogFormulaRules($pdo, $data['artikel_code']);
        $preview = calcCatalog($data, $globalParams, $formulaRules);
        $data = array_merge($data, $preview);
        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */
        $insertCols = array_keys($data);
        $sql =
            "INSERT INTO catalog_items ("
            . implode(',', $insertCols)
            . ") VALUES ("
            . implode(',', array_fill(0, count($insertCols), '?'))
            . ")";
        try {
            $st = $pdo->prepare($sql);
            $st->execute(
                array_map(
                    fn ($c) => $data[$c] ?? '',
                    $insertCols
                )
            );
            $message =
                'Datensatz gespeichert. ID: '
                . $pdo->lastInsertId();
        } catch (PDOException $e) {
            $isError = true;
            /*
            |--------------------------------------------------------------------------
            | Duplicate artikel_code
            |--------------------------------------------------------------------------
            */
            if (($e->errorInfo[1] ?? 0) == 1062) {
                $message =
                    'Fehler: artikel_code bereits vorhanden: '
                    . $data['artikel_code'];
            } else {
                $message =
                    'DB-Fehler: '
                    . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>
        Neuen Catalog-Satz anlegen
    </title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<div class="container py-3">
    <h4>
        Neuen Satz in catalog_items anlegen
    </h4>
    <div class="mb-2">
        <a
            class="btn btn-outline-primary btn-sm"
            href="catalog.php"
        >
            Katalog
        </a>
        <a
            class="btn btn-outline-secondary btn-sm"
            href="list.php"
        >
            Liste
        </a>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($preview): ?>
        <div class="alert alert-info">
            Berechnete Werte gespeichert.
        </div>
    <?php endif; ?>
    <div class="card card-body mb-3">
        <h5 class="mb-3">
            Vorlage aus bestehendem Artikel laden
        </h5>
        <form method="get" class="row g-2">
            <div class="col-md-9">
                <label class="form-label">
                    artikel_code suchen
                </label>
                <input
                    class="form-control"
                    name="template_query"
                    value="<?= htmlspecialchars($_GET['template_query'] ?? '') ?>"
                    placeholder="z.B. AB1S oder SRO2"
                >
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100">
                    Vorlage suchen
                </button>
            </div>
        </form>
        <?php if ($templateResults): ?>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-striped mb-0">
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
                    <?php foreach ($templateResults as $result): ?>
                        <tr>
                            <td><?= (int)$result['id'] ?></td>
                            <td><?= htmlspecialchars((string)$result['artikel_code']) ?></td>
                            <td><?= htmlspecialchars((string)$result['artikel']) ?></td>
                            <td><?= htmlspecialchars((string)$result['kategorie']) ?></td>
                            <td><?= htmlspecialchars((string)$result['subkategorie']) ?></td>
                            <td><?= htmlspecialchars((string)$result['beschreibung']) ?></td>
                            <td class="text-end">
                                <a
                                    class="btn btn-primary btn-sm"
                                    href="?template_id=<?= (int)$result['id'] ?>"
                                >
                                    Verwenden
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif (isset($_GET['template_query']) && trim($_GET['template_query']) !== ''): ?>
            <div class="text-muted mt-3">
                Keine Vorlage gefunden.
            </div>
        <?php endif; ?>
    </div>
    <form
        method="post"
        class="card card-body"
    >
        <div class="row g-2">
            <?php foreach ($inputFields as $f): ?>
                <div class="col-md-4">
                    <label class="form-label">
                        <?= htmlspecialchars($f) ?>
                    </label>
                    <input
                        class="form-control"
                        name="<?= htmlspecialchars($f) ?>"
                        value="<?= htmlspecialchars($_POST[$f] ?? $formData[$f] ?? '') ?>"
                        <?= in_array($f, $requiredFields) ? 'required' : '' ?>
                    >
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary">
                Speichern
            </button>
        </div>
    </form>
</div>
</body>
</html>
