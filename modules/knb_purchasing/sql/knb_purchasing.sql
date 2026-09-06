CREATE TABLE IF NOT EXISTS `0_purchase_indents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_description` varchar(200) NOT NULL,
  `qty` double NOT NULL DEFAULT 0,
  `uom` varchar(20) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `requested_date` date NOT NULL,
  `required_by_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
