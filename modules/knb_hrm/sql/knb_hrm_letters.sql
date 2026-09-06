-- HR Letter Formats - confirmed real gap against the TechCloud commercial
-- agreement's HR Management scope. No template/merge/document generation
-- of any kind exists anywhere in this codebase (confirmed by a dedicated
-- search before building this). Templates hold {{placeholder}} text;
-- merging is plain string substitution against a fixed, documented set of
-- employee fields (see modules/knb_hrm/manage/letter_db.inc) - no PDF
-- library needed, generated output is printable HTML.
CREATE TABLE IF NOT EXISTS `0_knb_letter_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `body` text NOT NULL,
  `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
