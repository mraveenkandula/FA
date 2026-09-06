<?php
/*
	KNB Group Production extension.
	Multi-stage production batch tracking (raw material -> semi-finished ->
	finished goods) with yield % and quality params - the real dairy-specific
	gap. TechCloud's FG/SFG work-order split tracks two production stages but
	never records yield or quality; this fills that in.
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
			'knb_production.sql' => array('production_batches', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
