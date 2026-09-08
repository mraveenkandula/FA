<?php
/*
	DB helpers for selfie-validated attendance - a photo captured alongside
	a punch (attendance_punch.php), stored in its own table rather than a
	new column on 0_attendance since a punch and its selfie are saved by two
	separate best-effort calls from the app (punch first, then the photo -
	see attendance_screen.dart's doc comment on why the photo must never
	block the punch itself).

	0_knb_attendance_selfies is not created by FA's migration installer
	(never invoked for these hand-placed knb_* modules - same situation
	documented in price_import_db.inc's apply_price_import()) - so this
	self-heals the table defensively, the same way and in the same place
	(inside the write path) as that function does for
	0_knb_price_import_log.
*/

function ensure_attendance_selfies_table()
{
	db_query("CREATE TABLE IF NOT EXISTS ".TB_PREF."knb_attendance_selfies (
		id int(11) NOT NULL AUTO_INCREMENT,
		employee_id int(11) NOT NULL,
		att_date date NOT NULL,
		punch_action varchar(10) NOT NULL,
		file_path varchar(255) NOT NULL,
		uploaded_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY employee_id (employee_id),
		KEY att_date (att_date)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci",
		"could not create knb_attendance_selfies table");
}

function save_attendance_selfie($employee_id, $att_date, $punch_action, $file_path)
{
	ensure_attendance_selfies_table();

	db_query("INSERT INTO ".TB_PREF."knb_attendance_selfies
		(employee_id, att_date, punch_action, file_path, uploaded_at)
		VALUES (".db_escape($employee_id).",".db_escape($att_date).",".db_escape($punch_action).","
		.db_escape($file_path).", NOW())",
		"could not save attendance selfie");
	return db_insert_id();
}

/*
	Selfie history for one employee - not currently exposed by any GET
	endpoint (not asked for), but kept here alongside the writer since it's
	the natural read counterpart and other knb_*_db.inc files in this
	module follow the same "read helper lives next to the write helper"
	shape (e.g. outlet_db.inc's add_outlet_photo()/get_outlet_photos()).
*/
function get_attendance_selfies($employee_id, $att_date = null)
{
	ensure_attendance_selfies_table();

	$sql = "SELECT id, employee_id, att_date, punch_action, file_path, uploaded_at
		FROM ".TB_PREF."knb_attendance_selfies
		WHERE employee_id=".db_escape($employee_id);
	if ($att_date !== null)
		$sql .= " AND att_date=".db_escape($att_date);
	$sql .= " ORDER BY uploaded_at DESC";
	return db_query($sql, "could not get attendance selfies");
}
