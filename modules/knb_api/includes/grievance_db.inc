<?php
/*
	modules/knb_hrm/inquiry/hr_records_db.inc's get_grievances() is
	read-only - 0_knb_grievances was populated by a data import (from the
	reference app's own grievance_get.php/grievance_post.php endpoints, per
	docs/mobile-app-reverse-engineering.md) and never had a web-side write
	path added. This adds the one write function this API needs, kept
	inside knb_api (not modules/knb_hrm) since that module wasn't touched
	for this task.

	IMPORTANT SCHEMA NOTE: 0_knb_grievances has no employee_id (or any
	other) foreign key to 0_hr_employees - just id, name, email, subject,
	message, submitted_at (verified via DESCRIBE against local dev DB; see
	docs/mobile-app-reverse-engineering.md's new API section). This insert
	uses exactly that existing column set, nothing added. grievance_get.php
	filters by matching the grievance row's `email` column against the
	authenticated employee's own email/official_email - the closest thing
	to an identifying column this table actually has.
*/

function add_grievance($name, $email, $subject, $message)
{
	db_query("INSERT INTO ".TB_PREF."knb_grievances (name, email, subject, message, submitted_at)
		VALUES (".db_escape($name).",".db_escape($email).",".db_escape($subject).","
		.db_escape($message).", NOW())",
		"could not add grievance");
	return db_insert_id();
}
