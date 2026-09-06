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
			'knb_hrm_expense_claims.sql' => array('hr_expense_claims', 'id', 'ANY'),
			'knb_hrm_leave.sql' => array('knb_leave_types', 'id', 'ANY'),
			'knb_hrm_payroll.sql' => array('knb_payroll', 'id', 'ANY'),
		);
		return $this->update_databases(-1, $updates, $check_only);
	}

	function install_access()
	{
		$security_areas['SA_KNB_EXPENSE_APPROVE'] = array(1<<8|1, _("Approve employee expense claims"));
		$security_areas['SA_KNB_LEAVE_APPROVE'] = array(1<<8|2, _("Approve employee leave requests"));
		// Payroll/HR records carry salary figures, PAN, Aadhaar and bank
		// account numbers - SA_OPEN (any logged-in user) is fine for
		// attendance/leave but not for this, so it gets its own area.
		$security_areas['SA_KNB_PAYROLL_VIEW'] = array(1<<8|3, _("View payroll and HR records (salary, PAN/Aadhaar, bank details)"));
		$security_sections = array(1<<8 => _("KNB Group HRM"));
		return array($security_areas, $security_sections);
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
		$this->add_lapp_function(0, _("Employee &Expense Claim"),
			"modules/knb_hrm/manage/expense_claim_entry.php", 'SA_PAYMENT', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Expense Claim A&pproval"),
			"modules/knb_hrm/manage/expense_claim_approval.php", 'SA_KNB_EXPENSE_APPROVE', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("&Leave Entry"),
			"modules/knb_hrm/manage/leave_entry.php", 'SA_OPEN', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Leave A&pproval"),
			"modules/knb_hrm/manage/leave_approval.php", 'SA_KNB_LEAVE_APPROVE', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("&Attendance Inquiry"),
			"modules/knb_hrm/inquiry/attendance_inquiry.php", 'SA_OPEN', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Geo Trac&king Inquiry"),
			"modules/knb_hrm/inquiry/geo_tracking_inquiry.php", 'SA_OPEN', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Lea&ve Inquiry"),
			"modules/knb_hrm/inquiry/leave_inquiry.php", 'SA_OPEN', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Pa&yroll Records Inquiry"),
			"modules/knb_hrm/inquiry/payroll_records_inquiry.php", 'SA_KNB_PAYROLL_VIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("HR &Records Inquiry"),
			"modules/knb_hrm/inquiry/hr_records_inquiry.php", 'SA_KNB_PAYROLL_VIEW', MENU_INQUIRY);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Departments"),
			"modules/knb_hrm/manage/department.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("De&signations"),
			"modules/knb_hrm/manage/designation.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Employees"),
			"modules/knb_hrm/manage/employee.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Lea&ve Types"),
			"modules/knb_hrm/manage/leave_types.php", 'SA_OPEN', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}
