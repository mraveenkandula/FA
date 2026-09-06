<?php
$page_security = 'SA_PAYMENT';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Employee Expense Claim"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/expense_claim_db.inc");
include_once(__DIR__ . "/employee_db.inc");

$categories = array('Travel' => _('Travel'), 'Fuel' => _('Fuel'), 'Food' => _('Food'), 'Accommodation' => _('Accommodation'), 'Other' => _('Other'));

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['employee_id']))
	{
		display_error(_("Please select an employee."));
		return false;
	}
	if (!check_num('amount', 0.01))
	{
		display_error(_("Amount must be a positive number."));
		set_focus('amount');
		return false;
	}
	return true;
}

if (isset($_POST['AddClaim']) && can_process() && check_csrf_token())
{
	add_expense_claim($_POST['employee_id'], date2sql($_POST['claim_date']), $_POST['category'],
		input_num('amount'), $_POST['description']);
	display_notification(_('Expense claim submitted for approval.'));
	unset($_POST);
}

if (!isset($_POST['claim_date']) || $_POST['claim_date'] == '')
	$_POST['claim_date'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list());
date_row(_("Claim Date").':', 'claim_date');
array_selector_row(_("Category").':', 'category', @$_POST['category'], $categories);
amount_row(_("Amount").':', 'amount');
textarea_row(_("Description").':', 'description', null, 40, 3);
end_table(1);

submit_center('AddClaim', _("Submit Claim"));
end_form();
end_page();
