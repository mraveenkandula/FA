<?php

function add_leave_type($name, $code, $days_confirmed, $days_probation)
{
	db_query("INSERT INTO ".TB_PREF."knb_leave_types (name, code, days_confirmed, days_probation)
		VALUES (".db_escape($name).",".db_escape($code).",".db_escape($days_confirmed).",".db_escape($days_probation).")",
		"could not add leave type");
}

function update_leave_type($id, $name, $code, $days_confirmed, $days_probation)
{
	db_query("UPDATE ".TB_PREF."knb_leave_types SET name=".db_escape($name).", code=".db_escape($code).","
		." days_confirmed=".db_escape($days_confirmed).", days_probation=".db_escape($days_probation)."
		WHERE id=".db_escape($id),
		"could not update leave type");
}

function delete_leave_type($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_leave_types WHERE id=".db_escape($id), "could not delete leave type");
}

function get_leave_type($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_leave_types WHERE id=".db_escape($id), "could not get leave type");
	return db_fetch($result);
}

function get_all_leave_types($show_inactive=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_leave_types";
	if (!$show_inactive)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get leave types");
}
