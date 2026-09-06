<?php

function add_sales_lead($lead_name, $phone, $address, $territory_id, $town_id, $beat_id, $assigned_employee_id, $remarks, $created_date)
{
	db_query("INSERT INTO ".TB_PREF."knb_sales_leads
		(lead_name, phone, address, territory_id, town_id, beat_id, status, assigned_employee_id, remarks, created_date)
		VALUES (".db_escape($lead_name).",".db_escape($phone ?: null, true).",".db_escape($address ?: null, true).","
		.db_escape($territory_id ?: null, true).",".db_escape($town_id ?: null, true).",".db_escape($beat_id ?: null, true).",
		'New',".db_escape($assigned_employee_id ?: null, true).",".db_escape($remarks ?: null, true).","
		.db_escape($created_date).")",
		"could not add sales lead");
}

function set_lead_status($id, $status)
{
	db_query("UPDATE ".TB_PREF."knb_sales_leads SET status=".db_escape($status)." WHERE id=".db_escape($id),
		"could not update lead status");
}

function get_sales_leads($status=null)
{
	$sql = "SELECT l.*, t.name AS territory_name, tn.name AS town_name, b.name AS beat_name,
		e.first_name, e.last_name
		FROM ".TB_PREF."knb_sales_leads l
		LEFT JOIN ".TB_PREF."sales_territories t ON t.id = l.territory_id
		LEFT JOIN ".TB_PREF."sales_towns tn ON tn.id = l.town_id
		LEFT JOIN ".TB_PREF."sales_beats b ON b.id = l.beat_id
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = l.assigned_employee_id
		WHERE 1=1";
	if ($status)
		$sql .= " AND l.status=".db_escape($status);
	$sql .= " ORDER BY l.created_date DESC, l.id DESC";
	return db_query($sql, "could not get sales leads");
}
