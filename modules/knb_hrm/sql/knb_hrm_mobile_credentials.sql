-- Supports modules/knb_hrm/manage/mobile_credentials.php (provisioning
-- for the mobile app login that modules/knb_api/endpoints/login.php
-- authenticates against).
--
-- The mobile_username/mobile_password_hash columns themselves already
-- exist (added by modules/knb_api/sql/knb_api.sql, applied earlier this
-- project) - the ADD COLUMN IF NOT EXISTS lines below are only a defensive
-- no-op for an environment that installs knb_hrm without knb_api's schema
-- having been applied first; this file does not modify anything under
-- modules/knb_api/.
--
-- Confirmed before adding the UNIQUE index: no duplicate non-null values
-- and no blank-string values existed in mobile_username on the local dev
-- DB (checked directly - only one row had a value at all, the synthetic
-- test employee). NULL is fine under a UNIQUE index in MySQL/MariaDB
-- (multiple NULLs are allowed), so employees with no mobile credential
-- yet are unaffected. A production apply of this file should re-run the
-- same duplicate/blank check first if the data may have diverged.

ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `mobile_username` varchar(30) DEFAULT NULL;
ALTER TABLE `0_hr_employees` ADD COLUMN IF NOT EXISTS `mobile_password_hash` varchar(255) DEFAULT NULL;

ALTER TABLE `0_hr_employees` ADD UNIQUE INDEX IF NOT EXISTS `mobile_username_unique` (`mobile_username`);
