-- Trade scheme engine - confirmed against TechCloud's live Sales menu AND
-- its actual Schemes Entry form (not just the menu label): a scheme is
-- scoped by Brand + Person Type + State + Territory over a date range, with
-- up to 8 volume slabs, each a target quantity (in cases) and a free-text
-- reward description - not a single buy/free/discount ratio. "Schemes Note"
-- turned out to be TechCloud's own item-to-brand and role master data
-- screen (already covered here by knb_inventory_ext's item_classification
-- and knb_distribution's Person Type), so it isn't rebuilt separately.
-- "Schemes Inquiry Note" is the real eligibility report - per customer,
-- total quantity ordered for the brand against the scheme's slabs - built
-- as an inquiry against real invoice data, not a stored settlement record.
CREATE TABLE IF NOT EXISTS `0_knb_schemes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `person_type` varchar(30) DEFAULT NULL,
  `state` varchar(60) DEFAULT NULL,
  `territory_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `0_knb_scheme_slabs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scheme_id` int(11) NOT NULL,
  `slab_no` tinyint(4) NOT NULL,
  `slab_label` varchar(60) DEFAULT NULL,
  `slab_target_qty` double NOT NULL DEFAULT 0,
  `scheme_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheme_id` (`scheme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
