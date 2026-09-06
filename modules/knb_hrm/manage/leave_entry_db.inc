<?php

function get_leave_balance($employee_id, $leave_type_id, $year)
{
	$employee = get_employee($employee_id);
	$leave_type = get_leave_type($leave_type_id);

	$allotted = ($employee['employment_status'] == 'Probation')
		? $leave_type['days_probation'] : $leave_type['days_confirmed'];

	$sql = "SELECT SUM(leave_days) AS used FROM ".TB_PREF."knb_leave_entries
		WHERE employee_id=".db_escape($employee_id)."
		AND leave_type_id=".db_escape($leave_type_id)."
		AND status='Approved'
		AND YEAR(leave_from)=".db_escape($year);
	$result = db_query($sql, "could not get leave utilised");
	$row = db_fetch($result);
	$used = $row['used'] ? (float)$row['used'] : 0;

	return array('allotted' => $allotted, 'used' => $used, 'remaining' => $allotted - $used);
}

function add_leave_entry($employee_id, $leave_type_id, $leave_from, $leave_to, $leave_days, $session_type, $reason)
{
	db_query("INSERT INTO ".TB_PREF."knb_leave_entries
		(employee_id, leave_type_id, leave_from, leave_to, leave_days, session_type, reason, status, applied_date)
		VALUES (".db_escape($employee_id).",".db_escape($leave_type_id).","
		.db_escape(date2sql($leave_from)).",".db_escape(date2sql($leave_to)).",".db_escape($leave_days).","
		.db_escape($session_type ?: null, true).",".db_escape($reason ?: null, true).",'Pending',"
		.db_escape(date2sql(Today())).")",
		"could not add leave entry");
}

function set_leave_status($id, $status, $rejection_reason=null)
{
	db_query("UPDATE ".TB_PREF."knb_leave_entries SET status=".db_escape($status).","
		." rejection_reason=".db_escape($rejection_reason ?: null, true)."
		WHERE id=".db_escape($id),
		"could not update leave status");
}

function get_leave_entries($status=null, $employee_id=null)
{
	$sql = "SELECT l.*, e.first_name, e.last_name, t.name AS leave_type_name, t.code AS leave_type_code
		FROM ".TB_PREF."knb_leave_entries l
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = l.employee_id
		LEFT JOIN ".TB_PREF."knb_leave_types t ON t.id = l.leave_type_id
		WHERE 1=1";
	if ($status)
		$sql .= " AND l.status=".db_escape($status);
	if ($employee_id)
		$sql .= " AND l.employee_id=".db_escape($employee_id);
	$sql .= " ORDER BY l.leave_from DESC, l.id DESC";
	return db_query($sql, "could not get leave entries");
}
