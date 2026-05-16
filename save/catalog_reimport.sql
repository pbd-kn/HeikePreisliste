-- Reimport script for updated ODS/CSV using semantic column names.
-- Usage in phpMyAdmin:
-- 1) Import CSV first into table catalog_items_import (same columns as catalog_items, without AUTO_INCREMENT id requirement)
-- 2) Execute this script.

USE preisliste_db;

-- 1) Ensure staging table exists with same structure
CREATE TABLE IF NOT EXISTS catalog_items_import LIKE catalog_items;

-- 2) Optional cleanup of staging before each import
-- TRUNCATE TABLE catalog_items_import;

-- 3) Replace productive data with staging data
START TRANSACTION;

TRUNCATE TABLE catalog_items;

INSERT INTO catalog_items (
  materialpreis_metall, arbeitszeit, verlust, galvanik,
  furnituren_au_750_333, furnituren_ag_925, colorit, schnur,
  verschluesse_gg_wg, verschluesse_925, verschluesse_edelstahl,
  stein_typ, stein_faktor, perle_typ, perle_faktor, furnituren_wg,
  zusatz_q, fixkosten_r, fixkosten_s, sonstiges_t, reparaturen,
  reparaturpreis, kalkulation_w, x_basis, y_aufgerundet,
  zwischenwert_z, aa_multiplikator, ab_vk_aufgerundet,
  spalte_ac, spalte_ad, spalte_ae, spalte_af, spalte_ag, spalte_ah,
  spalte_ai, spalte_aj, spalte_ak, spalte_al, spalte_am, spalte_an,
  source_row, imported_at
)
SELECT
  materialpreis_metall, arbeitszeit, verlust, galvanik,
  furnituren_au_750_333, furnituren_ag_925, colorit, schnur,
  verschluesse_gg_wg, verschluesse_925, verschluesse_edelstahl,
  stein_typ, stein_faktor, perle_typ, perle_faktor, furnituren_wg,
  zusatz_q, fixkosten_r, fixkosten_s, sonstiges_t, reparaturen,
  reparaturpreis, kalkulation_w, x_basis, y_aufgerundet,
  zwischenwert_z, aa_multiplikator, ab_vk_aufgerundet,
  spalte_ac, spalte_ad, spalte_ae, spalte_af, spalte_ag, spalte_ah,
  spalte_ai, spalte_aj, spalte_ak, spalte_al, spalte_am, spalte_an,
  source_row, COALESCE(imported_at, CURRENT_TIMESTAMP)
FROM catalog_items_import;

COMMIT;

-- 4) Optional: clear staging after successful import
-- TRUNCATE TABLE catalog_items_import;
