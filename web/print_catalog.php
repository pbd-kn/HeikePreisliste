<?php
require __DIR__ . '/config.php';
$q = trim($_GET['q'] ?? '');
$sql = "
    SELECT
        id,
        artikel_code,
        artikel,
        kategorie,
        subkategorie,
        beschreibung,
        ek,
        preis_stueck_ek,
        preis_paar_ek,
        preis_stueck_2_5,
        preis_paar_2_5,
        preis_stueck_2_3,
        preis_paar_2_3
    FROM catalog_items
";
$params = [];
if ($q !== '') {
    $sql .= "
        WHERE
            artikel_code LIKE :q
            OR artikel LIKE :q
            OR beschreibung LIKE :q
            OR kategorie LIKE :q
            OR subkategorie LIKE :q
    ";
    $params[':q'] = "%$q%";
}
$sql .= "
    ORDER BY id ASC
    LIMIT 3000
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>
        Druckansicht Katalog
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 8mm;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: top;
        }
        th {
            background: #eee;
        }
        .no-print {
            margin-bottom: 8px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">
        Drucken
    </button>
</div>
<h3>
    Katalog Druckansicht
</h3>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Artikelcode</th>
        <th>Artikel</th>
        <th>Kategorie</th>
        <th>Subkategorie</th>
        <th>Beschreibung</th>
        <th>EK</th>
        <th>Stück EK</th>
        <th>Paar EK</th>
        <th>Stück 2.5</th>
        <th>Paar 2.5</th>
        <th>Stück 2.3</th>
        <th>Paar 2.3</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td>
                <?= (int)$r['id'] ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['artikel_code']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['artikel']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['kategorie']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['subkategorie']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['beschreibung']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['ek']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_stueck_ek']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_paar_ek']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_stueck_2_5']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_paar_2_5']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_stueck_2_3']) ?>
            </td>
            <td>
                <?= htmlspecialchars((string)$r['preis_paar_2_3']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
