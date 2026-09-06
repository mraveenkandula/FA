<?php
/*
	Read-only queries over the smaller real HR records merged in from the
	TechCloud export (holidays calendar, employee grievances, local
	conveyance/travel claims, notice periods) - none of these had an
	existing knb_hrm feature to extend, so they're new, simple tables with
	no approval workflow of their own yet.
*/

function get_holidays()
{
	return db_query("SELECT * FROM ".TB_PREF."knb_holidays ORDER BY holiday_date");
}

function get_grievances()
{
	return db_query("SELECT * FROM ".TB_PREF."knb_grievances ORDER BY submitted_at DESC");
}

function get_local_conveyance($employee_id = null)
{
	$sql = "SELECT c.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_local_conveyance c
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = c.employee_id";
	if ($employee_id)
		$sql .= " WHERE c.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY c.travel_date DESC";
	return db_query($sql);
}

function get_notice_periods()
{
	return db_query(
		"SELECT n.*, e.first_name, e.last_name
		 FROM ".TB_PREF."knb_notice_periods n
		 LEFT JOIN ".TB_PREF."hr_employees e ON e.id = n.employee_id
		 ORDER BY n.start_date DESC");
}
