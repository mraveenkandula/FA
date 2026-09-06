<?php

function add_sub_category($name) {
	db_query("INSERT INTO ".TB_PREF."item_sub_categories (name) VALUES (".db_escape($name).")", "could not add sub category");
}
function update_sub_category($id, $name) {
	db_query("UPDATE ".TB_PREF."item_sub_categories SET name=".db_escape($name)." WHERE id=".db_escape($id), "could not update sub category");
}
function get_all_sub_categories($all=false) {
	$sql = "SELECT * FROM ".TB_PREF."item_sub_categories";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get sub categories");
}
function get_sub_category($id) {
	$result = db_query("SELECT * FROM ".TB_PREF."item_sub_categories WHERE id=".db_escape($id), "could not get sub category");
	return db_fetch($result);
}
function delete_sub_category($id) {
	db_query("DELETE FROM ".TB_PREF."item_sub_categories WHERE id=".db_escape($id), "could not delete sub category");
}

function add_brand($name) {
	db_query("INSERT INTO ".TB_PREF."item_brands (name) VALUES (".db_escape($name).")", "could not add brand");
}
function update_brand($id, $name) {
	db_query("UPDATE ".TB_PREF."item_brands SET name=".db_escape($name)." WHERE id=".db_escape($id), "could not update brand");
}
function get_all_brands($all=false) {
	$sql = "SELECT * FROM ".TB_PREF."item_brands";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get brands");
}
function get_brand($id) {
	$result = db_query("SELECT * FROM ".TB_PREF."item_brands WHERE id=".db_escape($id), "could not get brand");
	return db_fetch($result);
}
function delete_brand($id) {
	db_query("DELETE FROM ".TB_PREF."item_brands WHERE id=".db_escape($id), "could not delete brand");
}

function add_capacity($name) {
	db_query("INSERT INTO ".TB_PREF."item_capacities (name) VALUES (".db_escape($name).")", "could not add capacity");
}
function update_capacity($id, $name) {
	db_query("UPDATE ".TB_PREF."item_capacities SET name=".db_escape($name)." WHERE id=".db_escape($id), "could not update capacity");
}
function get_all_capacities($all=false) {
	$sql = "SELECT * FROM ".TB_PREF."item_capacities";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get capacities");
}
function get_capacity($id) {
	$result = db_query("SELECT * FROM ".TB_PREF."item_capacities WHERE id=".db_escape($id), "could not get capacity");
	return db_fetch($result);
}
function delete_capacity($id) {
	db_query("DELETE FROM ".TB_PREF."item_capacities WHERE id=".db_escape($id), "could not delete capacity");
}

function get_item_classification($stock_id) {
	$result = db_query("SELECT * FROM ".TB_PREF."item_classification WHERE stock_id=".db_escape($stock_id), "could not get item classification");
	return db_fetch($result);
}
function save_item_classification($stock_id, $sub_category_id, $brand_id, $capacity_id) {
	db_query("REPLACE INTO ".TB_PREF."item_classification (stock_id, sub_category_id, brand_id, capacity_id) VALUES ("
		.db_escape($stock_id).",".db_escape($sub_category_id ?: null, true).","
		.db_escape($brand_id ?: null, true).",".db_escape($capacity_id ?: null, true).")",
		"could not save item classification");
}
