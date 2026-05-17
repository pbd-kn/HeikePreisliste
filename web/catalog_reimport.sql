-- Reimport script for updated ODS/CSV using the current catalog_items column names.
-- Usage in phpMyAdmin:
-- 1) Import CSV first into table catalog_items_import with the same structure as catalog_items.
-- 2) Execute this script to replace the live data.

USE preisliste_db;

CREATE TABLE IF NOT EXISTS catalog_items_import LIKE catalog_items;

START TRANSACTION;

DELETE FROM catalog_items;

INSERT INTO catalog_items (
  artikel_code, bild, ag_in_g, ag_incl_verlust, au_in_g, au_incl_verlust,
  zeit_in_h, artikel_zusatz, stueck_1, steine_perlen_ek, steine_messe,
  artikel_2, stueck_2, furnituren_steine_ek, steine_messe_2,
  plattierung_oxidation, schnur_2, leer_1, leer_2, kategorie,
  subkategorie, artikelnr, artikel, ek, preis_stueck_ek, preis_paar_ek,
  vkstk_ek_2_5_ungerundet, preis_stueck_2_5,
  paarpreis_vk_2_5_ungerundet, preis_paar_2_5, beschreibung,
  nochmals_artikel, vkstk_ek_2_3_ungerundet, preis_stueck_2_3,
  vkpaar_ek_2_3_ungerundet, preis_paar_2_3, reserve_1, reserve_2,
  reserve_3, reserve_4
)
SELECT
  artikel_code, bild, ag_in_g, ag_incl_verlust, au_in_g, au_incl_verlust,
  zeit_in_h, artikel_zusatz, stueck_1, steine_perlen_ek, steine_messe,
  artikel_2, stueck_2, furnituren_steine_ek, steine_messe_2,
  plattierung_oxidation, schnur_2, leer_1, leer_2, kategorie,
  subkategorie, artikelnr, artikel, ek, preis_stueck_ek, preis_paar_ek,
  vkstk_ek_2_5_ungerundet, preis_stueck_2_5,
  paarpreis_vk_2_5_ungerundet, preis_paar_2_5, beschreibung,
  nochmals_artikel, vkstk_ek_2_3_ungerundet, preis_stueck_2_3,
  vkpaar_ek_2_3_ungerundet, preis_paar_2_3, reserve_1, reserve_2,
  reserve_3, reserve_4
FROM catalog_items_import;

COMMIT;
