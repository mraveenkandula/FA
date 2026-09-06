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
