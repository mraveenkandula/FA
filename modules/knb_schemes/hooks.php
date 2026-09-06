<?php
/*
	KNB Group Schemes extension.
	Trade scheme engine - confirmed against TechCloud's live Sales menu and
	its actual Schemes Entry screen: Brand + Person Type + State + Territory
	scope, a date range, and up to 8 volume slabs (target qty in cases +
	free-text reward). No core FrontAccounting equivalent. Eligibility is a
	real-time inquiry against actual invoiced quantity, not a separate
	stored approval workflow - TechCloud itself has no approval step here,
	so this doesn't invent one either.
*/

class hooks_knb_schemes extends hooks
{
	var $module_name = 'knb_schemes';

	function install_options($app)
	{
		switch ($app->id) {
			case 'orders':
				$app->add_lapp_function(0, _("Sch&emes Entry"),
					"modules/knb_schemes/manage/schemes.php", 'SA_CUSTOMER', MENU_TRANSACTION);

				$app->add_lapp_function(1, _("Schemes In&quiry"),
					"modules/knb_schemes/inquiry/schemes_inquiry.php", 'SA_CUSTOMER', MENU_INQUIRY);
				$app->add_lapp_function(1, _("Schemes Elig&ibility Inquiry"),
					"modules/knb_schemes/inquiry/schemes_eligibility_inquiry.php", 'SA_CUSTOMER', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_schemes.sql' => array('knb_schemes', 'id', 'ANY'),
			'knb_schemes_promotion_types.sql' => array('knb_schemes', 'promotion_type', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
