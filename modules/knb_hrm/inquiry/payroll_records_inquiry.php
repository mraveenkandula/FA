<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Payroll Records Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/payroll_records_db.inc");
include_once(__DIR__ . "/../manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('All Employees'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list());
end_table();
end_form();

$employee_id = @$_POST['employee_id'] ?: null;

display_note(_("Payroll Runs"), 0, 1);
$result = get_payroll_runs($employee_id);
start_table(TABLESTYLE, "width='95%'");
table_header(array(_('Employee'), _('Period'), _('Paid Days'), _('Basic Pay'), _('HRA'),
	_('PF'), _('ESI'), _('Prof. Tax'), _('Loan Ded.'), _('Incentives'), _('Payable'), _('Approved')));
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['period_from']).' - '.sql2date($row['period_to']));
	label_cell($row['paid_days']);
	amount_cell($row['basic_pay']);
	amount_cell($row['hra']);
	amount_cell($row['epf']);
	amount_cell($row['esi']);
	amount_cell($row['professional_tax']);
	amount_cell($row['loan_deduction']);
	amount_cell($row['incentives']);
	amount_cell($row['payable_amount']);
	label_cell($row['approved'] ? _('Yes') : _('No'));
	end_row();
}
end_table(1);

display_note(_("Salary Structure"), 1, 1);
$result = get_salary_structures();
start_table(TABLESTYLE, "width='70%'");
table_header(array(_('Designation'), _('Pay Rule'), _('Pay Amount'), _('Basic?'), _('Effective Date')));
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['designation_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['pay_rule'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['pay_amount']);
	label_cell($row['is_basic'] ? _('Yes') : _('No'));
	label_cell(sql2date($row['effective_date']));
	end_row();
}
end_table(1);

display_note(_("Professional Tax Slabs"), 1, 1);
$result = get_professional_tax_slabs();
start_table(TABLESTYLE, "width='40%'");
table_header(array(_('Slab Type'), _('Basic Amount')));
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($row['slab_type']);
	amount_cell($row['basic_amount']);
	end_row();
}
end_table(1);

display_note(_("Employee Incentives"), 1, 1);
$result = get_employee_incentives($employee_id);
start_table(TABLESTYLE, "width='70%'");
table_header(array(_('Employee'), _('Brand'), _('Incentive Amount')));
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['brand'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['incentive_amount']);
	end_row();
}
end_table(1);

display_note(_("Employee Loans"), 1, 1);
$result = get_employee_loans($employee_id);
start_table(TABLESTYLE, "width='90%'");
table_header(array(_('Employee'), _('Loan Type'), _('Amount'), _('Paid'), _('Pending'), _('Status'), _('Start Date')));
$k = 0;
$any = false;
while ($row = db_fetch($result))
{
	$any = true;
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['loan_type'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['loan_amount']);
	amount_cell($row['paid_amount']);
	amount_cell($row['pending_amount']);
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['loan_start_date']));
	end_row();
}
end_table(1);
if (!$any)
	display_note(_("No employee loans on record."), 0, 1);

end_page();
