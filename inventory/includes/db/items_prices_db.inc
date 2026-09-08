<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
function add_item_price($stock_id, $sales_type_id, $curr_abrev, $price, $pieces_price=0)
{
	$sql = "INSERT INTO ".TB_PREF."prices (stock_id, sales_type_id, curr_abrev, price, pieces_price)
		VALUES (".db_escape($stock_id).", ".db_escape($sales_type_id)
		.", ".db_escape($curr_abrev).", ".db_escape($price).", ".db_escape($pieces_price).")";

	db_query($sql,"an item price could not be added");
}

// $pieces_price=null (the default) leaves the existing pieces_price value
// untouched - important because this function is also called by
// modules/knb_inventory_ext/manage/price_import_db.inc's bulk CSV import,
// which only ever updates the box/case price and must not silently zero out
// a real per-piece price that was set separately via this page.
function update_item_price($price_id, $sales_type_id, $curr_abrev, $price, $pieces_price=null)
{
	$sql = "UPDATE ".TB_PREF."prices SET sales_type_id=".db_escape($sales_type_id).",
		curr_abrev=".db_escape($curr_abrev).",
		price=".db_escape($price);
	if ($pieces_price !== null)
		$sql .= ", pieces_price=".db_escape($pieces_price);
	$sql .= " WHERE id=".db_escape($price_id);

	db_query($sql,"an item price could not be updated");
}

function delete_item_price($price_id)
{
	$sql="DELETE FROM ".TB_PREF."prices WHERE id= ".db_escape($price_id);
	db_query($sql,"an item price could not be deleted");
}

function get_prices($stock_id)
{
	$sql = "SELECT pricelist.sales_type, price.* 
		FROM ".TB_PREF."prices price, "
			.TB_PREF."sales_types pricelist
		WHERE price.sales_type_id = pricelist.id
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, sales_type_id";

	return db_query($sql,"item prices could not be retreived");
}

function get_stock_price($price_id)
{
	$sql = "SELECT * FROM ".TB_PREF."prices WHERE id=".db_escape($price_id);

	$result = db_query($sql,"price could not be retreived");

	return db_fetch($result);
}

function get_stock_price_type_currency($stock_id, $type, $currency)
{
	$sql = "SELECT * FROM ".TB_PREF."prices WHERE stock_id=".db_escape($stock_id)."
		AND sales_type_id=".db_escape($type)."
		AND curr_abrev=".db_escape($currency);

	$result = db_query($sql,"price could not be retreived");

	return db_fetch($result);
}
