-- Customer date of birth + phone, needed for TechCloud's confirmed Customer
-- Birthday List/Calendar screens - core FrontAccounting's debtors_master has
-- no such fields, so they're added to the existing customer_distribution
-- mapping table (the established per-customer attachment point) rather than
-- a new table.
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `date_of_birth` date DEFAULT NULL;
ALTER TABLE `0_customer_distribution` ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL;
