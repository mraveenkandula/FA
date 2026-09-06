<?php
/*
	KNB Group KPI Dashboard.
	The "Dashboards Details" gap confirmed against TechCloud's live Sales
	menu - a cross-cutting view of production yield, sales target vs
	achievement, scheme-slab opportunities, customer churn risk, and
	pending-approval counts, alongside core FrontAccounting's own revenue/
	cost trend and low-stock widgets. No new tables - every widget here is a
	read-only aggregation over data the other knb_* modules already write.
*/

class hooks_knb_dashboard extends hooks
{
	var $module_name = 'knb_dashboard';

	function install_options($app)
	{
		switch ($app->id) {
			case 'orders':
				$app->add_lapp_function(1, _("&KPI Dashboard"),
					"modules/knb_dashboard/inquiry/kpi_dashboard.php", 'SA_GLANALYTIC', MENU_INQUIRY);
				$app->add_lapp_function(1, _("&Sales Ops Dashboard"),
					"modules/knb_dashboard/inquiry/sales_ops_dashboard.php", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
				break;
		}
	}
}
