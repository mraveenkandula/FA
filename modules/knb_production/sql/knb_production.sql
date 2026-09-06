-- Multi-stage production tracking: raw material -> semi-finished goods ->
-- finished goods, with yield % and quality params captured per batch. This
-- is the real dairy-specific gap TechCloud's FG/SFG work-order split doesn't
-- actually close (it tracks two production stages, but never records yield
-- or quality).
CREATE TABLE IF NOT EXISTS `0_production_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_date` date NOT NULL,
  `stage` varchar(20) NOT NULL COMMENT 'RM_TO_SFG or SFG_TO_FG',
  `input_item` varchar(20) DEFAULT NULL,
  `input_qty` double NOT NULL DEFAULT 0,
  `output_item` varchar(20) DEFAULT NULL,
  `output_qty` double NOT NULL DEFAULT 0,
  `yield_percent` double DEFAULT NULL,
  `fat_content` double DEFAULT NULL,
  `moisture_percent` double DEFAULT NULL,
  `incharge_employee_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stage` (`stage`),
  KEY `batch_date` (`batch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
