<?php

function add_town($name, $territory_id, $district, $state)
{
	db_query("INSERT INTO ".TB_PREF."sales_towns (name, territory_id, district, state) VALUES ("
		.db_escape($name).",".db_escape($territory_id ?: null, true).","
		.db_escape($district ?: null, true).",".db_escape($state ?: null, true).")",
		"could not add town");
}

function update_town($id, $name, $territory_id, $district, $state)
{
	db_query("UPDATE ".TB_PREF."sales_towns SET name=".db_escape($name).", territory_id=".db_escape($territory_id ?: null, true)
		.", district=".db_escape($district ?: null, true).", state=".db_escape($state ?: null, true)." WHERE id=".db_escape($id),
		"could not update town");
}

function get_all_towns($all=false)
{
	$sql = "SELECT t.*, z.name AS territory_name FROM ".TB_PREF."sales_towns t
		LEFT JOIN ".TB_PREF."sales_territories z ON z.id = t.territory_id";
	if (!$all) $sql .= " WHERE !t.inactive";
	$sql .= " ORDER BY t.name";
	return db_query($sql, "could not get towns");
}

function get_town($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."sales_towns WHERE id=".db_escape($id), "could not get town");
	return db_fetch($result);
}

function delete_town($id)
{
	db_query("DELETE FROM ".TB_PREF."sales_towns WHERE id=".db_escape($id), "could not delete town");
}
