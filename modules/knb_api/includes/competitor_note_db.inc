<?php
/*
	DB helpers for competitor-activity notes - a lightweight "I saw
	competitor X doing Y" capture a field rep logs from the app. Explicitly
	NOT "market intelligence" (no analysis/aggregation here - see the doc
	comment on competitor_notes_get.php for what this deliberately doesn't
	do). debtor_no is nullable: a rep might log a competitor sighting that
	isn't tied to any specific outlet visit.

	Same self-healing CREATE TABLE IF NOT EXISTS pattern as
	attendance_selfie_db.inc/price_import_db.inc, for the same reason (FA's
	migration installer never runs for these hand-placed modules).
*/

function ensure_competitor_notes_table()
{
	db_query("CREATE TABLE IF NOT EXISTS ".TB_PREF."knb_competitor_notes (
		id int(11) NOT NULL AUTO_INCREMENT,
		employee_id int(11) NOT NULL,
		debtor_no int(11) DEFAULT NULL,
		competitor_name varchar(100) NOT NULL,
		note text,
		file_path varchar(255) DEFAULT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY employee_id (employee_id),
		KEY debtor_no (debtor_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci",
		"could not create knb_competitor_notes table");
}

function add_competitor_note($employee_id, $debtor_no, $competitor_name, $note, $file_path)
{
	ensure_competitor_notes_table();

	db_query("INSERT INTO ".TB_PREF."knb_competitor_notes
		(employee_id, debtor_no, competitor_name, note, file_path, created_at)
		VALUES (".db_escape($employee_id).",".db_escape($debtor_no ?: null, true).","
		.db_escape($competitor_name).",".db_escape($note ?: null, true).","
		.db_escape($file_path ?: null, true).", NOW())",
		"could not save competitor note");
	return db_insert_id();
}

/*
	Lists notes, newest first - optionally filtered to one employee (the
	authenticated caller's own notes) and/or one outlet. Joins
	debtors_master/hr_employees for display names the same way
	get_outlet_photos()/get_all_verifications() do for their own rows.
*/
function get_competitor_notes($employee_id = null, $debtor_no = null)
{
	ensure_competitor_notes_table();

	$sql = "SELECT n.id, n.employee_id, n.debtor_no, n.competitor_name, n.note,
			n.file_path, n.created_at, e.first_name, e.last_name, d.name AS outlet_name
		FROM ".TB_PREF."knb_competitor_notes n
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = n.employee_id
		LEFT JOIN ".TB_PREF."debtors_master d ON d.debtor_no = n.debtor_no
		WHERE 1=1";
	if ($employee_id !== null)
		$sql .= " AND n.employee_id=".db_escape($employee_id);
	if ($debtor_no !== null)
		$sql .= " AND n.debtor_no=".db_escape($debtor_no);
	$sql .= " ORDER BY n.created_at DESC";
	return db_query($sql, "could not get competitor notes");
}
