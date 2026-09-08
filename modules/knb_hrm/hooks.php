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
			'knb_hrm_letters.sql' => array('knb_letter_templates', 'id', 'ANY'),
			'knb_hrm_tasks.sql' => array('knb_employee_tasks', 'id', 'ANY'),
			'knb_hrm_holidays_shifts_assets.sql' => array('knb_shift_types', 'id', 'ANY'),
			'knb_hrm_mobile_credentials.sql' => array('hr_employees', 'mobile_username', 'ANY'),
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
		// Distinct from SA_KNB_PAYROLL_VIEW above: that area is read-only
		// (the inquiry pages), this one gates add/edit/delete on the
		// salary-sensitive maintenance pages (Employee Loans, Professional
		// Tax Slabs) - a user who can view payroll data shouldn't
		// automatically also be able to change it.
		$security_areas['SA_KNB_PAYROLL_MANAGE'] = array(1<<8|6, _("Add, edit or delete payroll-related records (loans, professional tax slabs)"));
		// Letter templates are official company correspondence (offer/
		// experience/appointment letters) and Generate Letter produces a
		// signed-looking document for an arbitrary employee - SA_OPEN
		// (any logged-in user) is too broad for either, unlike the plain
		// department/designation/leave-type maintenance elsewhere in this
		// module.
		$security_areas['SA_KNB_LETTER_MANAGE'] = array(1<<8|4, _("Manage HR letter templates and generate employee letters"));
		// Assigning a task to (potentially any) employee is a supervisory
		// action, unlike updating the status of a task already assigned to
		// you - that stays SA_OPEN, matching how leave/expense entry work.
		$security_areas['SA_KNB_TASK_ASSIGN'] = array(1<<8|5, _("Assign tasks to employees"));
		// Setting an employee's mobile app login is a credential-issuance
		// action, not a data-entry one - closer in kind to
		// SA_KNB_LETTER_MANAGE than to the plain SA_OPEN maintenance
		// pages in this module, so it gets its own dedicated area rather
		// than reusing SA_OPEN or SA_KNB_PAYROLL_MANAGE.
		$security_areas['SA_KNB_MOBILE_CREDENTIALS'] = array(1<<8|7, _("Set or reset employee mobile app login credentials"));
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
		$this->add_lapp_function(0, _("Assign &Task"),
			"modules/knb_hrm/manage/task_entry.php", 'SA_KNB_TASK_ASSIGN', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("&Update Task Status"),
			"modules/knb_hrm/manage/task_update.php", 'SA_OPEN', MENU_TRANSACTION);

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
		$this->add_lapp_function(1, _("&Generate Letter"),
			"modules/knb_hrm/inquiry/generate_letter.php", 'SA_KNB_LETTER_MANAGE', MENU_INQUIRY);
		$this->add_lapp_function(1, _("&Task Management Inquiry"),
			"modules/knb_hrm/inquiry/task_inquiry.php", 'SA_OPEN', MENU_INQUIRY);

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Departments"),
			"modules/knb_hrm/manage/department.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("De&signations"),
			"modules/knb_hrm/manage/designation.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Employees"),
			"modules/knb_hrm/manage/employee.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Mobile App Credentials"),
			"modules/knb_hrm/manage/mobile_credentials.php", 'SA_KNB_MOBILE_CREDENTIALS', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Lea&ve Types"),
			"modules/knb_hrm/manage/leave_types.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Letter &Templates"),
			"modules/knb_hrm/manage/letter_templates.php", 'SA_KNB_LETTER_MANAGE', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Holidays"),
			"modules/knb_hrm/manage/holidays.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Shift Types"),
			"modules/knb_hrm/manage/shift_types.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Asset Allocations"),
			"modules/knb_hrm/manage/asset_allocation.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Notice Periods"),
			"modules/knb_hrm/manage/notice_period.php", 'SA_OPEN', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Employee &Loans"),
			"modules/knb_hrm/manage/employee_loan.php", 'SA_KNB_PAYROLL_MANAGE', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Professional Tax Slabs"),
			"modules/knb_hrm/manage/professional_tax.php", 'SA_KNB_PAYROLL_MANAGE', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}
