<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Employees"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/employee_db.inc");
include_once(__DIR__ . "/department_db.inc");
include_once(__DIR__ . "/designation_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen($_POST['first_name']) == 0)
	{
		display_error(_("The employee's first name cannot be empty."));
		set_focus('first_name');
		return false;
	}
	return true;
}

function department_list()
{
	$items = array();
	$result = get_all_departments(true);
	while ($row = db_fetch($result))
		$items[$row['id']] = $row['name'];
	return $items;
}

function designation_list()
{
	$items = array();
	$result = get_all_designations(true);
	while ($row = db_fetch($result))
		$items[$row['id']] = $row['name'];
	return $items;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_employee($_POST['emp_code'], $_POST['first_name'], $_POST['last_name'],
		$_POST['gender'], $_POST['department_id'], $_POST['designation_id'],
		$_POST['mobile'], $_POST['email'], $_POST['dob'], $_POST['hire_date']);
	display_notification(_('New employee has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_employee($selected_id, $_POST['emp_code'], $_POST['first_name'], $_POST['last_name'],
		$_POST['gender'], $_POST['department_id'], $_POST['designation_id'],
		$_POST['mobile'], $_POST['email'], $_POST['dob'], $_POST['hire_date']);
	display_notification(_('Selected employee has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_employee($selected_id);
	display_notification(_('Selected employee has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_employees(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='80%'");

$th = array(_('Code'), _('Name'), _('Department'), _('Designation'), _('Mobile'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($myrow["emp_code"]);
	label_cell(trim($myrow["first_name"]." ".$myrow["last_name"]));
	label_cell($myrow["department_name"]);
	label_cell($myrow["designation_name"]);
	label_cell($myrow["mobile"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'hr_employees', 'id');
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

start_table(TABLESTYLE2);

if ($selected_id != -1 && $Mode == 'Edit')
{
	$myrow = get_employee($selected_id);
	$_POST['emp_code'] = $myrow["emp_code"];
	$_POST['first_name'] = $myrow["first_name"];
	$_POST['last_name'] = $myrow["last_name"];
	$_POST['gender'] = $myrow["gender"];
	$_POST['department_id'] = $myrow["department_id"];
	$_POST['designation_id'] = $myrow["designation_id"];
	$_POST['mobile'] = $myrow["mobile"];
	$_POST['email'] = $myrow["email"];
	$_POST['dob'] = $myrow["dob"];
	$_POST['hire_date'] = $myrow["hire_date"];
}
if ($selected_id != -1)
	hidden('selected_id', $selected_id);

text_row_ex(_("Employee Code").':', 'emp_code', 20);
text_row_ex(_("First Name").':', 'first_name', 40);
text_row_ex(_("Last Name").':', 'last_name', 40);
array_selector_row(_("Gender").':', 'gender', @$_POST['gender'],
	array('Male' => _('Male'), 'Female' => _('Female'), 'Other' => _('Other')));
array_selector_row(_("Department").':', 'department_id', @$_POST['department_id'], department_list());
array_selector_row(_("Designation").':', 'designation_id', @$_POST['designation_id'], designation_list());
text_row_ex(_("Mobile").':', 'mobile', 20);
text_row_ex(_("E-mail").':', 'email', 40);
date_row(_("Date of Birth").':', 'dob');
date_row(_("Hire Date").':', 'hire_date');

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
