<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Expense Booking"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/expense_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

$expense_categories = array('Travel', 'Fuel', 'Food', 'Lodging', 'Other');

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function category_list()
{
	global $expense_categories;
	return array_combine($expense_categories, $expense_categories);
}

function can_process()
{
	if (empty($_POST['sales_employee_id']))
	{
		display_error(_("Select a sales employee."));
		return false;
	}
	if (!check_num('amount', 0.01))
	{
		display_error(_("Amount must be greater than zero."));
		set_focus('amount');
		return false;
	}
	return true;
}

if (isset($_POST['SaveExpense']) && can_process() && check_csrf_token())
{
	add_expense_claim($_POST['sales_employee_id'], date2sql($_POST['expense_date']),
		$_POST['category'], input_num('amount'), $_POST['description']);
	display_notification(_('Expense claim submitted.'));
	unset($_POST);
}

if (!isset($_POST['expense_date']) || $_POST['expense_date'] == '')
	$_POST['expense_date'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
date_row(_("Expense Date").':', 'expense_date');
array_selector_row(_("Category").':', 'category', @$_POST['category'], category_list());
amount_row(_("Amount").':', 'amount');
text_row_ex(_("Description").':', 'description', 60);
end_table(1);

submit_center('SaveExpense', _("Submit Claim"));
end_form();
end_page();
