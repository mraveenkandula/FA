<?php

function add_notice_period($employee_id, $notice_days, $start_date, $end_date)
{
	db_query("INSERT INTO ".TB_PREF."knb_notice_periods (employee_id, notice_days, start_date, end_date)
		VALUES (".db_escape($employee_id ?: null, true).",".db_escape($notice_days).","
		.db_escape(date2sql($start_date)).",".db_escape(date2sql($end_date)).")",
		"could not add notice period");
}

function update_notice_period($id, $employee_id, $notice_days, $start_date, $end_date)
{
	db_query("UPDATE ".TB_PREF."knb_notice_periods SET
		employee_id=".db_escape($employee_id ?: null, true).",
		notice_days=".db_escape($notice_days).",
		start_date=".db_escape(date2sql($start_date)).",
		end_date=".db_escape(date2sql($end_date))."
		WHERE id=".db_escape($id),
		"could not update notice period");
}

function delete_notice_period($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_notice_periods WHERE id=".db_escape($id),
		"could not delete notice period");
}

function get_notice_period($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_notice_periods WHERE id=".db_escape($id));
	return db_fetch($result);
}

function get_all_notice_periods($employee_id=null)
{
	$sql = "SELECT n.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_notice_periods n
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = n.employee_id";
	if ($employee_id)
		$sql .= " WHERE n.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY n.start_date DESC";
	return db_query($sql);
}
