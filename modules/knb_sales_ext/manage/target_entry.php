<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Target Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/target_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['sales_employee_id']))
	{
		display_error(_("Select a sales employee."));
		return false;
	}
	if (!check_num('target_amount', 0))
	{
		display_error(_("Target amount must be a non-negative number."));
		set_focus('target_amount');
		return false;
	}
	return true;
}

if (isset($_POST['SaveTarget']) && can_process() && check_csrf_token())
{
	// this install's date_format is m/d/Y (confirmed via 0_users.date_format=0) -
	// build the first-of-month string in that same order, not d/m/Y.
	$period_month = date2sql(date('m/01/Y', strtotime(date2sql($_POST['period_month']))));
	add_sales_target($_POST['sales_employee_id'], $period_month, input_num('target_amount'), $_POST['notes']);
	display_notification(_('Sales target saved.'));
	unset($_POST);
}

if (!isset($_POST['period_month']) || $_POST['period_month'] == '')
	$_POST['period_month'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
date_row(_("Period (any date in the target month)").':', 'period_month');
amount_row(_("Target Amount").':', 'target_amount');
text_row_ex(_("Notes").':', 'notes', 60);
end_table(1);

submit_center('SaveTarget', _("Save Target"));
end_form();
end_page();
