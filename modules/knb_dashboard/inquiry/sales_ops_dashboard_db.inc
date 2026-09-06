<?php
/*
	Card data for the Sales Ops Dashboard - the "Today's Insights" /
	"Billing & Financial" style card grid seen on TechCloud's live Sales
	tabs (Sales Order Value Overview, Daily/Monthly Analysis, Customer
	Deliveries, Billing to Customer). Split into "today" (genuinely live
	operational counters - correctly mostly zero right after a historical
	data import, same as TechCloud's own live dashboard showed) and
	"all-time" (the real business-volume numbers the import actually gives
	us something to show).
*/

function get_todays_counters()
{
	$today = date2sql(Today());
	$counts = array();

	$counts['orders_today'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_orders WHERE ord_date='$today'"))[0];

	$counts['outlets_added_today'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."debtors_master WHERE created_date='$today'"))[0];

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(d.qty_in_liters_kgs),0) FROM ".TB_PREF."debtor_trans_details d
		 JOIN ".TB_PREF."debtor_trans t ON t.trans_no=d.debtor_trans_no AND t.type=d.debtor_trans_type
		 WHERE t.tran_date='$today' AND d.description LIKE '%GHEE%'"));
	$counts['ghee_volume_today'] = round((float)$row[0], 1);

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(d.qty_in_liters_kgs),0) FROM ".TB_PREF."debtor_trans_details d
		 JOIN ".TB_PREF."debtor_trans t ON t.trans_no=d.debtor_trans_no AND t.type=d.debtor_trans_type
		 WHERE t.tran_date='$today' AND d.description LIKE '%HONEY%'"));
	$counts['honey_volume_today'] = round((float)$row[0], 1);

	return $counts;
}

function get_outlet_beat_town_summary()
{
	$s = array();
	$s['outlets_assigned'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."debtors_master WHERE inactive=0"))[0];
	$s['productive_outlets'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(DISTINCT debtor_no) FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE))[0];
	$s['beats_mapped'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_beats"))[0];
	$s['towns_mapped'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_towns"))[0];
	return $s;
}

function get_sales_order_summary()
{
	$s = array();
	$s['total_orders'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_orders"))[0];
	$s['approved_orders'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_orders WHERE approve_status=1"))[0];
	$s['pending_orders'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."sales_orders WHERE approve_status=0"))[0];
	return $s;
}

// All-time Ghee/Honey secondary sales volume (qty_in_liters_kgs), the same
// metric TechCloud's cards label "Ghee Secondary (In Ltrs)" / "Honey
// Secondary (in kgs)" - secondary sales meaning outlet-facing invoiced
// quantity, as opposed to primary (factory-to-distributor) volume.
function get_product_volume_summary()
{
	$s = array();
	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(qty_in_liters_kgs),0) FROM ".TB_PREF."debtor_trans_details WHERE description LIKE '%GHEE%'"));
	$s['ghee_volume'] = round((float)$row[0], 1);
	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(qty_in_liters_kgs),0) FROM ".TB_PREF."debtor_trans_details WHERE description LIKE '%HONEY%'"));
	$s['honey_volume'] = round((float)$row[0], 1);
	return $s;
}

function get_billing_summary()
{
	$s = array();
	$s['total_invoices'] = (int)db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE))[0];

	$row = db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."debtor_trans
		 WHERE type=".ST_SALESINVOICE." AND alloc < (ov_amount+ov_gst+ov_freight+ov_freight_tax)"));
	$s['pending_invoices'] = (int)$row[0];

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(ov_amount+ov_gst+ov_freight+ov_freight_tax-alloc),0)
		 FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE));
	$s['total_receivable'] = round((float)$row[0], 2);

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(ov_amount+ov_gst-alloc),0)
		 FROM ".TB_PREF."supp_trans WHERE type=".ST_SUPPINVOICE));
	$s['total_payable'] = round((float)$row[0], 2);

	return $s;
}

// Reuses core's own per-item-per-location QOH function
// (includes/db/inventory_db.inc) - reorder_level is set per (stock_id,
// loc_code) in loc_stock, so the QOH check must be scoped to that same
// location, not the item's company-wide total (which would rarely trip
// a single location's reorder trigger). Only rows with a real
// reorder_level configured are checked.
function get_low_stock_count()
{
	$count = 0;
	$result = db_query(
		"SELECT ls.stock_id, ls.loc_code, ls.reorder_level
		 FROM ".TB_PREF."loc_stock ls
		 JOIN ".TB_PREF."stock_master sm ON sm.stock_id=ls.stock_id
		 WHERE ls.reorder_level > 0 AND sm.inactive=0");
	while ($row = db_fetch($result))
	{
		$qoh = get_qoh_on_date($row['stock_id'], $row['loc_code']);
		if ($qoh !== null && $qoh < $row['reorder_level'])
			$count++;
	}
	return $count;
}
