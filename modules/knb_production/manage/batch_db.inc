<?php

function add_production_batch($batch_date, $stage, $input_item, $input_qty, $output_item, $output_qty,
	$fat_content, $moisture_percent, $incharge_employee_id, $remarks)
{
	$yield_percent = $input_qty > 0 ? round(($output_qty / $input_qty) * 100, 2) : null;
	db_query("INSERT INTO ".TB_PREF."production_batches
		(batch_date, stage, input_item, input_qty, output_item, output_qty, yield_percent,
		 fat_content, moisture_percent, incharge_employee_id, remarks)
		VALUES (".db_escape($batch_date).",".db_escape($stage).",".db_escape($input_item ?: null, true).","
		.db_escape($input_qty).",".db_escape($output_item ?: null, true).",".db_escape($output_qty).","
		.db_escape($yield_percent, true).",".db_escape($fat_content !== '' ? $fat_content : null, true).","
		.db_escape($moisture_percent !== '' ? $moisture_percent : null, true).","
		.db_escape($incharge_employee_id ?: null, true).",".db_escape($remarks ?: null, true).")",
		"could not add production batch");
}

function get_production_batches($stage=null, $date_from=null, $date_to=null)
{
	$sql = "SELECT b.*, e.first_name, e.last_name,
		si.description AS input_desc, so.description AS output_desc
		FROM ".TB_PREF."production_batches b
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = b.incharge_employee_id
		LEFT JOIN ".TB_PREF."stock_master si ON si.stock_id = b.input_item
		LEFT JOIN ".TB_PREF."stock_master so ON so.stock_id = b.output_item
		WHERE 1=1";
	if ($stage)
		$sql .= " AND b.stage=".db_escape($stage);
	if ($date_from)
		$sql .= " AND b.batch_date >= ".db_escape($date_from);
	if ($date_to)
		$sql .= " AND b.batch_date <= ".db_escape($date_to);
	$sql .= " ORDER BY b.batch_date DESC, b.id DESC";
	return db_query($sql, "could not get production batches");
}

function get_average_yield($stage, $date_from, $date_to)
{
	$sql = "SELECT AVG(yield_percent) AS avg_yield, COUNT(*) AS batch_count
		FROM ".TB_PREF."production_batches
		WHERE stage=".db_escape($stage)."
		AND batch_date >= ".db_escape($date_from)." AND batch_date <= ".db_escape($date_to);
	$result = db_query($sql, "could not get average yield");
	return db_fetch($result);
}
