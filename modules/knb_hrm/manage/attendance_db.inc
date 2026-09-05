<?php

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

function get_attendance_report($date_from, $date_to, $employee_id=null)
{
	$sql = "SELECT a.*, e.emp_code, e.first_name, e.last_name
		FROM ".TB_PREF."hr_attendance a
		JOIN ".TB_PREF."hr_employees e ON e.id = a.employee_id
		WHERE a.att_date >= ".db_escape($date_from)." AND a.att_date <= ".db_escape($date_to);
	if ($employee_id)
		$sql .= " AND a.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY a.att_date DESC, e.first_name";
	return db_query($sql, "could not get attendance report");
}
