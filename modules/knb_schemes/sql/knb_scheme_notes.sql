-- Schemes Note - confirmed against TechCloud's live Sales > Schemes Note
-- screen (Transactions module): a simple Brand + Item + Role association,
-- distinct from the scheme slab/tier setup in knb_schemes (which scopes a
-- scheme to a brand + person_type but not to specific items within that
-- brand). Zero rows exist in TechCloud's own live data at analysis time.

CREATE TABLE IF NOT EXISTS `0_knb_scheme_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `stock_id` varchar(20) NOT NULL,
  `person_type` varchar(30) DEFAULT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `stock_id` (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
