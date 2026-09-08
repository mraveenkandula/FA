CREATE TABLE IF NOT EXISTS `0_hr_expense_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- TA/DA (Travel Allowance / Dearness Allowance) manual claim entry reuses
-- this table instead of a new one - documentation copy only, this .sql file
-- is never auto-run for this hand-placed module (see
-- modules/knb_inventory_ext's price_import_db.inc precedent); the actual
-- self-healing mechanism is ensure_expense_claim_schema() in
-- modules/knb_hrm/manage/expense_claim_db.inc, called defensively from
-- add_expense_claim()/get_expense_claims().
ALTER TABLE `0_hr_expense_claims` ADD COLUMN IF NOT EXISTS `claim_type` varchar(20) NOT NULL DEFAULT 'Expense';
