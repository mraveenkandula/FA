-- Audit log for the Bulk Price Import tool (modules/knb_inventory_ext/manage/price_import.php).
-- One row per stock_id/sales_type_id pair actually written to 0_prices by an
-- import batch, recording the price it replaced (if any) so a bad import can
-- be investigated/reversed by hand. Kept separate from core's
-- 0_audit_trail, which is keyed to GL transaction types (systypes) that a
-- bulk price update is not.
CREATE TABLE IF NOT EXISTS `0_knb_price_import_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_batch` varchar(20) NOT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `stock_id` varchar(20) NOT NULL,
  `sales_type_id` int(11) NOT NULL,
  `curr_abrev` char(3) NOT NULL DEFAULT '',
  `old_price` double DEFAULT NULL,
  `new_price` double NOT NULL,
  `action` varchar(10) NOT NULL,
  `imported_by` int(11) DEFAULT NULL,
  `imported_by_name` varchar(60) DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `import_batch` (`import_batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
