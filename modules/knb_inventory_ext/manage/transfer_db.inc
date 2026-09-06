<?php

function add_transfer_request($from_location, $to_location, $stock_id, $qty, $requested_by, $requested_date, $remarks)
{
	db_query("INSERT INTO ".TB_PREF."stock_transfer_requests
		(from_location, to_location, stock_id, qty, requested_by, requested_date, remarks, status)
		VALUES (".db_escape($from_location).",".db_escape($to_location).",".db_escape($stock_id).","
		.db_escape($qty).",".db_escape($requested_by ?: null, true).",".db_escape($requested_date).","
		.db_escape($remarks ?: null, true).",'Pending')",
		"could not add stock transfer request");
}

function get_transfer_requests($status=null)
{
	$sql = "SELECT t.*, e.first_name, e.last_name,
		lf.location_name AS from_name, lt.location_name AS to_name, s.description AS item_desc
		FROM ".TB_PREF."stock_transfer_requests t
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = t.requested_by
		LEFT JOIN ".TB_PREF."locations lf ON lf.loc_code = t.from_location
		LEFT JOIN ".TB_PREF."locations lt ON lt.loc_code = t.to_location
		LEFT JOIN ".TB_PREF."stock_master s ON s.stock_id = t.stock_id";
	if ($status)
		$sql .= " WHERE t.status=".db_escape($status);
	$sql .= " ORDER BY t.requested_date DESC, t.id DESC";
	return db_query($sql, "could not get stock transfer requests");
}

function set_transfer_status($id, $status, $approved_date)
{
	db_query("UPDATE ".TB_PREF."stock_transfer_requests SET status=".db_escape($status)
		.", approved_date=".db_escape($approved_date ?: null, true)." WHERE id=".db_escape($id),
		"could not update transfer request status");
}
