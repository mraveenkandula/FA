<?php
/*
	KNB Group Distributor Portal extension.
	Confirmed real gap against the actual TechCloud commercial agreement's
	mobile app scope: "Distributor Login" and "Stock Check (QOH, Demand,
	Ordered)". Built as responsive web pages rather than a native mobile
	app - there is no existing mobile app project in this repo to extend,
	and this reuses FA's own DB/session/security model instead of a new
	tech stack.

	The distributor-facing pages (modules/knb_distributor_portal/portal/)
	are a separate, lightweight login system - a distributor is a
	customer (debtors_master), not an FA user, so it deliberately does not
	go through FA's normal cookie/session/menu stack (includes/session.inc)
	the way every other page in this app does. Only the admin-facing
	provisioning page below is registered in FA's own menu.
*/

class hooks_knb_distributor_portal extends hooks
{
	var $module_name = 'knb_distributor_portal';

	function install_options($app)
	{
		switch ($app->id) {
			case 'orders':
				$app->add_lapp_function(2, _("Distributor &Portal Access"),
					"modules/knb_distributor_portal/manage/distributor_access.php", 'SA_CUSTOMER', MENU_MAINTENANCE);
				break;
		}
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_distributor_portal.sql' => array('debtors_master', 'portal_active', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}
