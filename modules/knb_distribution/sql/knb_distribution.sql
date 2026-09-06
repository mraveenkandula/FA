CREATE TABLE IF NOT EXISTS `0_sales_territories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_sales_towns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `territory_id` int(11) DEFAULT NULL,
  `district` varchar(60) DEFAULT NULL,
  `state` varchar(60) DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `territory_id` (`territory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_sales_beats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `town_id` int(11) DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `town_id` (`town_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- One row per FrontAccounting customer (debtors_master.debtor_no), attaching
-- the distribution-hierarchy classification without touching the core
-- customer maintenance screen.
CREATE TABLE IF NOT EXISTS `0_customer_distribution` (
  `debtor_no` int(11) NOT NULL,
  `person_type` varchar(30) NOT NULL DEFAULT 'Retailer',
  `territory_id` int(11) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `beat_id` int(11) DEFAULT NULL,
  `sales_employee_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`debtor_no`),
  KEY `territory_id` (`territory_id`),
  KEY `town_id` (`town_id`),
  KEY `beat_id` (`beat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
