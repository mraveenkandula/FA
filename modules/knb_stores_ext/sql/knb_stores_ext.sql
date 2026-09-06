-- Stock Verification (physical/cycle stock count reconciliation) - confirmed
-- against TechCloud's live Stores menu, no equivalent in core FrontAccounting.
CREATE TABLE IF NOT EXISTS `0_knb_stock_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verify_date` date NOT NULL,
  `loc_code` varchar(5) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `adjustment_trans_no` int(11) DEFAULT NULL COMMENT 'ST_INVADJUST trans_no once finalized, if any variances existed',
  `finalized` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `loc_code` (`loc_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_stock_verification_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verification_id` int(11) NOT NULL,
  `stock_id` varchar(20) NOT NULL,
  `system_qty` double NOT NULL DEFAULT 0,
  `counted_qty` double NOT NULL DEFAULT 0,
  `variance_qty` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `verification_id` (`verification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
