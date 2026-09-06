<?php
/*
	Banking & Financial Dashboard - the GL/Banking tab's "Total
	Receivable/Payable", sales trend chart, and Purchases/Expenses/Stock
	Value/Cash in Hand/Bank Balance panel from TechCloud's live dashboard,
	now backed by the real merged data.
*/
$page_security = 'SA_GLANALYTIC';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Banking and Financial Dashboard"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
// class.graphic.inc must load before dashboard.inc - dashboard.inc
// references Chart::$palette at its own top level, so loading it first
// fatals silently if the Chart class doesn't exist yet.
include_once($path_to_root . "/reporting/includes/class.graphic.inc");
include_once(__DIR__ . "/sales_ops_dashboard_db.inc");
include_once(__DIR__ . "/banking_dashboard_db.inc");

function kpi_card($value, $label)
{
	echo "<div class='knb-kpi-card'>"
		."<div class='knb-kpi-card-value'>".htmlspecialchars($value, ENT_QUOTES, 'UTF-8')."</div>"
		."<div class='knb-kpi-card-label'>".htmlspecialchars($label, ENT_QUOTES, 'UTF-8')."</div>"
		."</div>";
}

$bill = get_billing_summary();
echo "<div class='knb-kpi-section-title'>"._("Receivables & Payables")."</div>";
echo "<div class='knb-kpi-grid'>";
kpi_card(number_format2($bill['total_receivable'], 2), _('Total Receivable'));
kpi_card(number_format2($bill['total_payable'], 2), _('Total Payable'));
echo "</div>";

$cashbank = get_cash_and_bank_balance();
echo "<div class='knb-kpi-section-title'>"._("Purchases, Expenses & Cash Position")."</div>";
echo "<div class='knb-kpi-grid'>";
kpi_card(number_format2(get_total_purchases(), 2), _('Purchases (all-time)'));
kpi_card(number_format2(get_total_expenses(), 2), _('Expenses (all-time)'));
kpi_card(number_format2(get_stock_value(), 2), _('Stock Value'));
kpi_card(number_format2($cashbank['cash'], 2), _('Cash in Hand'));
kpi_card(number_format2($cashbank['bank'], 2), _('Bank Balance'));
kpi_card(get_low_stock_count(), _('Low Stock Items'));
echo "</div>";

display_note(_("Sales Trend (invoiced, by month)"), 1, 0);
$trend = get_sales_trend();
if (count($trend) > 0)
{
	$months = array();
	$totals = array();
	foreach ($trend as $row)
	{
		$months[] = $row['ym'];
		$totals[] = round((float)$row['total'], 2);
	}
	$pg = new Chart('line', 'sales_trend');
	$pg->setLabels($months);
	$pg->addSerie(_('Invoiced Sales'), $totals);
	$pg->setXTitle(_("Month"));
	$pg->setYTitle(_("Amount"));
	$pg->display();
}
else
	display_note(_("No invoiced sales recorded yet."), 0, 1);

end_page();
