<?php

function save_geo_ping($employee_id, $latitude, $longitude, $accuracy_m, $remarks)
{
	$sql = "INSERT INTO ".TB_PREF."hr_geo_tracking
		(employee_id, recorded_at, latitude, longitude, accuracy_m, remarks)
		VALUES (".db_escape($employee_id).", NOW(), ".db_escape($latitude).","
		.db_escape($longitude).",".db_escape($accuracy_m ?: null, true).",".db_escape($remarks ?: null, true).")";
	db_query($sql, "could not save location");
}

function get_geo_report($date_from, $date_to, $employee_id=null)
{
	$sql = "SELECT g.*, e.emp_code, e.first_name, e.last_name
		FROM ".TB_PREF."hr_geo_tracking g
		JOIN ".TB_PREF."hr_employees e ON e.id = g.employee_id
		WHERE DATE(g.recorded_at) >= ".db_escape($date_from)." AND DATE(g.recorded_at) <= ".db_escape($date_to);
	if ($employee_id)
		$sql .= " AND g.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY g.recorded_at DESC";
	return db_query($sql, "could not get geo tracking report");
}
