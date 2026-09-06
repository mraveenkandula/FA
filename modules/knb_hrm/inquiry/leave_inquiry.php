<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Leave Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/leave_entry_db.inc");
include_once(__DIR__ . "/../manage/employee_db.inc");

$statuses = array('' => _('All'), 'Pending' => _('Pending'), 'Approved' => _('Approved'), 'Rejected' => _('Rejected'));

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
array_selector_row(_("Status").':', 'status', @$_POST['status'], $statuses);
end_table();
end_form();

$result = get_leave_entries(@$_POST['status'] ?: null, @$_POST['employee_id'] ?: null);
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Employee'), _('Leave Type'), _('From'), _('To'), _('Days'), _('Session'), _('Reason'), _('Status'), _('Rejection Reason'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['leave_type_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['leave_from']));
	label_cell(sql2date($row['leave_to']));
	amount_cell($row['leave_days']);
	label_cell(htmlspecialchars($row['session_type'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['rejection_reason'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
