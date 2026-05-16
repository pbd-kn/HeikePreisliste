<?php
require __DIR__ . '/config.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT id, materialpreis_metall, arbeitszeit, verlust, galvanik, furnituren_au_750_333, x_basis, y_aufgerundet, ab_vk_aufgerundet
        FROM catalog_items";
$params = [];
if ($q !== '') {
    $sql .= " WHERE materialpreis_metall LIKE :q OR galvanik LIKE :q OR furnituren_au_750_333 LIKE :q";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY id ASC LIMIT 3000";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Druckansicht Katalog</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 8mm; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #666; padding: 3px 4px; vertical-align: top; }
    th { background: #eee; }
    .no-print { margin-bottom: 8px; }
    @media print { .no-print { display:none; } }
  </style>
</head>
<body>
  <div class="no-print"><button onclick="window.print()">Drucken</button></div>
  <h3>Katalog Druckansicht</h3>
  <table>
    <thead><tr><th>ID</th><th>Materialpreis</th><th>Arbeitszeit</th><th>Verlust</th><th>Galvanik</th><th>Furnituren Au</th><th>X</th><th>Y</th><th>AB</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['materialpreis_metall']) ?></td>
        <td><?= htmlspecialchars((string)$r['arbeitszeit']) ?></td>
        <td><?= htmlspecialchars((string)$r['verlust']) ?></td>
        <td><?= htmlspecialchars((string)$r['galvanik']) ?></td>
        <td><?= htmlspecialchars((string)$r['furnituren_au_750_333']) ?></td>
        <td><?= htmlspecialchars((string)$r['x_basis']) ?></td>
        <td><?= htmlspecialchars((string)$r['y_aufgerundet']) ?></td>
        <td><?= htmlspecialchars((string)$r['ab_vk_aufgerundet']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
