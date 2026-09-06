<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Leave Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/leave_entry_db.inc");
include_once(__DIR__ . "/leave_types_db.inc");
include_once(__DIR__ . "/employee_db.inc");

$session_types = array('Full Day' => _('Full Day'), 'First Half' => _('First Half'), 'Second Half' => _('Second Half'));

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}
function leave_type_list()
{
	$items = array();
	$result = get_all_leave_types(false);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'].' ('.$row['code'].')';
	return $items;
}

function can_process()
{
	if (empty($_POST['employee_id']) || empty($_POST['leave_type_id']))
	{
		display_error(_("Select an employee and leave type."));
		return false;
	}
	if (!check_num('leave_days', 0.5))
	{
		display_error(_("Leave days must be a positive number."));
		return false;
	}
	return true;
}

if (isset($_POST['ApplyLeave']) && can_process() && check_csrf_token())
{
	add_leave_entry($_POST['employee_id'], $_POST['leave_type_id'], $_POST['leave_from'], $_POST['leave_to'],
		input_num('leave_days'), $_POST['session_type'], $_POST['reason']);
	display_notification(_('Leave applied. Awaiting approval.'));
	unset($_POST);
}

if (!isset($_POST['leave_from']) || $_POST['leave_from'] == '')
{
	$_POST['leave_from'] = Today();
	$_POST['leave_to'] = Today();
	$_POST['leave_days'] = 1;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list(), array('select_submit' => true));
array_selector_row(_("Leave Type").':', 'leave_type_id', @$_POST['leave_type_id'], leave_type_list(), array('select_submit' => true));

if (!empty($_POST['employee_id']) && !empty($_POST['leave_type_id']))
{
	$balance = get_leave_balance($_POST['employee_id'], $_POST['leave_type_id'], date('Y'));
	label_row(_("Allotted / Utilised / Remaining").':',
		number_format($balance['allotted'], 1).' / '.number_format($balance['used'], 1).' / '.number_format($balance['remaining'], 1));
}

array_selector_row(_("Session Type").':', 'session_type', @$_POST['session_type'], $session_types);
date_row(_("Leave From").':', 'leave_from');
date_row(_("Leave To").':', 'leave_to');
amount_row(_("Leave Days").':', 'leave_days');
textarea_row(_("Reason").':', 'reason', null, 40, 2);
end_table(1);

submit_center('ApplyLeave', _("Apply Leave"));
end_form();
end_page();
