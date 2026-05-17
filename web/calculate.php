<?php

function catalogColumns(): array
{
    return [
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
}

function catalogColumnAliases(): array
{
    return [
        'artikel' => 'artikel_code',
        'bild' => 'bild',
        'ag_in_g' => 'ag_in_g',
        'ag_incl_verlust' => 'ag_incl_verlust',
        'au_in_g' => 'au_in_g',
        'au_incl_verlust' => 'au_incl_verlust',
        'zeit_in_h' => 'zeit_in_h',
        'artikel_zusatz' => 'artikel_zusatz',
        'stueck' => 'stueck_1',
        'steine_perlen_ek' => 'steine_perlen_ek',
        'steine_messe' => 'steine_messe',
        'furnituren_steine_ek' => 'furnituren_steine_ek',
        'steine_messe_2' => 'steine_messe_2',
        'plattierung_oxidation' => 'plattierung_oxidation',
        'schnur' => 'schnur_2',
        'kategorie' => 'kategorie',
        'subkategorie' => 'subkategorie',
        'artikelnr' => 'artikelnr',
        'ek' => 'ek',
        'preisstueckek' => 'preis_stueck_ek',
        'preispaarek' => 'preis_paar_ek',
        'preisstueck2_5' => 'preis_stueck_2_5',
        'preispaar2_5' => 'preis_paar_2_5',
        'beschreibung' => 'beschreibung',
        'nochmalsartikel' => 'nochmals_artikel',
        'preisstueck2_3' => 'preis_stueck_2_3',
        'preispaar2_3' => 'preis_paar_2_3'
    ];
}

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

function loadGlobalParams(PDO $pdo): array
{
    $params = [];
    $rows = $pdo
        ->query("SELECT param_key, param_value FROM global_params")
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $params[(string)$row['param_key']] = (float)$row['param_value'];
    }

    return $params;
}

function defaultCatalogFormulaRules(): array
{
    return [
        'ag_incl_verlust' => 'default_ag_incl_verlust',
        'au_incl_verlust' => 'default_au_incl_verlust',
        'material' => 'default_material',
        'arbeitszeit' => 'default_arbeitszeit',
        'stueck_1_messe' => 'default_stueck_1_messe',
        'stueck_2_messe' => 'default_stueck_2_messe',
        'plattierung_oxidation' => 'default_plattierung_oxidation',
        'schnur_2' => 'default_schnur_2',
        'fixkosten_r5' => 'default_fixkosten_r5',
        'fixkosten_s' => 'default_fixkosten_s',
        'metall_zuschlag_s' => 'default_metall_zuschlag_s',
        'ek' => 'material + arbeitszeit + stueck_1_messe + stueck_2_messe + plattierung_oxidation + schnur_2 + fixkosten_r5 + fixkosten_s + metall_zuschlag_s',
        'factor_25' => '2.5',
        'factor_23' => '2.3',
        'preis_stueck_ek' => 'default_preis_stueck_ek',
        'preis_paar_ek' => 'default_preis_paar_ek',
        'vkstk_ek_2_5_ungerundet' => 'default_vkstk_ek_2_5_ungerundet',
        'preis_stueck_2_5' => 'default_preis_stueck_2_5',
        'paarpreis_vk_2_5_ungerundet' => 'default_paarpreis_vk_2_5_ungerundet',
        'preis_paar_2_5' => 'default_preis_paar_2_5',
        'vkstk_ek_2_3_ungerundet' => 'default_vkstk_ek_2_3_ungerundet',
        'preis_stueck_2_3' => 'default_preis_stueck_2_3',
        'vkpaar_ek_2_3_ungerundet' => 'default_vkpaar_ek_2_3_ungerundet',
        'preis_paar_2_3' => 'default_preis_paar_2_3'
    ];
}

function catalogFormulaRuleLabels(): array
{
    return [
        'ag_incl_verlust' => 'AG incl. Verlust',
        'au_incl_verlust' => 'AU incl. Verlust',
        'material' => 'Material',
        'arbeitszeit' => 'Arbeitszeit',
        'stueck_1_messe' => 'Stueck 1 / Messe',
        'stueck_2_messe' => 'Stueck 2 / Messe',
        'plattierung_oxidation' => 'Plattierung / Oxidation',
        'schnur_2' => 'Schnur 2',
        'fixkosten_r5' => 'Fixkosten R5',
        'fixkosten_s' => 'Fixkosten S',
        'metall_zuschlag_s' => 'Metall-Zuschlag S',
        'ek' => 'EK',
        'factor_25' => 'Faktor VK 2.5',
        'factor_23' => 'Faktor VK 2.3',
        'preis_stueck_ek' => 'Preis Stueck EK',
        'preis_paar_ek' => 'Preis Paar EK',
        'vkstk_ek_2_5_ungerundet' => 'VK Stueck 2.5 ungerundet',
        'preis_stueck_2_5' => 'Preis Stueck 2.5',
        'paarpreis_vk_2_5_ungerundet' => 'Paarpreis 2.5 ungerundet',
        'preis_paar_2_5' => 'Preis Paar 2.5',
        'vkstk_ek_2_3_ungerundet' => 'VK Stueck 2.3 ungerundet',
        'preis_stueck_2_3' => 'Preis Stueck 2.3',
        'vkpaar_ek_2_3_ungerundet' => 'VK Paar 2.3 ungerundet',
        'preis_paar_2_3' => 'Preis Paar 2.3'
    ];
}

function ensureCatalogFormulaRules(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS catalog_formula_rules (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            artikel_code VARCHAR(150) NOT NULL DEFAULT '',
            target_field VARCHAR(64) NOT NULL,
            formula TEXT NOT NULL,
            label VARCHAR(120) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_formula_rule (artikel_code, target_field)
        )
    ");

    $pdo->exec("
        DELETE r1 FROM catalog_formula_rules r1
        INNER JOIN catalog_formula_rules r2
            ON COALESCE(r1.artikel_code, '') = COALESCE(r2.artikel_code, '')
            AND r1.target_field = r2.target_field
            AND r1.id > r2.id
    ");
    $pdo->exec("UPDATE catalog_formula_rules SET artikel_code = '' WHERE artikel_code IS NULL");
    try {
        $pdo->exec("ALTER TABLE catalog_formula_rules MODIFY artikel_code VARCHAR(150) NOT NULL DEFAULT ''");
    } catch (Throwable $e) {
        // Already migrated or database does not require the alteration.
    }

    $defaults = defaultCatalogFormulaRules();
    $st = $pdo->prepare("
        INSERT INTO catalog_formula_rules (artikel_code, target_field, formula, label, active)
        VALUES ('', ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE formula = formula
    ");

    foreach ($defaults as $field => $formula) {
        $st->execute([$field, $formula, 'Default ' . $field]);
    }
}

function loadCatalogFormulaRules(PDO $pdo, ?string $articleCode = null): array
{
    ensureCatalogFormulaRules($pdo);
    $rules = defaultCatalogFormulaRules();

    $st = $pdo->prepare("
        SELECT target_field, formula
        FROM catalog_formula_rules
        WHERE active = 1
        AND artikel_code = ''
    ");
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rules[(string)$row['target_field']] = (string)$row['formula'];
    }

    if ($articleCode !== null && trim($articleCode) !== '') {
        $st = $pdo->prepare("
            SELECT target_field, formula
            FROM catalog_formula_rules
            WHERE active = 1
            AND artikel_code = ?
        ");
        $st->execute([trim($articleCode)]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rules[(string)$row['target_field']] = (string)$row['formula'];
        }
    }

    return $rules;
}

function loadCatalogFormulaRuleRows(PDO $pdo, ?string $articleCode = null): array
{
    ensureCatalogFormulaRules($pdo);
    $articleCode = trim((string)$articleCode);

    $st = $pdo->prepare("
        SELECT id, artikel_code, target_field, formula, label, active, updated_at
        FROM catalog_formula_rules
        WHERE artikel_code = ?
        ORDER BY target_field
    ");
    $st->execute([$articleCode]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function saveCatalogFormulaRule(PDO $pdo, string $articleCode, string $targetField, string $formula, string $label = ''): void
{
    ensureCatalogFormulaRules($pdo);
    $articleCode = trim($articleCode);
    $targetField = trim($targetField);
    $formula = trim($formula);
    $label = trim($label);
    $defaults = defaultCatalogFormulaRules();

    if (!array_key_exists($targetField, $defaults)) {
        throw new RuntimeException('Unbekanntes Formelfeld: ' . $targetField);
    }
    if ($formula === '') {
        throw new RuntimeException('Formel darf nicht leer sein.');
    }

    $st = $pdo->prepare("
        INSERT INTO catalog_formula_rules (artikel_code, target_field, formula, label, active)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            formula = VALUES(formula),
            label = VALUES(label),
            active = 1
    ");
    $st->execute([$articleCode, $targetField, $formula, $label]);
}

function deleteCatalogFormulaRule(PDO $pdo, string $articleCode, string $targetField): void
{
    ensureCatalogFormulaRules($pdo);
    $articleCode = trim($articleCode);
    if ($articleCode === '') {
        throw new RuntimeException('Default-Regeln koennen nicht geloescht werden.');
    }

    $st = $pdo->prepare("
        DELETE FROM catalog_formula_rules
        WHERE artikel_code = ?
        AND target_field = ?
    ");
    $st->execute([$articleCode, trim($targetField)]);
}

function catalogFormulaVars(array $row, array $globals = []): array
{
    $vars = $globals;
    foreach ($row as $key => $value) {
        if (is_string($key) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            $vars[$key] = parseEur((string)$value);
        }
    }
    return $vars;
}

function evalCatalogFormula(string $formula, array $vars): float
{
    $formula = trim($formula);
    if ($formula === '') {
        return 0.0;
    }

    if ($formula[0] === '=') {
        $formula = trim(substr($formula, 1));
    }

    $tokens = preg_split(
        '/([()+\-*\/])/',
        $formula,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    );
    $phpExpression = '';

    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }

        if (preg_match('/^[()+\-*\/]$/', $token)) {
            $phpExpression .= $token;
            continue;
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $token)) {
            if (!array_key_exists($token, $vars)) {
                throw new RuntimeException('Unbekannte Variable in Formel: ' . $token);
            }
            $phpExpression .= '(' . (float)$vars[$token] . ')';
            continue;
        }

        if (preg_match('/^[0-9]+([,.][0-9]+)?$/', $token)) {
            $phpExpression .= str_replace(',', '.', $token);
            continue;
        }

        throw new RuntimeException('Ungueltiger Ausdruck in Formel: ' . $token);
    }

    set_error_handler(function () {
        throw new RuntimeException('Formel konnte nicht berechnet werden.');
    });
    try {
        $result = eval('return ' . $phpExpression . ';');
    } finally {
        restore_error_handler();
    }

    return (float)$result;
}

function parseFormulaOrEur(?string $value, array $vars): float
{
    $raw = trim((string)$value);
    if ($raw !== '' && $raw[0] === '=') {
        return evalCatalogFormula($raw, $vars);
    }
    return parseEur($raw);
}

function globalParam(array $globals, string $key, float $default = 0.0): float
{
    return array_key_exists($key, $globals) ? (float)$globals[$key] : $default;
}

function parseWorkHours(string $value): float
{
    $hours = parseEur($value);
    $normalized = str_replace(',', '.', trim($value));
    if (preg_match('/^[0-9]+\.[0-9]{2}$/', $normalized)) {
        $fraction = $hours - floor($hours);
        if (abs($fraction - 0.67) < 0.0001) {
            return $hours - 0.004;
        }
        if (abs($fraction - 0.33) < 0.0001) {
            return $hours + 0.00333;
        }
        if (abs($fraction - 0.83) < 0.0001) {
            return $hours + 0.003;
        }
    }
    return $hours;
}

function catalogMetalRate(array $row, array $globals): float
{
    $articleCode = strtoupper((string)($row['artikel_code'] ?? ''));
    if (parseEur($row['au_incl_verlust'] ?? '') > 0 || parseEur($row['au_in_g'] ?? '') > 0) {
        if (str_contains($articleCode, 'WG')) {
            return globalParam($globals, 'A7');
        }
        return globalParam($globals, 'A5');
    }

    return globalParam($globals, 'A3');
}

function catalogCalculationParts(array $row, array $globals = []): array
{
    $vars = catalogFormulaVars($row, $globals);
    $agIn = parseFormulaOrEur($row['ag_in_g'] ?? '0', $vars);
    $auIn = parseFormulaOrEur($row['au_in_g'] ?? '0', $vars);

    $agInclRaw = trim((string)($row['ag_incl_verlust'] ?? ''));
    $agIncl = $agInclRaw !== ''
        ? parseFormulaOrEur($agInclRaw, $vars)
        : ($agIn > 0 ? $agIn * (1 + globalParam($globals, 'C3')) : 0.0);

    $auInclRaw = trim((string)($row['au_incl_verlust'] ?? ''));
    $auIncl = $auInclRaw !== ''
        ? parseFormulaOrEur($auInclRaw, $vars)
        : ($auIn > 0 ? $auIn * (1 + globalParam($globals, 'C5')) : 0.0);

    $zeitRaw = trim((string)($row['zeit_in_h'] ?? ''));
    if (preg_match('/=([0-9\.,]+)/', $zeitRaw, $m)) {
        $arbeitszeit = parseWorkHours($m[1]);
    } elseif ($zeitRaw !== '' && $zeitRaw[0] === '=') {
        $arbeitszeit = evalCatalogFormula($zeitRaw, $vars);
    } else {
        $arbeitszeit = parseWorkHours($zeitRaw);
    }

    $stueck1 = parseFormulaOrEur($row['stueck_1'] ?? '0', $vars);
    $steineEk = parseFormulaOrEur($row['steine_perlen_ek'] ?? '0', $vars);
    $steineMesseRaw = trim((string)($row['steine_messe'] ?? ''));
    $steineMesse = $steineMesseRaw !== ''
        ? parseFormulaOrEur($steineMesseRaw, $vars)
        : ($steineEk * 1.5);

    $stueck2 = parseFormulaOrEur($row['stueck_2'] ?? '0', $vars);
    $furniturenEk = parseFormulaOrEur($row['furnituren_steine_ek'] ?? '0', $vars);
    $steineMesse2Raw = trim((string)($row['steine_messe_2'] ?? ''));
    $steineMesse2 = $steineMesse2Raw !== ''
        ? parseFormulaOrEur($steineMesse2Raw, $vars)
        : ($furniturenEk * 1.5);

    $metalIncl = $auIncl > 0 ? $auIncl : $agIncl;
    $metalRate = catalogMetalRate($row, $globals);
    $articleCode = strtoupper((string)($row['artikel_code'] ?? ''));
    $isGold = $auIncl > 0;
    $metalSurcharge = 0.0;
    $fixedSurcharge = 0.0;

    if ($isGold) {
        if (str_contains($articleCode, 'AH8')) {
            $metalSurcharge = $auIncl * globalParam($globals, 'S11');
        } else {
            $fixedSurcharge = globalParam($globals, 'S9');
        }
    } elseif (strtolower((string)($row['kategorie'] ?? '')) === 'ohrstecker') {
        $fixedSurcharge = globalParam($globals, 'R3') + globalParam($globals, 'S3');
    } else {
        $metalSurcharge = $agIncl * globalParam($globals, 'S7');
    }

    return [
        'ag_incl_verlust' => $agIncl,
        'au_incl_verlust' => $auIncl,
        'material' => $metalIncl * $metalRate,
        'arbeitszeit' => $arbeitszeit * globalParam($globals, 'B3'),
        'stueck_1_messe' => $stueck1 * $steineMesse,
        'stueck_2_messe' => $stueck2 * $steineMesse2,
        'plattierung_oxidation' => parseFormulaOrEur($row['plattierung_oxidation'] ?? '0', $vars),
        'schnur_2' => parseFormulaOrEur($row['schnur_2'] ?? '0', $vars),
        'fixkosten_r5' => $isGold || strtolower((string)($row['kategorie'] ?? '')) === 'ohrstecker'
            ? 0.0
            : globalParam($globals, 'R5'),
        'fixkosten_s' => $fixedSurcharge,
        'metall_zuschlag_s' => $metalSurcharge
    ];
}

function configuredCatalogCalculationParts(array $row, array $globals = [], array $rules = []): array
{
    $parts = catalogCalculationParts($row, $globals);
    $rules = array_merge(defaultCatalogFormulaRules(), $rules);
    $formulaVars = array_merge(catalogFormulaVars($row, $globals), $parts);

    foreach ($parts as $field => $value) {
        $formulaVars['default_' . $field] = $value;
    }

    foreach (array_keys($parts) as $field) {
        if (!array_key_exists($field, $rules)) {
            continue;
        }
        $parts[$field] = evalCatalogFormula($rules[$field], $formulaVars);
        $formulaVars[$field] = $parts[$field];
    }

    return $parts;
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
function calcCatalogBase(array $row, array $globals = [], array $rules = []): array
{
    // -------------------------------------------------
    // BASISWERTE
    // -------------------------------------------------
    $rules = array_merge(defaultCatalogFormulaRules(), $rules);
    $parts = configuredCatalogCalculationParts($row, $globals, $rules);
    $formulaVars = array_merge(catalogFormulaVars($row, $globals), $parts);
    foreach (catalogCalculationParts($row, $globals) as $field => $value) {
        $formulaVars['default_' . $field] = $value;
    }
    // -------------------------------------------------
    // KALKULATION
    // -------------------------------------------------
    $ek = evalCatalogFormula($rules['ek'], $formulaVars);
    // aufrunden
    $preisStueckEk = ceil($ek);
    // VK Faktor 2.5
    $factor25 = evalCatalogFormula($rules['factor_25'], $formulaVars);
    $vk25 = $preisStueckEk * $factor25;
    $preis25 = ceil($vk25);
    // VK Faktor 2.3
    $factor23 = evalCatalogFormula($rules['factor_23'], $formulaVars);
    $vk23 = $preisStueckEk * $factor23;
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

function calcCatalog(array $row, array $globals = [], array $rules = []): array
{
    $rules = array_merge(defaultCatalogFormulaRules(), $rules);
    $parts = configuredCatalogCalculationParts($row, $globals, $rules);
    $formulaVars = array_merge(catalogFormulaVars($row, $globals), $parts);

    foreach (catalogCalculationParts($row, $globals) as $field => $value) {
        $formulaVars['default_' . $field] = $value;
    }

    $ek = evalCatalogFormula($rules['ek'], $formulaVars);
    $formulaVars['ek'] = $ek;
    $formulaVars['default_ek'] = $ek;

    $preisStueckEk = ceil($ek);
    $factor25 = evalCatalogFormula($rules['factor_25'], $formulaVars);
    $vk25 = $preisStueckEk * $factor25;
    $preis25 = ceil($vk25);
    $factor23 = evalCatalogFormula($rules['factor_23'], $formulaVars);
    $vk23 = $preisStueckEk * $factor23;
    $preis23 = ceil($vk23);

    $output = [
        'ek' => $ek,
        'preis_stueck_ek' => $preisStueckEk,
        'preis_paar_ek' => $preisStueckEk * 2,
        'vkstk_ek_2_5_ungerundet' => $vk25,
        'preis_stueck_2_5' => $preis25,
        'paarpreis_vk_2_5_ungerundet' => $vk25 * 2,
        'preis_paar_2_5' => $preis25 * 2,
        'vkstk_ek_2_3_ungerundet' => $vk23,
        'preis_stueck_2_3' => $preis23,
        'vkpaar_ek_2_3_ungerundet' => $vk23 * 2,
        'preis_paar_2_3' => $preis23 * 2
    ];

    foreach ($output as $field => $value) {
        $formulaVars[$field] = $value;
        $formulaVars['default_' . $field] = $value;
    }

    foreach ([
        'preis_stueck_ek',
        'preis_paar_ek',
        'vkstk_ek_2_5_ungerundet',
        'preis_stueck_2_5',
        'paarpreis_vk_2_5_ungerundet',
        'preis_paar_2_5',
        'vkstk_ek_2_3_ungerundet',
        'preis_stueck_2_3',
        'vkpaar_ek_2_3_ungerundet',
        'preis_paar_2_3'
    ] as $field) {
        $output[$field] = evalCatalogFormula($rules[$field], $formulaVars);
        $formulaVars[$field] = $output[$field];
    }

    return [
        'ek' => number_format($output['ek'], 2, '.', ''),
        'preis_stueck_ek' => number_format($output['preis_stueck_ek'], 2, '.', ''),
        'preis_paar_ek' => number_format($output['preis_paar_ek'], 2, '.', ''),
        'vkstk_ek_2_5_ungerundet' => number_format($output['vkstk_ek_2_5_ungerundet'], 2, '.', ''),
        'preis_stueck_2_5' => number_format($output['preis_stueck_2_5'], 2, '.', ''),
        'paarpreis_vk_2_5_ungerundet' => number_format($output['paarpreis_vk_2_5_ungerundet'], 2, '.', ''),
        'preis_paar_2_5' => number_format($output['preis_paar_2_5'], 2, '.', ''),
        'vkstk_ek_2_3_ungerundet' => number_format($output['vkstk_ek_2_3_ungerundet'], 2, '.', ''),
        'preis_stueck_2_3' => number_format($output['preis_stueck_2_3'], 2, '.', ''),
        'vkpaar_ek_2_3_ungerundet' => number_format($output['vkpaar_ek_2_3_ungerundet'], 2, '.', ''),
        'preis_paar_2_3' => number_format($output['preis_paar_2_3'], 2, '.', '')
    ];
}
