-- Sales-side gaps confirmed against TechCloud that aren't covered by core
-- FrontAccounting or the existing knb_distribution module: employee sales
-- targets/incentives, journey planning, and lead capture.

CREATE TABLE IF NOT EXISTS `0_knb_sales_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_employee_id` int(11) NOT NULL,
  `period_month` date NOT NULL COMMENT 'first day of the target month',
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_period` (`sales_employee_id`,`period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_incentive_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_achievement_pct` double NOT NULL DEFAULT 0,
  `max_achievement_pct` double NOT NULL DEFAULT 0,
  `incentive_pct` double NOT NULL DEFAULT 0,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_journey_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_employee_id` int(11) NOT NULL,
  `plan_date` date NOT NULL,
  `beat_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_employee_id` (`sales_employee_id`),
  KEY `plan_date` (`plan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_sales_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `territory_id` int(11) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `beat_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New',
  `assigned_employee_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
