<?php
// matches gl_month_performance()'s own internal check_page_security() call
// below - that function calls exit() if the user lacks SA_GLANALYTIC, which
// would silently kill this whole page mid-render for anyone who passed a
// looser gate here but not that one. This is a management/owner-level view
// anyway (revenue trend, target achievement, churn risk), not a general
// field-rep screen, so gating the whole page at that same level is the
// right fit, not just a workaround.
$page_security = 'SA_GLANALYTIC';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "KPI Dashboard"));

include_once($path_to_root . "/includes/ui.inc");
// dashboard.inc references Chart::$palette at its own top level (not inside
// a function), so class.graphic.inc must load first or that include fatals
// immediately - silently, since an uncaught error here happens before any
// of this page's own output.
include_once($path_to_root . "/reporting/includes/class.graphic.inc");
include_once($path_to_root . "/includes/dashboard.inc");
include_once(__DIR__ . "/kpi_dashboard_db.inc");
include_once($path_to_root . "/modules/knb_schemes/manage/schemes_db.inc");

$today = Today();

// ---- Operational: things sitting unaddressed right now ----
$pending = get_pending_action_counts();
echo "<table width='100%'><tr valign=top>";
echo "<td align='center' style='width:25%;'><div class='square square2'>"._('Pending Leave Requests')
	."<p class='span1'>".$pending['leave_requests']."</p></div></td>";
echo "<td align='center' style='width:25%;'><div class='square square2'>"._('Pending Purchase Indents')
	."<p class='span1'>".$pending['purchase_indents']."</p></div></td>";
echo "<td align='center' style='width:25%;'><div class='square square2'>"._('Pending Stock Transfers')
	."<p class='span1'>".$pending['stock_transfers']."</p></div></td>";
echo "<td align='center' style='width:25%;'><div class='square square2'>"._('Pending Expense Claims')
	."<p class='span1'>".$pending['expense_claims']."</p></div></td>";
echo "</tr></table><br>";

// ---- Revenue / Costs / Profit trend (reuses core's own GL performance widget) ----
table('100%', '100%');
gl_month_performance($today, 100, 6);
table_end();

// ---- Production yield trend (new - the core dairy quality/efficiency signal) ----
$yield_rows = get_production_yield_trend(6);
$months = array();
$series = array('RM_TO_SFG' => array(), 'SFG_TO_FG' => array());
foreach ($yield_rows as $row)
{
	if (!in_array($row['ym'], $months))
		$months[] = $row['ym'];
}
sort($months);
foreach ($months as $ym)
{
	foreach (array('RM_TO_SFG', 'SFG_TO_FG') as $stage)
	{
		$match = null;
		foreach ($yield_rows as $row)
			if ($row['ym'] == $ym && $row['stage'] == $stage)
				$match = $row;
		$series[$stage][] = $match ? round($match['avg_yield'], 1) : 0;
	}
}
if (count($months) > 0)
{
	$pg = new Chart('line', 'yield_trend');
	$pg->setLabels($months);
	$pg->addSerie(_('RM -> SFG Yield %'), $series['RM_TO_SFG']);
	$pg->addSerie(_('SFG -> FG Yield %'), $series['SFG_TO_FG']);
	$pg->setXTitle(_("Month"));
	$pg->setYTitle(_("Yield %"));
	display_title(_("Production Yield Trend (last 6 months)"));
	$pg->display();
}
else
	display_note(_("No production batches recorded yet."), 1);

// ---- Sales target vs achievement, current month ----
$period_month = date('Y-m-01');
$target_rows = get_target_vs_achievement_summary($period_month);
$total_target = 0;
$total_actual = 0;
foreach ($target_rows as $row)
{
	$total_target += $row['target_amount'];
	$total_actual += $row['actual_sales'];
}
display_title(_("Sales Target vs Achievement (this month)"));
if (count($target_rows) > 0)
{
	echo "<table width='100%'><tr valign=top>";
	echo "<td align='center' style='width:33%;'><div class='square square1'>"._('Total Target')
		."<p class='span1'>".number_format2($total_target)."</p></div></td>";
	echo "<td align='center' style='width:33%;'><div class='square square1'>"._('Total Achieved')
		."<p class='span1'>".number_format2($total_actual)."</p></div></td>";
	$pct = $total_target > 0 ? round($total_actual / $total_target * 100, 1) : 0;
	echo "<td align='center' style='width:33%;'><div class='square".($pct >= 100 ? '1' : '2')."'>"._('Achievement %')
		."<p class='span1'>$pct%</p></div></td>";
	echo "</tr></table>";

	start_table(TABLESTYLE, "width='70%'");
	table_header(array(_('Employee'), _('Target'), _('Achieved'), _('%')));
	$k = 0;
	foreach ($target_rows as $row)
	{
		alt_table_row_color($k);
		label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
		amount_cell($row['target_amount']);
		amount_cell($row['actual_sales']);
		$row_pct = $row['target_amount'] > 0 ? round($row['actual_sales'] / $row['target_amount'] * 100, 1) : 0;
		label_cell($row_pct.'%');
		end_row();
	}
	end_table(1);
}
else
	display_note(_("No sales targets set for this month yet."), 1);

// ---- Scheme slab opportunities: customers close to unlocking their next tier ----
display_title(_("Scheme Opportunities - customers close to their next slab"));
$opportunities = get_scheme_slab_opportunities(20);
if (count($opportunities) > 0)
{
	start_table(TABLESTYLE, "width='90%'");
	table_header(array(_('Customer'), _('Current Qty'), _('Next Slab Target'), _('Qty Remaining'), _('Reward')));
	$k = 0;
	foreach ($opportunities as $row)
	{
		alt_table_row_color($k);
		label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
		amount_cell($row['current_qty']);
		amount_cell($row['next_slab_target']);
		amount_cell($row['remaining_qty']);
		label_cell(htmlspecialchars($row['reward'], ENT_QUOTES, 'UTF-8'));
		end_row();
	}
	end_table(1);
}
else
	display_note(_("No customers currently within reach of a scheme's next slab."), 1);

// ---- Customer churn risk ----
display_title(_("Customer Churn Risk (no order in 60+ days)"));
$churn_rows = get_churn_risk_customers(60);
if (count($churn_rows) > 0)
{
	start_table(TABLESTYLE, "width='70%'");
	table_header(array(_('Customer'), _('Last Order Date'), _('Days Since')));
	$k = 0;
	foreach ($churn_rows as $row)
	{
		alt_table_row_color($k);
		label_cell(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
		label_cell(sql2date($row['last_order_date']));
		label_cell($row['days_since_last_order']);
		end_row();
	}
	end_table(1);
}
else
	display_note(_("No customers flagged as churn risk."), 1);

// ---- Low stock (reuses core's own reorder-level widget) ----
stock_below_reorder($today, 0);

end_page();
