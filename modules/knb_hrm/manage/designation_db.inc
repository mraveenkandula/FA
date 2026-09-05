<?php

function add_designation($name)
{
	$sql = "INSERT INTO ".TB_PREF."hr_designations (name) VALUES (".db_escape($name).")";
	db_query($sql, "could not add designation");
}

function update_designation($id, $name)
{
	$sql = "UPDATE ".TB_PREF."hr_designations SET name = ".db_escape($name)." WHERE id = ".db_escape($id);
	db_query($sql, "could not update designation");
}

function get_all_designations($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."hr_designations";
	if (!$all)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get designations");
}

function get_designation($id)
{
	$sql = "SELECT * FROM ".TB_PREF."hr_designations WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get designation");
	return db_fetch($result);
}

function delete_designation($id)
{
	$sql = "DELETE FROM ".TB_PREF."hr_designations WHERE id=".db_escape($id);
	db_query($sql, "could not delete designation");
}
