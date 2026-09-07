<?php

function add_shift_type($code, $name)
{
	$sql = "INSERT INTO ".TB_PREF."knb_shift_types (code, name) VALUES ("
		.db_escape($code).",".db_escape($name).")";
	db_query($sql, "could not add shift type");
}

function update_shift_type($id, $code, $name)
{
	$sql = "UPDATE ".TB_PREF."knb_shift_types SET code=".db_escape($code).", name=".db_escape($name)."
		WHERE id=".db_escape($id);
	db_query($sql, "could not update shift type");
}

function get_all_shift_types($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_shift_types";
	if (!$all)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get shift types");
}

function get_shift_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_shift_types WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get shift type");
	return db_fetch($result);
}

function delete_shift_type($id)
{
	$sql = "DELETE FROM ".TB_PREF."knb_shift_types WHERE id=".db_escape($id);
	db_query($sql, "could not delete shift type");
}
