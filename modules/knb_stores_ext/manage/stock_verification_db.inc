<?php

function start_verification($verify_date, $loc_code, $employee_id, $remarks)
{
	db_query("INSERT INTO ".TB_PREF."knb_stock_verification (verify_date, loc_code, employee_id, remarks)
		VALUES (".db_escape(date2sql($verify_date)).",".db_escape($loc_code).","
		.db_escape($employee_id ?: null, true).",".db_escape($remarks ?: null, true).")",
		"could not start stock verification");
	return db_insert_id();
}

function get_verification($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_stock_verification WHERE id=".db_escape($id),
		"could not get stock verification");
	return db_fetch($result);
}

function get_all_verifications($loc_code=null)
{
	$sql = "SELECT v.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_stock_verification v
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = v.employee_id
		WHERE 1=1";
	if ($loc_code)
		$sql .= " AND v.loc_code=".db_escape($loc_code);
	$sql .= " ORDER BY v.verify_date DESC, v.id DESC";
	return db_query($sql, "could not get stock verifications");
}

function add_verification_item($verification_id, $stock_id, $counted_qty)
{
	$verification = get_verification($verification_id);
	$system_qty = get_qoh_on_date($stock_id, $verification['loc_code'], sql2date($verification['verify_date']));
	$variance = $counted_qty - $system_qty;

	db_query("INSERT INTO ".TB_PREF."knb_stock_verification_items
		(verification_id, stock_id, system_qty, counted_qty, variance_qty)
		VALUES (".db_escape($verification_id).",".db_escape($stock_id).","
		.db_escape($system_qty).",".db_escape($counted_qty).",".db_escape($variance).")",
		"could not add verification item");
}

function get_verification_items($verification_id)
{
	$sql = "SELECT vi.*, s.description
		FROM ".TB_PREF."knb_stock_verification_items vi
		LEFT JOIN ".TB_PREF."stock_master s ON s.stock_id = vi.stock_id
		WHERE vi.verification_id=".db_escape($verification_id)."
		ORDER BY vi.id";
	return db_query($sql, "could not get verification items");
}

/*
	Post a real Inventory Adjustment for every item whose counted quantity
	differs from the system quantity, via core's own add_stock_adjustment()
	- so a stock count actually corrects on-hand quantities and GL, instead
	of just being logged.
*/
function finalize_verification($verification_id)
{
	$verification = get_verification($verification_id);
	if ($verification['finalized'])
		return $verification['adjustment_trans_no'];

	$items = array();
	$result = get_verification_items($verification_id);
	while ($row = db_fetch($result))
	{
		if (abs($row['variance_qty']) < 0.00001)
			continue;
		$stockitem = get_item($row['stock_id']);
		$items[] = (object)array(
			'stock_id' => $row['stock_id'],
			'quantity' => $row['variance_qty'],
			'standard_cost' => $stockitem['material_cost'],
		);
	}

	$adj_id = null;
	if (count($items) > 0)
	{
		global $Refs;
		$ref = $Refs->get_next(ST_INVADJUST, null, sql2date($verification['verify_date']));
		$adj_id = add_stock_adjustment($items, $verification['loc_code'],
			sql2date($verification['verify_date']), $ref, $verification['remarks']);
	}

	db_query("UPDATE ".TB_PREF."knb_stock_verification SET finalized=1, adjustment_trans_no="
		.db_escape($adj_id ?: null, true)." WHERE id=".db_escape($verification_id),
		"could not finalize stock verification");

	return $adj_id;
}
