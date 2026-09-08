<?php

// ensure_attendance_selfies_table()/get_attendance_selfies() live here,
// normally only pulled in by the mobile API's own bootstrap
// (modules/knb_api/includes/api_bootstrap.inc) - the web UI never loads
// that, so get_attendance_report() below needs this included explicitly
// or its call to ensure_attendance_selfies_table() is a fatal
// undefined-function error, not a graceful failure.
require_once($path_to_root . '/modules/knb_api/includes/attendance_selfie_db.inc');

function get_active_employees()
{
	$sql = "SELECT id, emp_code, first_name, last_name FROM ".TB_PREF."hr_employees
		WHERE !inactive ORDER BY first_name";
	return db_query($sql, "could not get employees");
}

function get_attendance_for_date($date)
{
	$sql = "SELECT employee_id, status, check_in, check_out, remarks
		FROM ".TB_PREF."hr_attendance WHERE att_date=".db_escape($date);
	$result = db_query($sql, "could not get attendance");
	$rows = array();
	while ($row = db_fetch($result))
		$rows[$row['employee_id']] = $row;
	return $rows;
}

function save_attendance($date, $employee_id, $status, $check_in, $check_out, $remarks)
{
	$sql = "REPLACE INTO ".TB_PREF."hr_attendance
		(employee_id, att_date, status, check_in, check_out, remarks)
		VALUES (".db_escape($employee_id).",".db_escape($date).",".db_escape($status).","
		.db_escape($check_in ?: null, true).",".db_escape($check_out ?: null, true).","
		.db_escape($remarks ?: null, true).")";
	db_query($sql, "could not save attendance");
}

/*
	Selfie columns (selfie_in_path/selfie_out_path) let admins/HR reviewing
	this report see the punch-in/punch-out photo captured by the mobile
	app's selfie-validated attendance (attendance_selfie_post.php, see
	modules/knb_api/includes/attendance_selfie_db.inc) - the same photo the
	employee themselves can already see in the app's own attendance
	history. 0_knb_attendance_selfies is created defensively on its own
	write path, not by FA's migration installer (same situation documented
	throughout this fork) - ensure_attendance_selfies_table() is called
	here too so this report's LEFT JOIN doesn't fail with a missing-table
	error on an install where no selfie has ever been uploaded yet.
*/
function get_attendance_report($date_from, $date_to, $employee_id=null)
{
	ensure_attendance_selfies_table();

	// Correlated subqueries rather than a LEFT JOIN: a retry after a failed
	// upload can leave more than one selfie row for the same employee/date/
	// punch_action (no unique constraint on that triple, by design - see
	// attendance_selfie_db.inc), and a plain JOIN would then duplicate the
	// attendance row itself. Each subquery picks just the most recent one.
	$sql = "SELECT a.*, e.emp_code, e.first_name, e.last_name,
			(SELECT file_path FROM ".TB_PREF."knb_attendance_selfies s_in
				WHERE s_in.employee_id = a.employee_id AND s_in.att_date = a.att_date AND s_in.punch_action = 'in'
				ORDER BY s_in.uploaded_at DESC LIMIT 1) AS selfie_in_path,
			(SELECT file_path FROM ".TB_PREF."knb_attendance_selfies s_out
				WHERE s_out.employee_id = a.employee_id AND s_out.att_date = a.att_date AND s_out.punch_action = 'out'
				ORDER BY s_out.uploaded_at DESC LIMIT 1) AS selfie_out_path
		FROM ".TB_PREF."hr_attendance a
		JOIN ".TB_PREF."hr_employees e ON e.id = a.employee_id
		WHERE a.att_date >= ".db_escape($date_from)." AND a.att_date <= ".db_escape($date_to);
	if ($employee_id)
		$sql .= " AND a.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY a.att_date DESC, e.first_name";
	return db_query($sql, "could not get attendance report");
}
