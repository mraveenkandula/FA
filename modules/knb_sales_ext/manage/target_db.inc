<?php

function add_sales_target($sales_employee_id, $period_month, $target_amount, $notes)
{
	db_query("INSERT INTO ".TB_PREF."knb_sales_targets
		(sales_employee_id, period_month, target_amount, notes)
		VALUES (".db_escape($sales_employee_id).",".db_escape($period_month).","
		.db_escape($target_amount).",".db_escape($notes ?: null, true).")
		ON DUPLICATE KEY UPDATE target_amount=".db_escape($target_amount).", notes=".db_escape($notes ?: null, true),
		"could not save sales target");
}

function get_sales_targets($sales_employee_id=null, $date_from=null, $date_to=null)
{
	$sql = "SELECT t.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_sales_targets t
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = t.sales_employee_id
		WHERE 1=1";
	if ($sales_employee_id)
		$sql .= " AND t.sales_employee_id=".db_escape($sales_employee_id);
	if ($date_from)
		$sql .= " AND t.period_month >= ".db_escape($date_from);
	if ($date_to)
		$sql .= " AND t.period_month <= ".db_escape($date_to);
	$sql .= " ORDER BY t.period_month DESC, e.first_name";
	return db_query($sql, "could not get sales targets");
}
