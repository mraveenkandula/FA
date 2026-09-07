-- Shift Types and Asset Allocations - confirmed against TechCloud's live
-- HRM menu and its actual data (Manage Shift Types, Employees Asset
-- Allocations). No equivalent anywhere in core FrontAccounting or the
-- existing knb_hrm tables.
--
-- knb_holidays is NOT created here: it already exists (created in an
-- earlier session not covered by this one's context, confirmed via
-- CREATE_TIME - 2026-09-06, a day before this file was written) with 10
-- rows of real holiday data matching TechCloud's exactly, and is already
-- read by modules/knb_hrm/inquiry/hr_records_db.inc's get_holidays().
-- Its actual schema is `name` varchar(100), not the varchar(60) an
-- earlier draft of this migration assumed - there was never a maintenance
-- (add/edit/delete) page for it though, which modules/knb_hrm/manage/
-- holidays.php now adds against the existing table as-is.

CREATE TABLE IF NOT EXISTS `0_knb_shift_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(60) NOT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_asset_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `allocation_date` date NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
