<?php

require __DIR__ . '/config.php';
function parseEur(?string $v): float
{
    $v = trim((string)$v);
    if ($v === '') {
        return 0.0;
    }
    $v = str_replace(['€', ' '], '', $v);
    $v = str_replace('.', '', $v);
    $v = str_replace(',', '.', $v);
    return (float)$v;
}
function parsePercent(?string $v): float
{
    $raw = trim((string)$v);
    if ($raw === '') {
        return 0.0;
    }
    $raw = str_replace('%', '', $raw);
    $num = parseEur($raw);
    return $num / 100;
}
/**
 * Neue CSV-Struktur:
 *
 * artikel_code
 * bild
 * ag_in_g
 * ag_incl_verlust
 * au_in_g
 * au_incl_verlust
 * zeit_in_h
 * artikel_zusatz
 * ...
 */
function calcCatalog(array $row): array
{
    // -------------------------------------------------
    // BASISWERTE
    // -------------------------------------------------
    $agIncl = parseEur($row['ag_incl_verlust'] ?? '0');
    $auIncl = parseEur($row['au_incl_verlust'] ?? '0');
    // Arbeitszeit z.B. "15min=0,25"
    $zeitRaw = trim((string)($row['zeit_in_h'] ?? ''));
    $arbeitszeit = 0.0;
    if (preg_match('/=([0-9\.,]+)/', $zeitRaw, $m)) {
        $arbeitszeit = parseEur($m[1]);
    } else {
        $arbeitszeit = parseEur($zeitRaw);
    }
    // Zusatzkosten
    $plattierung = parseEur($row['plattierung_oxidation'] ?? '0');
    $schnur = parseEur($row['schnur_2'] ?? '0');
    // EK Werte
    $steineEk = parseEur($row['steine_perlen_ek'] ?? '0');
    $furniturenEk = parseEur($row['furnituren_steine_ek'] ?? '0');
    // -------------------------------------------------
    // KALKULATION
    // -------------------------------------------------
    $ek =
        $agIncl +
        $auIncl +
        $arbeitszeit +
        $plattierung +
        $schnur +
        $steineEk +
        $furniturenEk;
    // aufrunden
    $preisStueckEk = ceil($ek);
    // VK Faktor 2.5
    $vk25 = $preisStueckEk * 2.5;
    $preis25 = ceil($vk25);
    // VK Faktor 2.3
    $vk23 = $preisStueckEk * 2.3;
    $preis23 = ceil($vk23);
    return [
        // EK
        'ek' => number_format($ek, 2, '.', ''),
        // Stück EK
        'preis_stueck_ek' => number_format($preisStueckEk, 2, '.', ''),
        // Paar EK
        'preis_paar_ek' => number_format($preisStueckEk * 2, 2, '.', ''),
        // 2.5
        'vkstk_ek_2_5_ungerundet' => number_format($vk25, 2, '.', ''),
        'preis_stueck_2_5' => number_format($preis25, 2, '.', ''),
        'paarpreis_vk_2_5_ungerundet' => number_format($vk25 * 2, 2, '.', ''),
        'preis_paar_2_5' => number_format($preis25 * 2, 2, '.', ''),
        // 2.3
        'vkstk_ek_2_3_ungerundet' => number_format($vk23, 2, '.', ''),
        'preis_stueck_2_3' => number_format($preis23, 2, '.', ''),
        'vkpaar_ek_2_3_ungerundet' => number_format($vk23 * 2, 2, '.', ''),
        'preis_paar_2_3' => number_format($preis23 * 2, 2, '.', '')
    ];
}
