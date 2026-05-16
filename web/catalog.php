<?php
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}
if (!isset($pdo) || !$pdo instanceof PDO) {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'preisliste_db';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    try {
        $pdo = new PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        die(
            'Datenbankverbindung fehlgeschlagen: '
            . htmlspecialchars($e->getMessage())
        );
    }
}
/*
|--------------------------------------------------------------------------
| Neue CSV/DB Struktur
|--------------------------------------------------------------------------
*/
$allCols = [
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
| Labels
|--------------------------------------------------------------------------
*/
function labelFromColumn(string $column): string
{
    $label = str_replace('_', ' ', $column);
    return mb_convert_case(
        $label,
        MB_CASE_TITLE,
        'UTF-8'
    );
}
/*
|--------------------------------------------------------------------------
| Show-Parameter
|--------------------------------------------------------------------------
*/
function parseShowColumns(string $showInput, array $allCols): array
{
    $tokens = array_filter(
        array_map(
            fn ($t) => strtolower(trim($t)),
            explode(',', $showInput)
        )
    );
    $allowed = array_flip($allCols);
    $resolved = [];
    foreach ($tokens as $token) {
        if (isset($allowed[$token])) {
            $resolved[] = $token;
        }
    }
    return array_values(array_unique($resolved));
}
/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/
$q = trim($_GET['q'] ?? '');
$showInput = trim($_GET['show'] ?? '');
if ($showInput === '') {
    $showCols = $allCols;
} else {
    $showCols = parseShowColumns(
        $showInput,
        $allCols
    );
    if (empty($showCols)) {
        $showCols = $allCols;
    }
}
$showInputDisplay = implode(', ', $showCols);
/*
|--------------------------------------------------------------------------
| SQL
|--------------------------------------------------------------------------
*/
$selectCols = ['id'];
foreach ($showCols as $c) {
    $selectCols[] = $c;
}
$sql =
    "SELECT "
    . implode(',', $selectCols)
    . " FROM catalog_items";
$params = [];
if ($q !== '') {
    $parts = [];
    foreach ($showCols as $c) {
        $parts[] = "$c LIKE :q";
    }
    $sql .= " WHERE " . implode(" OR ", $parts);
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY id ASC LIMIT 5000";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
/*
|--------------------------------------------------------------------------
| Active Labels
|--------------------------------------------------------------------------
*/
$activeLabels = array_map(
    fn ($c) => labelFromColumn($c),
    $showCols
);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>Katalog</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <style>
        body {
            background: #f6f7f8;
        }
        .scroll-top {
            overflow-x: auto;
            overflow-y: hidden;
            height: 18px;
            border: 1px solid #ddd;
            border-bottom: 0;
            background: #fff;
            border-radius: .375rem .375rem 0 0;
        }
        .scroll-top-inner {
            height: 1px;
        }
        .scroll-bottom {
            overflow-x: auto;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            max-height: 72vh;
            border-radius: 0 0 .375rem .375rem;
        }
        .catalog-table {
            border-collapse: collapse;
            min-width: 5200px;
            width: max-content;
            margin: 0;
            font-size: .85rem;
        }
        .catalog-table th,
        .catalog-table td {
            white-space: nowrap;
            min-width: 170px;
            border: 1px solid #e6e6e6;
            padding: 6px 8px;
            vertical-align: top;
            background: #fff;
        }
        .catalog-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #f1f3f5;
        }
        .catalog-table th:first-child,
        .catalog-table td:first-child {
            min-width: 70px;
            position: sticky;
            left: 0;
            z-index: 4;
            background: #f8f9fa;
        }
        .small-muted {
            font-size: .85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
<div class="container-fluid py-3">
    <h4 class="mb-3">
        Katalogansicht
    </h4>
    <div class="mb-2">
        <a
            class="btn btn-outline-primary btn-sm"
            href="index.php"
        >
            Startmenü
        </a>
        <a
            class="btn btn-outline-secondary btn-sm"
            href="catalog_create.php"
        >
            Neuen Satz anlegen
        </a>
        <a
            class="btn btn-outline-secondary btn-sm"
            href="list.php"
        >
            Liste
        </a>
        <a
            class="btn btn-outline-secondary btn-sm"
            href="print_catalog.php"
            target="_blank"
        >
            Druckansicht
        </a>
    </div>
    <form
        class="row g-2 mb-3"
        method="get"
    >
        <div class="col-md-4">
            <label class="form-label">
                Suche
            </label>
            <input
                class="form-control"
                name="q"
                value="<?= htmlspecialchars($q) ?>"
                placeholder="z. B. Gold, Anhänger, Süßwasser"
            >
        </div>
        <div class="col-md-6">
            <label class="form-label">
                Spalten anzeigen
            </label>
            <input
                class="form-control"
                name="show"
                value="<?= htmlspecialchars($showInputDisplay) ?>"
            >
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">
                Anzeigen
            </button>
        </div>
    </form>
    <div class="mb-2 small-muted">
        Aktive Spalten:
        <?= htmlspecialchars(implode(', ', $activeLabels)) ?>
        |
        Zeilen:
        <?= count($rows) ?>
    </div>
    <div
        class="scroll-top"
        id="scrollTop"
    >
        <div
            class="scroll-top-inner"
            id="scrollTopInner"
        ></div>
    </div>
    <div
        class="scroll-bottom"
        id="scrollBottom"
    >
        <table
            class="catalog-table table table-striped table-hover mb-0"
            id="catalogTable"
        >
            <thead>
            <tr>
                <th>ID</th>
                <?php foreach ($showCols as $c): ?>
                    <th>
                        <?= htmlspecialchars(labelFromColumn($c)) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <?= (int)$r['id'] ?>
                    </td>
                    <?php foreach ($showCols as $c): ?>
                        <td>
                            <?= htmlspecialchars((string)($r[$c] ?? '')) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
(function () {
    const scrollTop =
        document.getElementById('scrollTop');
    const scrollTopInner =
        document.getElementById('scrollTopInner');
    const scrollBottom =
        document.getElementById('scrollBottom');
    const table =
        document.getElementById('catalogTable');
    function syncTopWidth()
    {
        scrollTopInner.style.width =
            table.scrollWidth + 'px';
    }
    let syncingTop = false;
    let syncingBottom = false;
    scrollTop.addEventListener('scroll', function () {
        if (syncingTop) {
            syncingTop = false;
            return;
        }
        syncingBottom = true;
        scrollBottom.scrollLeft =
            scrollTop.scrollLeft;
    });
    scrollBottom.addEventListener('scroll', function () {
        if (syncingBottom) {
            syncingBottom = false;
            return;
        }
        syncingTop = true;
        scrollTop.scrollLeft =
            scrollBottom.scrollLeft;
    });
    window.addEventListener(
        'resize',
        syncTopWidth
    );
    window.addEventListener(
        'load',
        syncTopWidth
    );
    syncTopWidth();
})();
</script>
</body>
</html>
