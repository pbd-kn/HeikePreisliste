<?php
require __DIR__ . '/config.php';

$sql = "SELECT id, materialpreis_metall, arbeitszeit, verlust, x_basis, y_aufgerundet, aa_multiplikator, ab_vk_aufgerundet
        FROM catalog_items
        ORDER BY id DESC
        LIMIT 500";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Catalog Items Liste</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid py-3">
  <h4>Catalog Items (neueste 500)</h4>
  <a class="btn btn-primary btn-sm mb-2" href="catalog.php">Zur Katalogansicht</a>
  <a class="btn btn-outline-secondary btn-sm mb-2" href="catalog_create.php">Neuen Satz anlegen</a>
  <div class="table-responsive">
    <table class="table table-striped table-sm bg-white">
      <thead>
        <tr>
          <th>ID</th><th>Materialpreis</th><th>Arbeitszeit</th><th>Verlust</th><th>X</th><th>Y</th><th>AA</th><th>AB</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars((string)$r['materialpreis_metall']) ?></td>
          <td><?= htmlspecialchars((string)$r['arbeitszeit']) ?></td>
          <td><?= htmlspecialchars((string)$r['verlust']) ?></td>
          <td><?= htmlspecialchars((string)$r['x_basis']) ?></td>
          <td><?= htmlspecialchars((string)$r['y_aufgerundet']) ?></td>
          <td><?= htmlspecialchars((string)$r['aa_multiplikator']) ?></td>
          <td><?= htmlspecialchars((string)$r['ab_vk_aufgerundet']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
