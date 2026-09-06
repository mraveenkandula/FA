-- Dairy-specific quality/yield metadata attached to real FrontAccounting Work
-- Orders. Earlier version of this module logged batches into its own table,
-- disconnected from core stock_moves/GL -- input material was never actually
-- deducted from inventory and output was never actually added, so on-hand
-- quantities and product costing were both silently wrong. This version
-- drives the batch through the core Work Order engine (add_work_order,
-- release_work_order, add_work_order_issue, work_order_produce) and only
-- keeps the fields core has no place for (fat/moisture/incharge) here,
-- keyed to the real workorder id.
CREATE TABLE IF NOT EXISTS `0_knb_production_quality` (
  `workorder_id` int(11) NOT NULL,
  `stage` varchar(20) NOT NULL COMMENT 'RM_TO_SFG or SFG_TO_FG',
  `fat_content` double DEFAULT NULL,
  `moisture_percent` double DEFAULT NULL,
  `incharge_employee_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`workorder_id`),
  KEY `stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
