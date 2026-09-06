<?php
/*
	KNB Group Production extension.
	Production Batch Entry drives core FrontAccounting Work Orders
	(add_work_order/release_work_order/add_work_order_issue/work_order_produce)
	so batches actually move real stock and post real WIP/inventory costing,
	instead of only logging to a disconnected table. Fat/moisture/incharge -
	fields core has no place for - are kept in knb_production_quality, keyed
	to the real workorder id.
*/

class hooks_knb_production extends hooks
{
	var $module_name = 'knb_production';

	function install_options($app)
	{
		switch ($app->id) {
			case 'manuf':
				$app->add_lapp_function(0, _("Production &Batch Entry"),
					"modules/knb_production/manage/batch_entry.php", 'SA_WORKORDERENTRY', MENU_TRANSACTION);
				$app->add_lapp_function(1, _("Production &Batch Inquiry"),
					"modules/knb_production/manage/batch_inquiry.php", 'SA_WORKORDERENTRY', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_production.sql' => array('knb_production_quality', 'workorder_id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
