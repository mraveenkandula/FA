-- Expense Booking - confirmed real gap against the TechCloud commercial
-- agreement's mobile app scope ("Expense Booking"). No existing table
-- anywhere in this codebase captures a field rep's travel/food/lodging
-- claims - separate migration file (not added to knb_sales_ext.sql) so it
-- actually runs against installs that already applied that one.
CREATE TABLE IF NOT EXISTS `0_knb_expense_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_employee_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Pending, 1=Approved, 2=Rejected',
  `approved_by` varchar(60) DEFAULT NULL COMMENT 'FA username of the approving user',
  `approved_date` datetime DEFAULT NULL,
  `created_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_employee_id` (`sales_employee_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
