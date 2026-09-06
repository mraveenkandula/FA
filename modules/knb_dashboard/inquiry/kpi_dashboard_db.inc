<?php
/*
	Ghee/dairy-specific KPIs that don't exist anywhere in core FrontAccounting
	(production yield, scheme-slab proximity, target-vs-achievement rollup,
	churn risk) or in any knb_* module built so far (each built its own
	single-purpose inquiry; this rolls the cross-cutting "how is the
	business doing right now" view up in one place, reusing core's real
	Chart class rather than a new charting library).
*/

/*
	Average production yield % per month, split by stage, for the last
	$months months - a real quality/efficiency signal (a sustained yield
	drop usually means a process or raw-material problem worth investigating
	before it shows up as a cost overrun).
*/
function get_production_yield_trend($months = 6)
{
	$sql = "SELECT DATE_FORMAT(w.date_, '%Y-%m') AS ym, q.stage,
			AVG(CASE WHEN iss.total_input_qty > 0 THEN w.units_issued / iss.total_input_qty * 100 ELSE NULL END) AS avg_yield,
			COUNT(*) AS batch_count
		FROM ".TB_PREF."knb_production_quality q
		JOIN ".TB_PREF."workorders w ON w.id = q.workorder_id
		LEFT JOIN (
			SELECT wi.workorder_id, ABS(SUM(wii.qty_issued)) AS total_input_qty
			FROM ".TB_PREF."wo_issues wi
			JOIN ".TB_PREF."wo_issue_items wii ON wii.issue_id = wi.issue_no
			GROUP BY wi.workorder_id
		) iss ON iss.workorder_id = w.id
		WHERE w.date_ >= DATE_SUB(CURDATE(), INTERVAL $months MONTH)
		GROUP BY ym, q.stage
		ORDER BY ym";
	$result = db_query($sql, "could not get production yield trend");
	$rows = array();
	while ($row = db_fetch_assoc($result))
		$rows[] = $row;
	return $rows;
}

/*
	Current-month sales target vs actual, summed across every employee with
	a target set this month (reuses the same actual-sales computation as
	knb_sales_ext's incentive inquiry, just aggregated rather than per-
	employee).
*/
function get_target_vs_achievement_summary($period_month)
{
	$sql = "SELECT e.id AS employee_id, e.first_name, e.last_name, t.target_amount,
			(SELECT SUM(ABS(dtd.quantity * dtd.unit_price))
				FROM ".TB_PREF."debtor_trans_details dtd
				JOIN ".TB_PREF."debtor_trans dt ON dt.trans_no = dtd.debtor_trans_no AND dt.type = dtd.debtor_trans_type
				JOIN ".TB_PREF."customer_distribution cd ON cd.debtor_no = dt.debtor_no
				WHERE dt.type = ".ST_SALESINVOICE."
				AND cd.sales_employee_id = e.id
				AND dt.tran_date >= ".db_escape($period_month)."
				AND dt.tran_date <= LAST_DAY(".db_escape($period_month).")
			) AS actual_sales
		FROM ".TB_PREF."knb_sales_targets t
		JOIN ".TB_PREF."hr_employees e ON e.id = t.sales_employee_id
		WHERE t.period_month = ".db_escape($period_month);
	$result = db_query($sql, "could not get target vs achievement summary");
	$rows = array();
	while ($row = db_fetch_assoc($result))
	{
		$row['actual_sales'] = $row['actual_sales'] ? (float)$row['actual_sales'] : 0;
		$rows[] = $row;
	}
	return $rows;
}

/*
	Customers who have ordered before but not in the last $days_inactive
	days - a churn-risk list a sales manager can act on before it becomes a
	permanent loss, not just a lagging total-revenue number.
*/
function get_churn_risk_customers($days_inactive = 60, $limit = 15)
{
	$sql = "SELECT d.debtor_no, d.name, MAX(dt.tran_date) AS last_order_date,
			DATEDIFF(CURDATE(), MAX(dt.tran_date)) AS days_since_last_order
		FROM ".TB_PREF."debtor_trans dt
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = dt.debtor_no
		WHERE dt.type = ".ST_SALESINVOICE."
		GROUP BY d.debtor_no, d.name
		HAVING days_since_last_order >= ".(int)$days_inactive."
		ORDER BY days_since_last_order DESC
		LIMIT ".(int)$limit;
	$result = db_query($sql, "could not get churn risk customers");
	$rows = array();
	while ($row = db_fetch_assoc($result))
		$rows[] = $row;
	return $rows;
}

/*
	Customers within reach of their next scheme slab right now - a genuine
	"one more case and they unlock the next tier" nudge list for the sales
	team, not just a report of what already happened.
*/
function get_scheme_slab_opportunities($within_pct = 20)
{
	$schemes_result = db_query("SELECT * FROM ".TB_PREF."knb_schemes
		WHERE !inactive AND CURDATE() BETWEEN start_date AND end_date", "could not get active schemes");
	$opportunities = array();
	$active_schemes = array();
	while ($scheme = db_fetch_assoc($schemes_result))
		$active_schemes[] = $scheme;

	foreach ($active_schemes as $scheme)
	{
		$slabs = get_scheme_slabs($scheme['id']);
		if (empty($slabs))
			continue;
		$achievement_result = get_scheme_achievement($scheme);
		while ($customer = db_fetch_assoc($achievement_result))
		{
			$reached = find_best_qualifying_slab($slabs, $customer['total_qty']);
			$reached_target = $reached ? $reached['slab_target_qty'] : 0;
			$next_slab = null;
			foreach ($slabs as $slab)
			{
				if ($slab['slab_target_qty'] > $reached_target
					&& ($next_slab === null || $slab['slab_target_qty'] < $next_slab['slab_target_qty']))
					$next_slab = $slab;
			}
			if (!$next_slab)
				continue;
			$remaining = $next_slab['slab_target_qty'] - $customer['total_qty'];
			$pct_remaining = ($remaining / $next_slab['slab_target_qty']) * 100;
			if ($pct_remaining > 0 && $pct_remaining <= $within_pct)
			{
				$opportunities[] = array(
					'customer_name' => $customer['customer_name'],
					'current_qty' => $customer['total_qty'],
					'next_slab_target' => $next_slab['slab_target_qty'],
					'remaining_qty' => $remaining,
					'reward' => $next_slab['scheme_text'],
				);
			}
		}
	}
	return $opportunities;
}

/*
	Pending-action counts across every knb_* approval workflow built so far -
	things sitting unaddressed are exactly the "early detection of problems"
	the KPI dashboard is meant to surface, not just revenue numbers.
*/
function get_pending_action_counts()
{
	$counts = array();
	$counts['leave_requests'] = db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."knb_leave_entries WHERE status='Pending'"))[0];
	$counts['purchase_indents'] = db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."purchase_indents WHERE status='Pending'"))[0];
	$counts['stock_transfers'] = db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."stock_transfer_requests WHERE status='Pending'"))[0];
	$counts['expense_claims'] = db_fetch_row(db_query(
		"SELECT COUNT(*) FROM ".TB_PREF."hr_expense_claims WHERE status='Pending'"))[0];
	return $counts;
}
