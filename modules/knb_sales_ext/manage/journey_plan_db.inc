<?php

function add_journey_plan($sales_employee_id, $plan_date, $beat_id, $remarks)
{
	db_query("INSERT INTO ".TB_PREF."knb_journey_plan (sales_employee_id, plan_date, beat_id, remarks)
		VALUES (".db_escape($sales_employee_id).",".db_escape($plan_date).","
		.db_escape($beat_id ?: null, true).",".db_escape($remarks ?: null, true).")",
		"could not add journey plan");
}

function get_journey_plans($sales_employee_id=null, $date_from=null, $date_to=null)
{
	$sql = "SELECT jp.*, e.first_name, e.last_name, b.name AS beat_name
		FROM ".TB_PREF."knb_journey_plan jp
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = jp.sales_employee_id
		LEFT JOIN ".TB_PREF."sales_beats b ON b.id = jp.beat_id
		WHERE 1=1";
	if ($sales_employee_id)
		$sql .= " AND jp.sales_employee_id=".db_escape($sales_employee_id);
	if ($date_from)
		$sql .= " AND jp.plan_date >= ".db_escape($date_from);
	if ($date_to)
		$sql .= " AND jp.plan_date <= ".db_escape($date_to);
	$sql .= " ORDER BY jp.plan_date DESC, jp.id DESC";
	return db_query($sql, "could not get journey plans");
}
