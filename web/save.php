<?php
require 'config.php';
require 'calculate.php';

$artikelname   = trim($_POST['artikelname'] ?? '');
$artikelgruppe = trim($_POST['artikelgruppe'] ?? '');
$profileCode   = $_POST['profile_code'] ?? '';
$gewicht       = (float)($_POST['gewicht_g'] ?? 0);
$zeit          = (float)($_POST['arbeitszeit_h'] ?? 0);

if ($artikelname === '' || $profileCode === '' || $gewicht < 0 || $zeit < 0) {
    die("Ungültige Eingaben.");
}

try {
    $calc = calculatePrice($pdo, $profileCode, $gewicht, $zeit);

    $pdo->beginTransaction();

    // Produkt anlegen
    $st = $pdo->prepare("
      INSERT INTO products (artikelname, artikelgruppe, profile_code, basisgewicht_g, basis_arbeitszeit_h)
      VALUES (?, ?, ?, ?, ?)
    ");
    $st->execute([$artikelname, $artikelgruppe, $profileCode, $gewicht, $zeit]);
    $productId = (int)$pdo->lastInsertId();

    // Berechnung speichern
    $st = $pdo->prepare("
      INSERT INTO price_calculations (
        product_id, profile_code, gewicht_g, arbeitszeit_h,
        materialpreis_eur_g, verlust_ratio, stundensatz_eur_h, fix_r3_eur, fix_s_eur, vk_multiplikator,
        mat_mit_verlust, x_basis, y_gerundet, aa_mult, ab_vk
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $st->execute([
      $productId, $profileCode, $gewicht, $zeit,
      $calc['materialpreis'], $calc['verlust'], $calc['stundensatz'], $calc['fix_r3'], $calc['fix_s'], $calc['mult_vk'],
      $calc['mat_mit_verlust'], $calc['x'], $calc['y'], $calc['aa'], $calc['ab']
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Fehler: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Gespeichert</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="alert alert-success">Berechnung gespeichert.</div>
  <div class="card card-body">
    <h5>Ergebnis</h5>
    <ul>
      <li>Material mit Verlust: <b><?=number_format($calc['mat_mit_verlust'],2,',','.')?> €</b></li>
      <li>X Basis: <b><?=number_format($calc['x'],2,',','.')?> €</b></li>
      <li>Y (aufgerundet): <b><?=number_format($calc['y'],2,',','.')?> €</b></li>
      <li>AA (Y * <?=$calc['mult_vk']?>): <b><?=number_format($calc['aa'],2,',','.')?> €</b></li>
      <li>AB VK (aufgerundet): <b><?=number_format($calc['ab'],2,',','.')?> €</b></li>
    </ul>
    <a class="btn btn-primary" href="index.php">Neu</a>
    <a class="btn btn-outline-secondary" href="list.php">Historie</a>
  </div>
</div>
</body>
</html>