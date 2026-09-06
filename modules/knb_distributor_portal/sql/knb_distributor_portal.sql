-- Distributor Login portal - confirmed real gap against the TechCloud
-- commercial agreement's mobile app scope ("Distributor Login", "Stock
-- Check (QOH, Demand, Ordered)"). debtors_master has no login credentials
-- of its own (unlike hr_employees.mobile_username/mobile_password_hash,
-- the pattern this reuses), so this adds a parallel pair of columns
-- scoped to customer-portal access rather than reusing the employee ones.
ALTER TABLE `0_debtors_master` ADD COLUMN IF NOT EXISTS `portal_username` varchar(60) DEFAULT NULL;
ALTER TABLE `0_debtors_master` ADD COLUMN IF NOT EXISTS `portal_password_hash` varchar(255) DEFAULT NULL;
ALTER TABLE `0_debtors_master` ADD COLUMN IF NOT EXISTS `portal_active` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `0_debtors_master` ADD UNIQUE INDEX IF NOT EXISTS `portal_username_unique` (`portal_username`);

-- A distributor's own self-reported "Demand" (what they say they still
-- need) - the one part of Stock Check that has no existing source in FA;
-- QOH comes from real stock_moves via get_qoh_on_date() against the
-- distributor's own location (0_locations.debtor_no), and Ordered comes
-- from their real open sales order lines - neither needs a new table.
CREATE TABLE IF NOT EXISTS `0_knb_distributor_demand` (
	`debtor_no` int(11) NOT NULL,
	`stock_id` varchar(20) NOT NULL,
	`demand_qty` double NOT NULL DEFAULT 0,
	`updated_at` datetime NOT NULL,
	PRIMARY KEY (`debtor_no`, `stock_id`)
) ENGINE=InnoDB;
