<?php

function add_asset_allocation($employee_id, $asset_name, $allocation_date, $remarks)
{
	$sql = "INSERT INTO ".TB_PREF."knb_asset_allocations (employee_id, asset_name, allocation_date, remarks) VALUES ("
		.db_escape($employee_id).",".db_escape($asset_name).",".db_escape(date2sql($allocation_date)).","
		.db_escape($remarks ?: null, true).")";
	db_query($sql, "could not add asset allocation");
}

function update_asset_allocation($id, $employee_id, $asset_name, $allocation_date, $remarks)
{
	$sql = "UPDATE ".TB_PREF."knb_asset_allocations SET employee_id=".db_escape($employee_id).","
		." asset_name=".db_escape($asset_name).", allocation_date=".db_escape(date2sql($allocation_date)).","
		." remarks=".db_escape($remarks ?: null, true)."
		WHERE id=".db_escape($id);
	db_query($sql, "could not update asset allocation");
}

function get_all_asset_allocations($all=false, $employee_id=null)
{
	$sql = "SELECT a.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_asset_allocations a
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = a.employee_id
		WHERE 1=1";
	if (!$all)
		$sql .= " AND !a.inactive";
	if ($employee_id)
		$sql .= " AND a.employee_id=".db_escape($employee_id);
	$sql .= " ORDER BY a.allocation_date DESC";
	return db_query($sql, "could not get asset allocations");
}

function get_asset_allocation($id)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_asset_allocations WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get asset allocation");
	return db_fetch($result);
}

function delete_asset_allocation($id)
{
	$sql = "DELETE FROM ".TB_PREF."knb_asset_allocations WHERE id=".db_escape($id);
	db_query($sql, "could not delete asset allocation");
}
