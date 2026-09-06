<?php
/*
	Card + chart data for the Banking & Financial Dashboard - the GL/Banking
	tab's "Total Receivable/Payable", sales trend chart, and
	Purchases/Expenses/Stock Value/Cash in Hand/Bank Balance panel seen on
	TechCloud's live dashboard.
*/

// Real business Purchases (gross invoiced, not net of what's still owed -
// that's total_payable, already on the Sales Ops Dashboard).
function get_total_purchases()
{
	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(ov_amount+ov_gst),0) FROM ".TB_PREF."supp_trans WHERE type=".ST_SUPPINVOICE));
	return round((float)$row[0], 2);
}

// Expenses = GL postings against any account under the "Costs" chart class
// (chart_class.ctype=6, see 0_chart_class) - the same classification FA's
// own P&L report uses, not a guess at specific account codes.
function get_total_expenses()
{
	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(g.amount),0) FROM ".TB_PREF."gl_trans g
		 JOIN ".TB_PREF."chart_master cm ON cm.account_code=g.account
		 JOIN ".TB_PREF."chart_types ct ON ct.id=cm.account_type
		 JOIN ".TB_PREF."chart_class cc ON cc.cid=ct.class_id
		 WHERE cc.ctype=6"));
	return round(abs((float)$row[0]), 2);
}

// Cash vs Bank split by FA's own bank_account_types (account_type=3 is
// "Cash Account" - see includes/sysnames.inc), balance via core's own
// get_gl_balance_from_to() against each account's linked GL code.
function get_cash_and_bank_balance()
{
	$cash = 0.0;
	$bank = 0.0;
	$result = db_query("SELECT account_code, account_type FROM ".TB_PREF."bank_accounts");
	while ($row = db_fetch($result))
	{
		$bal = (float)get_gl_balance_from_to('', Today(), $row['account_code']);
		if ($row['account_type'] == 3)
			$cash += $bal;
		else
			$bank += $bal;
	}
	return array('cash' => round($cash, 2), 'bank' => round($bank, 2));
}

// Stock value = SUM(quantity on hand x standard cost) across every active
// item - reuses core's get_qoh_on_date() the same way the low-stock check
// on the Sales Ops Dashboard does.
function get_stock_value()
{
	$total = 0.0;
	$result = db_query(
		"SELECT stock_id, purchase_cost FROM ".TB_PREF."stock_master WHERE inactive=0 AND mb_flag != 'D'");
	while ($row = db_fetch($result))
	{
		$qoh = get_qoh_on_date($row['stock_id']);
		if ($qoh)
			$total += $qoh * (float)$row['purchase_cost'];
	}
	return round($total, 2);
}

// Last 12 calendar months that actually have invoiced sales, oldest first -
// not a fixed "last 6 months from Today()" window, which would show
// nothing for a dataset whose real activity doesn't line up with today.
function get_sales_trend()
{
	$result = db_query(
		"SELECT DATE_FORMAT(tran_date, '%Y-%m') AS ym, SUM(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS total
		 FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE."
		 GROUP BY ym ORDER BY ym DESC LIMIT 12");
	$rows = array();
	while ($row = db_fetch($result))
		$rows[] = $row;
	return array_reverse($rows);
}
