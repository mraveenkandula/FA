<?php
/*
	KNB Group Stores Extensions.
	Stock Verification (physical/cycle count reconciliation) - confirmed
	against TechCloud's live Stores menu, no equivalent screen exists in
	core FrontAccounting. On finalize it posts a real Inventory Adjustment
	via core's own add_stock_adjustment() for whatever varies, so counted
	stock is never just logged - it's actually corrected.
*/

class hooks_knb_stores_ext extends hooks
{
	var $module_name = 'knb_stores_ext';

	function install_options($app)
	{
		switch ($app->id) {
			case 'stock':
				$app->add_lapp_function(0, _("Stock &Verification"),
					"modules/knb_stores_ext/manage/stock_verification.php", 'SA_INVENTORYADJUSTMENT', MENU_TRANSACTION);
				$app->add_lapp_function(1, _("Stock Verification In&quiry"),
					"modules/knb_stores_ext/inquiry/stock_verification_inquiry.php", 'SA_INVENTORYADJUSTMENT', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_stores_ext.sql' => array('knb_stock_verification', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
