<?php

function add_territory($name)
{
	db_query("INSERT INTO ".TB_PREF."sales_territories (name) VALUES (".db_escape($name).")",
		"could not add territory");
}

function update_territory($id, $name)
{
	db_query("UPDATE ".TB_PREF."sales_territories SET name=".db_escape($name)." WHERE id=".db_escape($id),
		"could not update territory");
}

function get_all_territories($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_territories";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get territories");
}

function get_territory($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."sales_territories WHERE id=".db_escape($id), "could not get territory");
	return db_fetch($result);
}

function delete_territory($id)
{
	db_query("DELETE FROM ".TB_PREF."sales_territories WHERE id=".db_escape($id), "could not delete territory");
}
