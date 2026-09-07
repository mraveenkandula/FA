<?php
$page_security = 'SA_KNB_PAYROLL_MANAGE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Employee Loans"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/employee_loan_db.inc");
include_once($path_to_root . "/modules/knb_hrm/inquiry/payroll_records_db.inc");
include_once(__DIR__ . "/employee_db.inc");

// This is a record-keeping master, not a repayment-processing engine -
// paid_amount/pending_amount are entered directly here, matching the
// "history, not a workflow" scope note already established for payroll in
// inquiry/payroll_records_db.inc. Computing installment schedules or
// auto-updating pending_amount from postings is out of scope until the
// accountant defines the actual repayment rules.

simple_page_mode(true);

function employee_list()
{
	$items = array('' => _('Select Employee'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['employee_id']))
	{
		display_error(_("Select an employee."));
		return false;
	}
	if (!check_num('loan_amount', 0))
	{
		display_error(_("The loan amount must be a non-negative number."));
		set_focus('loan_amount');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_employee_loan($_POST['employee_id'], input_num('loan_amount'), $_POST['loan_type'], $_POST['payment_type'],
		input_num('installment_amount'), input_num('installment_month'), $_POST['loan_start_date'],
		$_POST['loan_end_date'], $_POST['status'], input_num('paid_amount'), input_num('pending_amount'),
		$_POST['remarks']);
	display_notification(_('New employee loan has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_employee_loan($selected_id, $_POST['employee_id'], input_num('loan_amount'), $_POST['loan_type'],
		$_POST['payment_type'], input_num('installment_amount'), input_num('installment_month'),
		$_POST['loan_start_date'], $_POST['loan_end_date'], $_POST['status'], input_num('paid_amount'),
		input_num('pending_amount'), $_POST['remarks']);
	display_notification(_('Selected employee loan has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_employee_loan($selected_id);
	display_notification(_('Selected employee loan has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav_filter = get_post('filter_employee_id');
	unset($_POST);
	$_POST['filter_employee_id'] = $sav_filter;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Search By Employee").':', 'filter_employee_id', @$_POST['filter_employee_id'], employee_list(), array('select_submit' => true));
end_table();

$result = get_employee_loans(@$_POST['filter_employee_id'] ?: null);

start_table(TABLESTYLE, "width='95%'");
$th = array(_('Employee'), _('Loan Amount'), _('Loan Type'), _('Installment'), _('Status'), _('Paid'), _('Pending'), '', '');
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($myrow['first_name'].' '.$myrow['last_name']), ENT_QUOTES, 'UTF-8'));
	amount_cell($myrow["loan_amount"]);
	label_cell(htmlspecialchars($myrow["loan_type"], ENT_QUOTES, 'UTF-8'));
	amount_cell($myrow["installment_amount"]);
	label_cell(htmlspecialchars($myrow["status"], ENT_QUOTES, 'UTF-8'));
	amount_cell($myrow["paid_amount"]);
	amount_cell($myrow["pending_amount"]);
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
end_table();

start_table(TABLESTYLE2);

if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$myrow = get_employee_loan($selected_id);
		$_POST['employee_id'] = $myrow["employee_id"];
		$_POST['loan_amount'] = $myrow["loan_amount"];
		$_POST['loan_type'] = $myrow["loan_type"];
		$_POST['payment_type'] = $myrow["payment_type"];
		$_POST['installment_amount'] = $myrow["installment_amount"];
		$_POST['installment_month'] = $myrow["installment_month"];
		$_POST['loan_start_date'] = $myrow["loan_start_date"] ? sql2date($myrow["loan_start_date"]) : '';
		$_POST['loan_end_date'] = $myrow["loan_end_date"] ? sql2date($myrow["loan_end_date"]) : '';
		$_POST['status'] = $myrow["status"];
		$_POST['paid_amount'] = $myrow["paid_amount"];
		$_POST['pending_amount'] = $myrow["pending_amount"];
		$_POST['remarks'] = $myrow["remarks"];
	}
	else
		$_POST['status'] = 'Active';
	hidden('selected_id', $selected_id);
}
else
	$_POST['status'] = @$_POST['status'] ?: 'Active';

array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list());
amount_row(_("Loan Amount").':', 'loan_amount');
text_row_ex(_("Loan Type").':', 'loan_type', 40);
text_row_ex(_("Payment Type").':', 'payment_type', 40);
amount_row(_("Installment Amount").':', 'installment_amount');
text_row_ex(_("Installment Months").':', 'installment_month', 10);
date_row(_("Loan Start Date").':', 'loan_start_date');
date_row(_("Loan End Date").':', 'loan_end_date');
text_row_ex(_("Status").':', 'status', 20);
amount_row(_("Paid Amount").':', 'paid_amount');
amount_row(_("Pending Amount").':', 'pending_amount');
text_row_ex(_("Remarks").':', 'remarks', 40);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
