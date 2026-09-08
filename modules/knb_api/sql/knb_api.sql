-- Phase 2 (mobile SFA app) foundation: token auth for field employees, and
-- the outlet-master fields (GPS, secondary phone, GST, type, photos) the
-- app's outlet-creation + duplicate-detection flow needs. knb_api has no
-- menu pages (it's a JSON API only), so unlike every other knb_* module
-- there's no hooks.php/installed_extensions entry - these tables are just
-- applied directly.

ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `mobile_username` varchar(30) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `mobile_password_hash` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `0_knb_api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Outlet master extensions - GPS coordinates (captured at outlet creation,
-- used both for the duplicate-detection radius check and later for
-- geofencing visits), secondary phone/GST (used in the dedup check too),
-- outlet type, and who created it.
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `gps_lat` decimal(10,7) DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `gps_lng` decimal(10,7) DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `secondary_phone` varchar(20) DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `gst_no` varchar(20) DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `outlet_type` varchar(30) DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `created_by_employee_id` int(11) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `0_knb_outlet_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debtor_no` int(11) NOT NULL,
  `photo_type` varchar(20) NOT NULL COMMENT 'Board, Chiller, Display, Other',
  `file_path` varchar(255) NOT NULL,
  `uploaded_by_employee_id` int(11) DEFAULT NULL,
  `uploaded_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `debtor_no` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Camera/media cluster (closing the Salesmatic marketing-sheet gap):
-- selfie-validated attendance, the outlet photo pipeline above finally
-- wired up to an endpoint, and lightweight competitor-activity capture.
-- These CREATE TABLE statements are here for documentation only - the
-- self-healing CREATE TABLE IF NOT EXISTS calls inside
-- modules/knb_api/includes/attendance_selfie_db.inc and
-- competitor_note_db.inc (called defensively from their write/read paths)
-- are what actually apply them in production, same as knb_price_import_log
-- above and 0_knb_outlet_photos itself.

CREATE TABLE IF NOT EXISTS `0_knb_attendance_selfies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `att_date` date NOT NULL,
  `punch_action` varchar(10) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `att_date` (`att_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_competitor_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `debtor_no` int(11) DEFAULT NULL,
  `competitor_name` varchar(100) NOT NULL,
  `note` text,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `debtor_no` (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Lead follow-up pipeline (modules/knb_api/includes/lead_db.inc). Also
-- created defensively at runtime (ensure_lead_tables(), same reason as
-- every other table in this file - see this file's header comment); listed
-- here for reference only, this .sql is never auto-applied.
CREATE TABLE IF NOT EXISTS `0_knb_lead_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debtor_no` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New' COMMENT 'New, Contacted, Converted, Lost',
  `follow_up_date` date DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `debtor_no` (`debtor_no`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_lead_status` (
  `debtor_no` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New',
  `last_follow_up_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`debtor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
