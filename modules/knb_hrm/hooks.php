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
			'knb_hrm_attendance.sql' => array('hr_attendance', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}
}

class knb_hrm_app extends application
{
	function __construct()
	{
		parent::__construct("hr_local", _($this->help_context = "&HRM"), true);

		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("&Daily Attendance Entry"),
			"modules/knb_hrm/manage/attendance_entry.php", 'SA_OPEN', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("&Geo Tracking"),
			"modules/knb_hrm/manage/geo_tracking_entry.php", 'SA_OPEN', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("&Attendance Inquiry"),
			"modules/knb_hrm/inquiry/attendance_inquiry.php", 'SA_OPEN', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Geo Trac&king Inquiry"),
			"modules/knb_hrm/inquiry/geo_tracking_inquiry.php", 'SA_OPEN', MENU_INQUIRY);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Departments"),
			"modules/knb_hrm/manage/department.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("De&signations"),
			"modules/knb_hrm/manage/designation.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Employees"),
			"modules/knb_hrm/manage/employee.php", 'SA_OPEN', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}
