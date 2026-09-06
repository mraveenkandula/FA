<?php
/*
	Sales Ops Dashboard - replicates the "Today's Insights" / "Billing &
	Financial" card-grid pattern seen across TechCloud's live Sales tabs
	(Sales Order Value Overview, Daily/Monthly Analysis, Customer
	Deliveries, Billing to Customer), now that the real TechCloud data
	export has been merged in and there's something real to show.

	"Today" cards are genuinely live counters, not a re-labeled all-time
	total - they will mostly read 0 right after a historical data import
	with no fresh activity yet, exactly like TechCloud's own live dashboard
	did in the reference screenshots. That's correct, not a bug.
*/
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Ops Dashboard"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
include_once(__DIR__ . "/sales_ops_dashboard_db.inc");

function kpi_card($value, $label)
{
	echo "<div class='knb-kpi-card'>"
		."<div class='knb-kpi-card-value'>".htmlspecialchars($value, ENT_QUOTES, 'UTF-8')."</div>"
		."<div class='knb-kpi-card-label'>".htmlspecialchars($label, ENT_QUOTES, 'UTF-8')."</div>"
		."</div>";
}

// ---- Today's Insights ----
echo "<div class='knb-kpi-section-title'>"._("Today's Insights")."</div>";
$today = get_todays_counters();
echo "<div class='knb-kpi-grid'>";
kpi_card($today['orders_today'], _('Orders Placed Today'));
kpi_card($today['outlets_added_today'], _('New Outlets Added Today'));
kpi_card(number_format2($today['ghee_volume_today'], 1), _('Ghee Volume Today (Ltrs)'));
kpi_card(number_format2($today['honey_volume_today'], 1), _('Honey Volume Today (kgs)'));
echo "</div>";

// ---- Outlets / Beats / Towns ----
echo "<div class='knb-kpi-section-title'>"._("Outlets, Beats & Towns")."</div>";
$obt = get_outlet_beat_town_summary();
echo "<div class='knb-kpi-grid'>";
kpi_card($obt['outlets_assigned'], _('Total Outlets Assigned'));
kpi_card($obt['productive_outlets'], _('Total Productive Outlets'));
kpi_card($obt['beats_mapped'], _('Beats Mapped'));
kpi_card($obt['towns_mapped'], _('Towns Mapped'));
echo "</div>";

// ---- Sales Orders ----
echo "<div class='knb-kpi-section-title'>"._("Sales Orders (all-time)")."</div>";
$so = get_sales_order_summary();
echo "<div class='knb-kpi-grid'>";
kpi_card($so['total_orders'], _('Total Sales Orders'));
kpi_card($so['approved_orders'], _('Approved Orders'));
kpi_card($so['pending_orders'], _('Pending Orders'));
echo "</div>";

// ---- Ghee / Honey secondary volume (all-time) ----
echo "<div class='knb-kpi-section-title'>"._("Secondary Sales Volume (all-time)")."</div>";
$vol = get_product_volume_summary();
echo "<div class='knb-kpi-grid'>";
kpi_card(number_format2($vol['ghee_volume'], 1), _('Ghee Secondary (In Ltrs)'));
kpi_card(number_format2($vol['honey_volume'], 1), _('Honey Secondary (In Kgs)'));
echo "</div>";

// ---- Deliveries ----
echo "<div class='knb-kpi-section-title'>"._("Deliveries")."</div>";
$delv = get_delivery_summary();
echo "<div class='knb-kpi-grid'>";
kpi_card($delv['total_deliveries'], _('Total Deliveries'));
kpi_card($delv['pending_deliveries'], _('Pending Deliveries'));
kpi_card($delv['deliveries_today'], _("Today's Deliveries"));
echo "</div>";

// ---- Billing & Financial ----
echo "<div class='knb-kpi-section-title'>"._("Billing & Financial")."</div>";
$bill = get_billing_summary();
echo "<div class='knb-kpi-grid'>";
kpi_card($bill['total_invoices'], _('Total Invoices'));
kpi_card($bill['pending_invoices'], _('Pending Invoices'));
kpi_card(number_format2($bill['total_receivable'], 2), _('Total Receivable'));
kpi_card(number_format2($bill['total_payable'], 2), _('Total Payable'));
kpi_card(get_low_stock_count(), _('Low Stock Items'));
echo "</div>";

end_page();
