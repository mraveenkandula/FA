<?php
/*
	KNB Group HRM extension.
	Adds a lightweight Employees/Departments/Designations module, built to
	replace the equivalent (paid, third-party) module in the pilot customer's
	current TechCloud ERP install.
*/

class hooks_knb_hrm extends hooks
{
	var $module_name = 'knb_hrm';

	function install_tabs($app)
	{
		$app->add_application(new knb_hrm_app());
	}

	function install_extension($check_only=true)
	{
		$updates = array(
			'knb_hrm.sql' => array('hr_departments', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}

class knb_hrm_app extends application
{
	function __construct()
	{
		parent::__construct("hr_local", _($this->help_context = "&HRM"), true);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(0, _("&Departments"),
			"modules/knb_hrm/manage/department.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(0, _("De&signations"),
			"modules/knb_hrm/manage/designation.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(0, _("&Employees"),
			"modules/knb_hrm/manage/employee.php", 'SA_OPEN', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}
