-- Leave management - confirmed against TechCloud's live HRM menu (Add and
-- Manage Employee Leave Entry / Manage Employee Leave Approvals / Leave
-- Types), no equivalent in core FrontAccounting or the existing knb_hrm
-- attendance screens.
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `employment_status` varchar(20) NOT NULL DEFAULT 'Confirmed' COMMENT 'Confirmed or Probation';

CREATE TABLE IF NOT EXISTS `0_knb_leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `code` varchar(10) NOT NULL,
  `days_confirmed` double NOT NULL DEFAULT 0,
  `days_probation` double NOT NULL DEFAULT 0,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_leave_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `leave_days` double NOT NULL DEFAULT 1,
  `session_type` varchar(20) DEFAULT NULL COMMENT 'Full Day, First Half, Second Half',
  `reason` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending' COMMENT 'Pending, Approved, Rejected',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `applied_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
