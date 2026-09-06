<?php
/*
	KNB Group Banking extension.
	Adds bank statement import + auto-reconciliation matching to the existing
	Banking and General Ledger app, so reconciling no longer means manually
	ticking every line against a paper/PDF statement.
*/

class hooks_knb_banking extends hooks
{
	var $module_name = 'knb_banking';

	function install_options($app)
	{
		global $path_to_root;

		switch ($app->id) {
			case 'GL':
				$app->add_rapp_function(0, _("Import Bank &Statement"),
					"modules/knb_banking/manage/import_statement.php", 'SA_OPEN', MENU_TRANSACTION);
				$app->add_rapp_function(1, _("Bank Statement &Matching"),
					"modules/knb_banking/inquiry/statement_lines.php", 'SA_OPEN', MENU_INQUIRY);
				$app->add_rapp_function(1, _("&GST Summary Report"),
					"modules/knb_banking/inquiry/gst_summary_report.php", 'SA_GLANALYTIC', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_banking.sql' => array('bank_statement_lines', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
