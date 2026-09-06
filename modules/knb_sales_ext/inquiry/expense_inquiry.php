<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Expense Claims Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/expense_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('All Employees'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function status_list()
{
	return array('' => _('All Statuses'), '0' => _('Pending'), '1' => _('Approved'), '2' => _('Rejected'));
}

function status_label($status)
{
	switch ((int)$status)
	{
		case 1: return _('Approved');
		case 2: return _('Rejected');
		default: return _('Pending');
	}
}

// approve/reject buttons are rendered inside the same form as the filter
// fields below - a single start_form()/end_form() pair, since FA's
// check_csrf_token() validates against one $_SESSION['csrf_token'] and a
// second form on the same page would silently invalidate the first one's
// token (hit and fixed this exact bug on the Distributor Portal Access page).
$approve_id = find_submit('Approve_');
$reject_id = find_submit('Reject_');
if (($approve_id != -1 || $reject_id != -1) && check_csrf_token())
{
	$approver = $_SESSION["wa_current_user"]->username;
	if ($approve_id != -1)
		set_expense_claim_status($approve_id, 1, $approver);
	if ($reject_id != -1)
		set_expense_claim_status($reject_id, 2, $approver);
}

if (!isset($_POST['date_from']) || $_POST['date_from'] == '')
	$_POST['date_from'] = date2sql(date('m/01/Y', strtotime(date2sql(Today()))));
if (!isset($_POST['date_to']) || $_POST['date_to'] == '')
	$_POST['date_to'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
array_selector_row(_("Status").':', 'status', @$_POST['status'], status_list());
date_row(_("From").':', 'date_from');
date_row(_("To").':', 'date_to');
end_table();
submit_center('Filter', _("Filter"), true, '', 'default');

$result = get_expense_claims(@$_POST['sales_employee_id'] ?: null, isset($_POST['status']) ? $_POST['status'] : null,
	date2sql($_POST['date_from']), date2sql($_POST['date_to']));
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Date'), _('Sales Employee'), _('Category'), _('Amount'), _('Description'), _('Status'), _('Approved By'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['expense_date']));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['amount']);
	label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
	label_cell(status_label($row['status']));
	label_cell($row['approved_by'] ? htmlspecialchars($row['approved_by'], ENT_QUOTES, 'UTF-8').' ('.sql2date($row['approved_date']).')' : '');
	echo "<td>";
	if ($row['status'] == 0)
	{
		echo "<button type='submit' name='Approve_".$row['id']."' value='1'>"._("Approve")."</button> ";
		echo "<button type='submit' name='Reject_".$row['id']."' value='1'>"._("Reject")."</button>";
	}
	echo "</td>";
	end_row();
}
end_table(1);
end_form();

end_page();
