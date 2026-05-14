<?php
require __DIR__ . '/config.php';

function parseEur(?string $v): float {
    $v = trim((string)$v);
    if ($v === '') return 0.0;
    $v = str_replace(['€', ' '], '', $v);
    $v = str_replace('.', '', $v);
    $v = str_replace(',', '.', $v);
    return (float)$v;
}

function parsePercent(?string $v): float {
    $raw = trim((string)$v);
    if ($raw === '') return 0.0;
    $raw = str_replace('%', '', $raw);
    $num = parseEur($raw);
    return $num / 100;
}

/**
 * ODS-orientierte Kernkalkulation:
 * X = material*(1+verlust) + arbeitszeit + fixkosten_r + fixkosten_s + zusatz_q
 * Y = ROUNDUP(X)
 * AA = Y * multiplikator (default 2.5)
 * AB = ROUNDUP(AA)
 */
function calcCatalog(array $row): array {
    $material = parseEur($row['materialpreis_metall'] ?? '0');
    $arbeitszeit = parseEur($row['arbeitszeit'] ?? '0');
    $verlust = parsePercent($row['verlust'] ?? '0');

    $fixR = parseEur($row['fixkosten_r'] ?? '0');
    $fixS = parseEur($row['fixkosten_s'] ?? '0');
    $zusatzQ = parseEur($row['zusatz_q'] ?? '0');

    $multiplikator = parseEur($row['aa_multiplikator'] ?? '2,5');
    if ($multiplikator <= 0) {
        $multiplikator = 2.5;
    }

    $xBasis = ($material * (1 + $verlust)) + $arbeitszeit + $fixR + $fixS + $zusatzQ;
    $yAufgerundet = ceil($xBasis);
    $aaWert = $yAufgerundet * $multiplikator;
    $abWert = ceil($aaWert);

    return [
        'x_basis' => $xBasis,
        'y_aufgerundet' => $yAufgerundet,
        'aa_multiplikator' => $aaWert,
        'ab_vk_aufgerundet' => $abWert,
        'spalte_ai' => $aaWert,
        'spalte_aj' => $abWert
    ];
}
