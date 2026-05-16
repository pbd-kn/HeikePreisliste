<?php
require __DIR__ . '/config.php';
require __DIR__ . '/calculate.php';
/*
|--------------------------------------------------------------------------
| Neue DB-Struktur
|--------------------------------------------------------------------------
*/
$inputFields = [
    'artikel_code',
    'bild',
    'ag_in_g',
    'ag_incl_verlust',
    'au_in_g',
    'au_incl_verlust',
    'zeit_in_h',
    'artikel_zusatz',
    'stueck_1',
    'steine_perlen_ek',
    'steine_messe',
    'artikel_2',
    'stueck_2',
    'furnituren_steine_ek',
    'steine_messe_2',
    'plattierung_oxidation',
    'schnur_2',
    'leer_1',
    'leer_2',
    'kategorie',
    'subkategorie',
    'artikelnr',
    'artikel',
    'ek',
    'preis_stueck_ek',
    'preis_paar_ek',
    'vkstk_ek_2_5_ungerundet',
    'preis_stueck_2_5',
    'paarpreis_vk_2_5_ungerundet',
    'preis_paar_2_5',
    'beschreibung',
    'nochmals_artikel',
    'vkstk_ek_2_3_ungerundet',
    'preis_stueck_2_3',
    'vkpaar_ek_2_3_ungerundet',
    'preis_paar_2_3',
    'reserve_1',
    'reserve_2',
    'reserve_3',
    'reserve_4'
];
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
        $preview = calcCatalog($data);
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
                        value="<?= htmlspecialchars($_POST[$f] ?? '') ?>"
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
