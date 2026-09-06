<?php

function add_production_batch($batch_date, $stage, $loc_code, $input_item, $input_qty, $output_item, $output_qty,
	$fat_content, $moisture_percent, $incharge_employee_id, $remarks)
{
	global $Refs;

	begin_transaction();

	$wo_ref = $Refs->get_next(ST_WORKORDER, null, $batch_date);
	$woid = add_work_order($wo_ref, $loc_code, $output_qty, $output_item,
		WO_ADVANCED, $batch_date, $batch_date, $remarks, 0, 0, 0, 0);

	release_work_order($woid, $batch_date, '');

	if ($input_item && $input_qty > 0)
	{
		$issue_ref = $Refs->get_next(ST_MANUISSUE, null, $batch_date);
		$items = array((object)array('stock_id' => $input_item, 'quantity' => $input_qty));
		// NB: FrontAccounting's $to_work_order flag is inverted from its name -
		// false is what actually consumes stock (verified empirically: passing
		// true increased the input item's on-hand qty and produced a negative
		// unit cost on the output item).
		add_work_order_issue($woid, $issue_ref, false, $items, $loc_code, 0, $batch_date, $remarks);
	}

	$produce_ref = $Refs->get_next(ST_MANURECEIVE, null, $batch_date);
	work_order_produce($woid, $produce_ref, $output_qty, $batch_date, $remarks, true);

	db_query("INSERT INTO ".TB_PREF."knb_production_quality
		(workorder_id, stage, fat_content, moisture_percent, incharge_employee_id, remarks)
		VALUES (".db_escape($woid).",".db_escape($stage).","
		.db_escape($fat_content !== '' ? $fat_content : null, true).","
		.db_escape($moisture_percent !== '' ? $moisture_percent : null, true).","
		.db_escape($incharge_employee_id ?: null, true).",".db_escape($remarks ?: null, true).")",
		"could not save production quality data");

	commit_transaction();

	return $woid;
}

function _production_batches_sql($stage, $date_from, $date_to)
{
	$sql = "SELECT w.id AS workorder_id, w.date_ AS batch_date, q.stage,
			si.description AS input_desc,
			iss.total_input_qty AS input_qty,
			so.description AS output_desc,
			w.units_issued AS output_qty,
			(CASE WHEN iss.total_input_qty > 0
				THEN ROUND(w.units_issued / iss.total_input_qty * 100, 2)
				ELSE NULL END) AS yield_percent,
			q.fat_content, q.moisture_percent, e.first_name, e.last_name
		FROM ".TB_PREF."knb_production_quality q
		JOIN ".TB_PREF."workorders w ON w.id = q.workorder_id
		LEFT JOIN ".TB_PREF."stock_master so ON so.stock_id = w.stock_id
		LEFT JOIN (
			SELECT wi.workorder_id, ABS(SUM(wii.qty_issued)) AS total_input_qty,
				SUBSTRING_INDEX(GROUP_CONCAT(wii.stock_id ORDER BY wii.id SEPARATOR ','), ',', 1) AS first_input_stock_id
			FROM ".TB_PREF."wo_issues wi
			JOIN ".TB_PREF."wo_issue_items wii ON wii.issue_id = wi.issue_no
			GROUP BY wi.workorder_id
		) iss ON iss.workorder_id = w.id
		LEFT JOIN ".TB_PREF."stock_master si ON si.stock_id = iss.first_input_stock_id
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = q.incharge_employee_id
		WHERE 1=1";
	if ($stage)
		$sql .= " AND q.stage=".db_escape($stage);
	if ($date_from)
		$sql .= " AND w.date_ >= ".db_escape($date_from);
	if ($date_to)
		$sql .= " AND w.date_ <= ".db_escape($date_to);
	return $sql;
}

function get_production_batches($stage=null, $date_from=null, $date_to=null)
{
	$sql = _production_batches_sql($stage, $date_from, $date_to)." ORDER BY w.date_ DESC, w.id DESC";
	return db_query($sql, "could not get production batches");
}

function get_average_yield($stage, $date_from, $date_to)
{
	$sql = "SELECT AVG(yield_percent) AS avg_yield, COUNT(*) AS batch_count FROM ("
		._production_batches_sql($stage, $date_from, $date_to)
		.") t WHERE yield_percent IS NOT NULL";
	$result = db_query($sql, "could not get average yield");
	return db_fetch($result);
}
