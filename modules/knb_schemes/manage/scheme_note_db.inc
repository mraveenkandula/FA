<?php

function add_scheme_note($brand_id, $stock_id, $person_type)
{
	db_query("INSERT INTO ".TB_PREF."knb_scheme_notes (brand_id, stock_id, person_type)
		VALUES (".db_escape($brand_id).",".db_escape($stock_id).",".db_escape($person_type ?: null, true).")",
		"could not add scheme note");
}

function update_scheme_note($id, $brand_id, $stock_id, $person_type)
{
	db_query("UPDATE ".TB_PREF."knb_scheme_notes SET
		brand_id=".db_escape($brand_id).",
		stock_id=".db_escape($stock_id).",
		person_type=".db_escape($person_type ?: null, true)."
		WHERE id=".db_escape($id),
		"could not update scheme note");
}

function delete_scheme_note($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_scheme_notes WHERE id=".db_escape($id),
		"could not delete scheme note");
}

function get_scheme_note($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_scheme_notes WHERE id=".db_escape($id));
	return db_fetch($result);
}

function get_all_scheme_notes($show_inactive=false, $brand_id=null)
{
	$sql = "SELECT n.*, b.name AS brand_name, s.description AS item_description
		FROM ".TB_PREF."knb_scheme_notes n
		LEFT JOIN ".TB_PREF."item_brands b ON b.id = n.brand_id
		LEFT JOIN ".TB_PREF."stock_master s ON s.stock_id = n.stock_id";
	$where = array();
	if (!$show_inactive)
		$where[] = "n.inactive=0";
	if ($brand_id)
		$where[] = "n.brand_id=".db_escape($brand_id);
	if ($where)
		$sql .= " WHERE ".implode(" AND ", $where);
	$sql .= " ORDER BY b.name, s.description";
	return db_query($sql);
}
