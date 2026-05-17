<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';
$fields = catalogColumns();
/*
|--------------------------------------------------------------------------
| Meldungen
|--------------------------------------------------------------------------
*/
$message = '';
$isError = false;
$row = null;
$searchResults = [];
$globalParams = loadGlobalParams($pdo);
/*
|--------------------------------------------------------------------------
| Nach ID laden
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['id'])
    && $_GET['id'] !== ''
) {
    $id = (int)$_GET['id'];
    $sql =
        "SELECT *
         FROM catalog_items
         WHERE id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $isError = true;
        $message =
            'Datensatz nicht gefunden.';
    }
}
/*
|--------------------------------------------------------------------------
| LIKE Suche artikel_code
|--------------------------------------------------------------------------
*/
if (
    isset($_GET['artikel_code_search'])
    && trim($_GET['artikel_code_search']) !== ''
) {
    $search =
        trim($_GET['artikel_code_search']);
    $sql =
        "SELECT
            id,
            artikel_code,
            artikel,
            beschreibung,
            kategorie,
            subkategorie
         FROM catalog_items
         WHERE artikel_code LIKE ?
         ORDER BY artikel_code, id";
    $st = $pdo->prepare($sql);
    $st->execute([
        '%' . $search . '%'
    ]);
    $searchResults =
        $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$searchResults) {
        $isError = true;
        $message =
            'Keine Treffer gefunden.';
    }
}
/*
|--------------------------------------------------------------------------
| Speichern
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save'
) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $isError = true;
        $message =
            'Ungültige ID.';
    } else {
        $data = [];
        foreach ($fields as $f) {
            $data[$f] =
                trim($_POST[$f] ?? '');
        }
        /*
        |--------------------------------------------------------------------------
        | Pflichtfelder
        |--------------------------------------------------------------------------
        */
        if ($data['artikel_code'] === '') {
            $isError = true;
            $message =
                'artikel_code ist Pflicht.';
        } elseif ($data['zeit_in_h'] === '') {
            $isError = true;
            $message =
                'zeit_in_h ist Pflicht.';
        }
        /*
        |--------------------------------------------------------------------------
        | Unique artikel_code prüfen
        |--------------------------------------------------------------------------
        */
        if (!$isError) {
            $sql =
                "SELECT id
                 FROM catalog_items
                 WHERE artikel_code = ?
                 AND id <> ?";
            $st = $pdo->prepare($sql);
            $st->execute([
                $data['artikel_code'],
                $id
            ]);
            if ($st->fetch()) {
                $isError = true;
                $message =
                    'artikel_code bereits vorhanden.';
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Speichern
        |--------------------------------------------------------------------------
        */
        if (!$isError) {
            $formulaRules =
                loadCatalogFormulaRules($pdo, $data['artikel_code']);
            $calc =
                calcCatalog($data, $globalParams, $formulaRules);
            $data =
                array_merge($data, $calc);
            $set = [];
            foreach (array_keys($data) as $c) {
                $set[] =
                    $c . ' = ?';
            }
            $sql =
                "UPDATE catalog_items
                 SET " . implode(',', $set) . "
                 WHERE id = ?";
            $st = $pdo->prepare($sql);
            $values =
                array_values($data);
            $values[] = $id;
            $st->execute($values);
            $message =
                'Datensatz gespeichert.';
            /*
            |--------------------------------------------------------------------------
            | Neu laden
            |--------------------------------------------------------------------------
            */
            $reload = $pdo->prepare("
                SELECT *
                FROM catalog_items
                WHERE id = ?
            ");
            $reload->execute([$id]);
            $row =
                $reload->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>
        Catalog Datensatz ändern
    </title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>
        Catalog Datensatz ändern
    </h3>
    <div class="mb-3">
        <a
            class="btn btn-outline-primary btn-sm"
            href="index.php"
        >
            Startmenü
        </a>
        <a
            class="btn btn-outline-secondary btn-sm"
            href="catalog.php"
        >
            Katalog
        </a>
        <?php if ($row): ?>
            <a
                class="btn btn-outline-secondary btn-sm"
                href="calc_explain.php?id=<?= (int)$row['id'] ?>"
            >
                Berechnung anzeigen
            </a>
            <a
                class="btn btn-outline-secondary btn-sm"
                href="formula_rules.php?artikel_code=<?= urlencode((string)$row['artikel_code']) ?>"
            >
                Formelregeln
            </a>
        <?php endif; ?>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <!--
    =====================================================
    Suche
    =====================================================
    -->
    <div class="card card-body mb-4">
        <h5 class="mb-3">
            Datensatz laden
        </h5>
        <!--
        =================================================
        Nach ID
        =================================================
        -->
        <form method="get" class="mb-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">
                        Nach ID laden
                    </label>
                    <input
                        type="number"
                        name="id"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['id'] ?? '') ?>"
                    >
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        Laden
                    </button>
                </div>
            </div>
        </form>
        <hr>
        <!--
        =================================================
        LIKE Suche
        =================================================
        -->
        <form method="get">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">
                        artikel_code suchen (LIKE)
                    </label>
                    <input
                        type="text"
                        name="artikel_code_search"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['artikel_code_search'] ?? '') ?>"
                        placeholder="z.B. SR02"
                    >
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-secondary w-100">
                        Suchen
                    </button>
                </div>
            </div>
        </form>
    </div>
    <!--
    =====================================================
    Trefferliste
    =====================================================
    -->
    <?php if ($searchResults): ?>
        <div class="card mb-4">
            <div class="card-header">
                Treffer:
                <?= count($searchResults) ?>
            </div>
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
                        <th>Bearbeiten</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($searchResults as $r): ?>
                        <tr>
                            <td>
                                <?= (int)$r['id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['artikel_code']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['artikel']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['kategorie']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['subkategorie']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($r['beschreibung']) ?>
                            </td>
                            <td>
                                <a
                                    class="btn btn-primary btn-sm"
                                    href="?id=<?= (int)$r['id'] ?>"
                                >
                                    Bearbeiten
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    <!--
    =====================================================
    Formular
    =====================================================
    -->
    <?php if ($row): ?>
        <form
            method="post"
            class="card card-body"
        >
            <input
                type="hidden"
                name="action"
                value="save"
            >
            <input
                type="hidden"
                name="id"
                value="<?= (int)$row['id'] ?>"
            >
            <div class="mb-3">
                <strong>
                    Bearbeite ID:
                    <?= (int)$row['id'] ?>
                </strong>
            </div>
            <div class="row g-3">
                <?php foreach ($fields as $f): ?>
                    <div class="col-md-4">
                        <label class="form-label">
                            <?= htmlspecialchars($f) ?>
                        </label>
                        <input
                            class="form-control"
                            name="<?= htmlspecialchars($f) ?>"
                            value="<?= htmlspecialchars($row[$f] ?? '') ?>"
                        >
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4">
                <button class="btn btn-success">
                    Änderungen speichern
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
