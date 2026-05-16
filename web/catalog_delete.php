<?php
require __DIR__ . '/config.php';
$message = '';
$isError = false;
$searchResults = [];
/*
|--------------------------------------------------------------------------
| Suche nach artikel_code (LIKE)
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'search'
) {
    $artikelCode = trim($_POST['artikel_code_search'] ?? '');
    if ($artikelCode === '') {
        $isError = true;
        $message =
            'Bitte artikel_code eingeben.';
    } else {
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
            '%' . $artikelCode . '%'
        ]);
        $searchResults =
            $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$searchResults) {
            $isError = true;
            $message =
                'Keine Treffer gefunden.';
        }
    }
}
/*
|--------------------------------------------------------------------------
| Direkt löschen
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_direct'
) {
    $id = trim($_POST['id'] ?? '');
    $artikelCode = trim($_POST['artikel_code'] ?? '');
    /*
    |--------------------------------------------------------------------------
    | Nach ID löschen
    |--------------------------------------------------------------------------
    */
    if ($id !== '') {
        $sql =
            "DELETE FROM catalog_items
             WHERE id = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $count = $st->rowCount();
        if ($count > 0) {
            $message =
                'Datensatz mit ID '
                . $id
                . ' gelöscht.';
        } else {
            $isError = true;
            $message =
                'Keine ID gefunden: '
                . $id;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Nach artikel_code löschen
    |--------------------------------------------------------------------------
    */ elseif ($artikelCode !== '') {
        $sql =
            "DELETE FROM catalog_items
             WHERE artikel_code = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$artikelCode]);
        $count = $st->rowCount();
        if ($count > 0) {
            $message =
                $count
                . ' Datensatz/Datensätze gelöscht für artikel_code: '
                . $artikelCode;
        } else {
            $isError = true;
            $message =
                'artikel_code nicht gefunden: '
                . $artikelCode;
        }
    } else {
        $isError = true;
        $message =
            'Bitte ID oder artikel_code eingeben.';
    }
}
/*
|--------------------------------------------------------------------------
| Einzelnen Treffer löschen
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_selected'
) {
    $id = (int)($_POST['delete_id'] ?? 0);
    if ($id <= 0) {
        $isError = true;
        $message =
            'Ungültige ID.';
    } else {
        $st = $pdo->prepare("
            SELECT artikel_code
            FROM catalog_items
            WHERE id = ?
        ");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $isError = true;
            $message =
                'Datensatz nicht gefunden.';
        } else {
            $del = $pdo->prepare("
                DELETE FROM catalog_items
                WHERE id = ?
            ");
            $del->execute([$id]);
            $message =
                'Datensatz gelöscht: ID '
                . $id
                . ' / artikel_code '
                . $row['artikel_code'];
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>
        Catalog Datensatz löschen
    </title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>
        Catalog Datensatz löschen
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
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?= $isError ? 'danger' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <!--
    =====================================================
    Direkt löschen
    =====================================================
    -->
    <form
        method="post"
        class="card card-body mb-4"
    >
        <input
            type="hidden"
            name="action"
            value="delete_direct"
        >
        <h5 class="mb-3">
            Direkt löschen
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">
                    Löschen nach ID
                </label>
                <input
                    type="number"
                    name="id"
                    class="form-control"
                >
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    Löschen nach artikel_code
                </label>
                <input
                    type="text"
                    name="artikel_code"
                    class="form-control"
                >
            </div>
        </div>
        <div class="mt-3">
            <button
                class="btn btn-danger"
                onclick="return confirm('Datensatz wirklich löschen?');"
            >
                Direkt löschen
            </button>
        </div>
    </form>
    <!--
    =====================================================
    LIKE Suche
    =====================================================
    -->
    <form
        method="post"
        class="card card-body mb-4"
    >
        <input
            type="hidden"
            name="action"
            value="search"
        >
        <h5 class="mb-3">
            artikel_code suchen (LIKE)
        </h5>
        <div class="row g-3">
            <div class="col-md-8">
                <input
                    type="text"
                    name="artikel_code_search"
                    class="form-control"
                    value="<?= htmlspecialchars($_POST['artikel_code_search'] ?? '') ?>"
                    placeholder="z.B. SR02"
                >
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">
                    Suchen
                </button>
            </div>
        </div>
    </form>
    <!--
    =====================================================
    Trefferliste
    =====================================================
    -->
    <?php if ($searchResults): ?>
        <div class="card">
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
                        <th>Löschen</th>
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
                                <form
                                    method="post"
                                    onsubmit="return confirm('Datensatz wirklich löschen?');"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete_selected"
                                    >
                                    <input
                                        type="hidden"
                                        name="delete_id"
                                        value="<?= (int)$r['id'] ?>"
                                    >
                                    <button class="btn btn-danger btn-sm">
                                        Löschen
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
