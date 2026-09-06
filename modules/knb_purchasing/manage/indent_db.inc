<?php

function add_indent($item_description, $qty, $uom, $requested_by, $requested_date, $required_by_date, $remarks)
{
	db_query("INSERT INTO ".TB_PREF."purchase_indents
		(item_description, qty, uom, requested_by, requested_date, required_by_date, remarks, status)
		VALUES (".db_escape($item_description).",".db_escape($qty).",".db_escape($uom ?: null, true).","
		.db_escape($requested_by ?: null, true).",".db_escape($requested_date).","
		.db_escape($required_by_date ?: null, true).",".db_escape($remarks ?: null, true).",'Pending')",
		"could not add purchase indent");
}

function get_indents($status=null)
{
	$sql = "SELECT i.*, e.first_name, e.last_name
		FROM ".TB_PREF."purchase_indents i
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = i.requested_by";
	if ($status)
		$sql .= " WHERE i.status=".db_escape($status);
	$sql .= " ORDER BY i.requested_date DESC, i.id DESC";
	return db_query($sql, "could not get purchase indents");
}

function get_indent($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."purchase_indents WHERE id=".db_escape($id), "could not get indent");
	return db_fetch($result);
}

function set_indent_status($id, $status, $approved_by, $approved_date)
{
	db_query("UPDATE ".TB_PREF."purchase_indents SET status=".db_escape($status)
		.", approved_by=".db_escape($approved_by ?: null, true).", approved_date=".db_escape($approved_date ?: null, true)
		." WHERE id=".db_escape($id), "could not update indent status");
}
