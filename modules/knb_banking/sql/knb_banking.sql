CREATE TABLE IF NOT EXISTS `0_bank_statement_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` smallint(6) NOT NULL,
  `txn_date` date NOT NULL,
  `amount` double NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `balance_after` double DEFAULT NULL,
  `import_batch` varchar(40) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'unmatched',
  `matched_bank_trans_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
