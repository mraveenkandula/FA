<?php
/*
	KNB Group Distribution extension.
	Adds the stockist/super-stockist/distributor/retailer hierarchy - territory,
	town, beat, and per-customer classification - to the existing Sales app,
	matching the pilot customer's real distribution structure (confirmed live:
	51 territories, ~83 towns, ~140 beats, 3 customer tiers, 2699 retailers).
*/

class hooks_knb_distribution extends hooks
{
	var $module_name = 'knb_distribution';

	function install_options($app)
	{
		switch ($app->id) {
			case 'orders':
				$app->add_lapp_function(2, _("&Territories"),
					"modules/knb_distribution/manage/territories.php", 'SA_OPEN', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("T&owns"),
					"modules/knb_distribution/manage/towns.php", 'SA_OPEN', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("&Beats"),
					"modules/knb_distribution/manage/beats.php", 'SA_OPEN', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("Customer &Distribution Mapping"),
					"modules/knb_distribution/manage/customer_mapping.php", 'SA_OPEN', MENU_MAINTENANCE);
				$app->add_lapp_function(1, _("Customers by Territor&y/Beat"),
					"modules/knb_distribution/inquiry/customers_by_area.php", 'SA_OPEN', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_distribution.sql' => array('sales_territories', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
