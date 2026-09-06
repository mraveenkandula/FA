<?php
/*
	Real gap against the TechCloud mobile app scope: "Stock Check (QOH,
	Demand, Ordered)". Each part comes from a different, honestly-labeled
	source rather than one query pretending to be all three:

	- QOH: real stock_moves ledger balance (get_qoh_on_date) at this
	  distributor's own stock location - 0_locations.debtor_no already
	  ties one location 1:1 to almost every real distributor/customer.
	- Ordered: real remaining (not yet delivered) quantity across this
	  distributor's own open sales order lines - quantity minus qty_sent,
	  excluding short-closed lines.
	- Demand: the one part with no existing source in FA - the
	  distributor's own self-reported "what I still need", captured here
	  for the first time in 0_knb_distributor_demand.
*/

function get_stock_check_items()
{
	return db_query("SELECT stock_id, description, units FROM ".TB_PREF."stock_master
		WHERE !inactive ORDER BY description", "could not retrieve stock items");
}

function get_qoh_map($loc_code)
{
	$map = array();
	if (!$loc_code)
		return $map;
	$result = db_query("SELECT stock_id, SUM(qty) AS qoh FROM ".TB_PREF."stock_moves st
		LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
		WHERE ISNULL(v.id) AND loc_code=".db_escape($loc_code)."
		GROUP BY stock_id", "could not retrieve stock on hand");
	while ($row = db_fetch($result))
		$map[$row['stock_id']] = (float)$row['qoh'];
	return $map;
}

function get_ordered_map($debtor_no)
{
	$map = array();
	$result = db_query("SELECT sod.stk_code, SUM(sod.quantity - sod.qty_sent) AS ordered
		FROM ".TB_PREF."sales_order_details sod
		JOIN ".TB_PREF."sales_orders so ON so.order_no=sod.order_no AND so.trans_type=sod.trans_type
		WHERE so.debtor_no=".db_escape($debtor_no)."
		AND sod.trans_type=".ST_SALESORDER."
		AND (sod.quantity - sod.qty_sent - IFNULL(sod.short_close_qty,0)) > 0
		GROUP BY sod.stk_code", "could not retrieve open sales orders");
	while ($row = db_fetch($result))
		$map[$row['stk_code']] = (float)$row['ordered'];
	return $map;
}

function get_demand_map($debtor_no)
{
	$map = array();
	$result = db_query("SELECT stock_id, demand_qty FROM ".TB_PREF."knb_distributor_demand
		WHERE debtor_no=".db_escape($debtor_no), "could not retrieve demand");
	while ($row = db_fetch($result))
		$map[$row['stock_id']] = (float)$row['demand_qty'];
	return $map;
}

function save_demand($debtor_no, $stock_id, $qty)
{
	db_query("INSERT INTO ".TB_PREF."knb_distributor_demand (debtor_no, stock_id, demand_qty, updated_at)
		VALUES (".db_escape($debtor_no).",".db_escape($stock_id).",".db_escape($qty).", NOW())
		ON DUPLICATE KEY UPDATE demand_qty=".db_escape($qty).", updated_at=NOW()",
		"could not save demand");
}
