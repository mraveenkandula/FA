-- Promotion types beyond quantity-slab, confirmed as a real gap against
-- the actual TechCloud commercial agreement's Promotion Management scope
-- (Price Off, Percentage Off, Bill value promotions, Buy-Get promotions) -
-- knb_schemes.sql only ever built the Quantity Slab type. Reuses the
-- existing slab-row table as a generic tiered-rule row for every type
-- rather than adding separate tables per type, since a Bill Value
-- promotion is genuinely tiered the same way a quantity slab is (just
-- keyed on invoice total instead of quantity).
ALTER TABLE `0_knb_schemes` ADD COLUMN IF NOT EXISTS `promotion_type` varchar(20) NOT NULL DEFAULT 'QTY_SLAB' COMMENT 'QTY_SLAB, PRICE_OFF, PERCENT_OFF, BILL_VALUE, BUY_GET';
ALTER TABLE `0_knb_scheme_slabs` ADD COLUMN IF NOT EXISTS `discount_percent` double DEFAULT NULL;
ALTER TABLE `0_knb_scheme_slabs` ADD COLUMN IF NOT EXISTS `discount_amount` double DEFAULT NULL;
ALTER TABLE `0_knb_scheme_slabs` ADD COLUMN IF NOT EXISTS `bill_value_threshold` double DEFAULT NULL;
ALTER TABLE `0_knb_scheme_slabs` ADD COLUMN IF NOT EXISTS `buy_qty` double DEFAULT NULL;
ALTER TABLE `0_knb_scheme_slabs` ADD COLUMN IF NOT EXISTS `get_qty` double DEFAULT NULL;
