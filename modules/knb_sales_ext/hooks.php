<?php
/*
	KNB Group Sales Extensions.
	Fills confirmed Sales-side gaps against TechCloud that neither core
	FrontAccounting nor knb_distribution cover: employee sales targets and
	incentive tiers, journey (beat visit) planning, and lead capture.
*/

class hooks_knb_sales_ext extends hooks
{
	var $module_name = 'knb_sales_ext';

	function install_options($app)
	{
		switch ($app->id) {
			case 'orders':
				$app->add_lapp_function(0, _("&Journey Plan Entry"),
					"modules/knb_sales_ext/manage/journey_plan_entry.php", 'SA_CUSTOMER', MENU_TRANSACTION);
				$app->add_lapp_function(0, _("&Lead Entry"),
					"modules/knb_sales_ext/manage/lead_entry.php", 'SA_CUSTOMER', MENU_TRANSACTION);
				$app->add_lapp_function(0, _("Sales &Target Entry"),
					"modules/knb_sales_ext/manage/target_entry.php", 'SA_CUSTOMER', MENU_TRANSACTION);

				$app->add_lapp_function(1, _("Journey Plan In&quiry"),
					"modules/knb_sales_ext/inquiry/journey_plan_inquiry.php", 'SA_CUSTOMER', MENU_INQUIRY);
				$app->add_lapp_function(1, _("Lead In&quiry"),
					"modules/knb_sales_ext/inquiry/lead_inquiry.php", 'SA_CUSTOMER', MENU_INQUIRY);
				$app->add_lapp_function(1, _("Sales Target && Incentive In&quiry"),
					"modules/knb_sales_ext/inquiry/incentive_inquiry.php", 'SA_CUSTOMER', MENU_INQUIRY);

				$app->add_lapp_function(2, _("Incentive &Tiers"),
					"modules/knb_sales_ext/manage/incentive_tiers.php", 'SA_CUSTOMER', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("Re&place Sales Employee"),
					"modules/knb_sales_ext/manage/replace_users.php", 'SA_CUSTOMER', MENU_MAINTENANCE);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_sales_ext.sql' => array('knb_sales_targets', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
