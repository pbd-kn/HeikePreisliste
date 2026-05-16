<?php
require __DIR__ . '/config.php';
$sql = "
    SELECT
        id,
        artikel_code,
        artikel,
        kategorie,
        subkategorie,
        ek,
        preis_stueck_ek,
        preis_paar_ek,
        preis_stueck_2_5,
        preis_paar_2_5,
        preis_stueck_2_3,
        preis_paar_2_3
    FROM catalog_items
    ORDER BY id DESC
    LIMIT 500
";
$rows = $pdo
    ->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>
        Catalog Items Liste
    </title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
<div class="container-fluid py-3">
    <h4>
        Catalog Items (neueste 500)
    </h4>
    <a
        class="btn btn-primary btn-sm mb-2"
        href="catalog.php"
    >
        Zur Katalogansicht
    </a>
    <a
        class="btn btn-outline-secondary btn-sm mb-2"
        href="catalog_create.php"
    >
        Neuen Satz anlegen
    </a>
    <div class="table-responsive">
        <table class="table table-striped table-sm bg-white">
            <thead>
            <tr>
                <th>ID</th>
                <th>Artikelcode</th>
                <th>Artikel</th>
                <th>Kategorie</th>
                <th>Subkategorie</th>
                <th>EK</th>
                <th>Preis Stück EK</th>
                <th>Preis Paar EK</th>
                <th>Preis Stück 2.5</th>
                <th>Preis Paar 2.5</th>
                <th>Preis Stück 2.3</th>
                <th>Preis Paar 2.3</th>
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
    </div>
</div>
</body>
</html>
