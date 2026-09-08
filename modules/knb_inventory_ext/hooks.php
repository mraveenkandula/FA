<?php
/*
	KNB Group Inventory Extensions.
	Adds item sub-category/brand/capacity classification (attached to
	existing FrontAccounting items without touching the core item form) and
	an approval step ahead of stock location transfers, matching TechCloud's
	Item Sub Categories / Item Brand Master / Item Capacity Master and
	Stock Location Approved screens.
*/

class hooks_knb_inventory_ext extends hooks
{
	var $module_name = 'knb_inventory_ext';

	function install_options($app)
	{
		switch ($app->id) {
			case 'stock':
				$app->add_lapp_function(2, _("Item Sub &Categories"),
					"modules/knb_inventory_ext/manage/sub_categories.php", 'SA_ITEM', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("Item &Brands"),
					"modules/knb_inventory_ext/manage/brands.php", 'SA_ITEM', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("Item Ca&pacities"),
					"modules/knb_inventory_ext/manage/capacities.php", 'SA_ITEM', MENU_MAINTENANCE);
				$app->add_lapp_function(2, _("Item &Classification Mapping"),
					"modules/knb_inventory_ext/manage/item_mapping.php", 'SA_ITEM', MENU_MAINTENANCE);
				$app->add_rapp_function(0, _("Stock Transfer &Request"),
					"modules/knb_inventory_ext/manage/transfer_request.php", 'SA_LOCATIONTRANSFER', MENU_TRANSACTION);
				$app->add_rapp_function(0, _("Stock Location &Approved"),
					"modules/knb_inventory_ext/manage/transfer_approval.php", 'SA_KNB_TRANSFER_APPROVE', MENU_TRANSACTION);
				$app->add_rapp_function(0, _("Bulk &Price Import"),
					"modules/knb_inventory_ext/manage/price_import.php", 'SA_KNB_PRICE_IMPORT', MENU_TRANSACTION);
				break;
		}
	}

	function install_access()
	{
		$security_areas['SA_KNB_TRANSFER_APPROVE'] = array(1<<8|1, _("Approve stock location transfers"));
		// Bulk price import overwrites 0_prices across the whole catalog in
		// one action - a much bigger blast radius than the single-item,
		// single-row edits core FA's own SA_SALESPRICE area gates, so it
		// gets its own dedicated area rather than reusing SA_SALESPRICE or
		// SA_ITEM.
		$security_areas['SA_KNB_PRICE_IMPORT'] = array(1<<8|2, _("Bulk import item sales prices from CSV"));
		$security_sections = array(1<<8 => _("KNB Inventory Extensions"));
		return array($security_areas, $security_sections);
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_inventory_ext.sql' => array('item_sub_categories', 'id', 'ANY'),
			'knb_price_import.sql' => array('knb_price_import_log', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
