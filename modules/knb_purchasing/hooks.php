<?php
/*
	KNB Group Purchasing extension.
	Adds a purchase-indent (requisition -> approval -> PO) workflow ahead of
	FrontAccounting's own PO entry, matching TechCloud's Purchase Indent
	Entry/Approval/Inquiry screens.
*/

class hooks_knb_purchasing extends hooks
{
	var $module_name = 'knb_purchasing';

	function install_options($app)
	{
		switch ($app->id) {
			case 'AP':
				$app->add_lapp_function(0, _("Purchase &Indent Entry"),
					"modules/knb_purchasing/manage/indent_entry.php", 'SA_PURCHASEORDER', MENU_TRANSACTION);
				$app->add_lapp_function(0, _("Purchase Indent &Approval"),
					"modules/knb_purchasing/manage/indent_approval.php", 'SA_PURCHASEORDER', MENU_TRANSACTION);
				$app->add_lapp_function(1, _("Purchase &Indent Inquiry"),
					"modules/knb_purchasing/manage/indent_inquiry.php", 'SA_PURCHASEORDER', MENU_INQUIRY);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_purchasing.sql' => array('purchase_indents', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
