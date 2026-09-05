<?php

function add_department($name)
{
	$sql = "INSERT INTO ".TB_PREF."hr_departments (name) VALUES (".db_escape($name).")";
	db_query($sql, "could not add department");
}

function update_department($id, $name)
{
	$sql = "UPDATE ".TB_PREF."hr_departments SET name = ".db_escape($name)." WHERE id = ".db_escape($id);
	db_query($sql, "could not update department");
}

function get_all_departments($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."hr_departments";
	if (!$all)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get departments");
}

function get_department($id)
{
	$sql = "SELECT * FROM ".TB_PREF."hr_departments WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get department");
	return db_fetch($result);
}

function delete_department($id)
{
	$sql = "DELETE FROM ".TB_PREF."hr_departments WHERE id=".db_escape($id);
	db_query($sql, "could not delete department");
}
