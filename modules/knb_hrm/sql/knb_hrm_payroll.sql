-- Payroll/PF/ESI/PT/loans/holidays/grievances/local-conveyance/notice-period
-- data model - the real TechCloud data export confirmed these as genuinely
-- used features (real payroll runs, real professional-tax slabs, real
-- employee loans). Payroll *processing* (computing a run, approving it,
-- generating payslips) stays out of scope until the accountant signs off
-- on the compliance rules - these tables and their inquiry pages only
-- expose the real historical data, read-only.
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `pan_no` varchar(20) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `aadhaar_no` varchar(20) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `bank_name` varchar(100) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `bank_account_number` varchar(60) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `ifsc_code` varchar(50) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `pf_no` varchar(90) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `esi_no` varchar(90) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `uan_no` varchar(90) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `father_name` varchar(250) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `mother_name` varchar(250) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `blood_group` varchar(20) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `emergency_contact` varchar(100) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `official_email` varchar(100) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `zone_id` varchar(111) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `branch_name` varchar(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `0_knb_payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `payroll_no` int(11) NOT NULL DEFAULT 0,
  `period_from` date DEFAULT NULL,
  `period_to` date DEFAULT NULL,
  `days` int(11) NOT NULL DEFAULT 0,
  `paid_days` int(11) NOT NULL DEFAULT 0,
  `basic_pay` double NOT NULL DEFAULT 0,
  `hra` double NOT NULL DEFAULT 0,
  `payable_amount` double NOT NULL DEFAULT 0,
  `salary_amount` double NOT NULL DEFAULT 0,
  `epf` double DEFAULT 0,
  `esi` double DEFAULT 0,
  `loan_deduction` double NOT NULL DEFAULT 0,
  `other_deduction` double NOT NULL DEFAULT 0,
  `professional_tax` double NOT NULL DEFAULT 0,
  `income_tax` double NOT NULL DEFAULT 0,
  `incentives` double NOT NULL DEFAULT 0,
  `bonus_amount` double NOT NULL DEFAULT 0,
  `advance_amount` double NOT NULL DEFAULT 0,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation_id` int(11) DEFAULT NULL,
  `grade_id` tinyint(2) NOT NULL DEFAULT 0,
  `pay_rule` varchar(15) DEFAULT NULL,
  `pay_amount` double NOT NULL DEFAULT 0,
  `pay_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_basic` tinyint(1) NOT NULL DEFAULT 0,
  `effective_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_professional_tax_slabs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slab_type` int(11) NOT NULL DEFAULT 0,
  `basic_amount` double NOT NULL DEFAULT 0,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_employee_incentives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `stock_id` int(11) DEFAULT NULL,
  `brand` varchar(200) DEFAULT NULL,
  `incentive_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_employee_loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `loan_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `loan_type` varchar(200) DEFAULT NULL,
  `payment_type` varchar(200) DEFAULT NULL,
  `installment_amount` int(11) NOT NULL DEFAULT 0,
  `installment_month` int(11) NOT NULL DEFAULT 0,
  `loan_start_date` date DEFAULT NULL,
  `loan_end_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pending_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `holiday_date` date NOT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_local_conveyance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `travel_date` date NOT NULL,
  `debtor_no` int(11) DEFAULT 0,
  `from_place` varchar(500) DEFAULT NULL,
  `to_place` varchar(500) DEFAULT NULL,
  `mode` varchar(50) DEFAULT NULL,
  `km` bigint(20) NOT NULL DEFAULT 0,
  `parking` double NOT NULL DEFAULT 0,
  `total` double NOT NULL DEFAULT 0,
  `remarks` varchar(1500) DEFAULT NULL,
  `approval_status` int(11) NOT NULL DEFAULT 0,
  `inactive` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_notice_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `notice_days` int(11) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_payroll_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
