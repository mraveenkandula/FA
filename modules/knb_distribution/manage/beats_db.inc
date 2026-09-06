<?php

function add_beat($name, $town_id)
{
	db_query("INSERT INTO ".TB_PREF."sales_beats (name, town_id) VALUES ("
		.db_escape($name).",".db_escape($town_id ?: null, true).")", "could not add beat");
}

function update_beat($id, $name, $town_id)
{
	db_query("UPDATE ".TB_PREF."sales_beats SET name=".db_escape($name).", town_id=".db_escape($town_id ?: null, true)
		." WHERE id=".db_escape($id), "could not update beat");
}

function get_all_beats($all=false)
{
	$sql = "SELECT b.*, t.name AS town_name FROM ".TB_PREF."sales_beats b
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = b.town_id";
	if (!$all) $sql .= " WHERE !b.inactive";
	$sql .= " ORDER BY b.name";
	return db_query($sql, "could not get beats");
}

function get_beat($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."sales_beats WHERE id=".db_escape($id), "could not get beat");
	return db_fetch($result);
}

function delete_beat($id)
{
	db_query("DELETE FROM ".TB_PREF."sales_beats WHERE id=".db_escape($id), "could not delete beat");
}
