<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Task Management Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/task_entry_db.inc");
include_once(__DIR__ . "/../manage/employee_db.inc");

$statuses = array('' => _('All'), 'Pending' => _('Pending'), 'In Progress' => _('In Progress'),
	'Completed' => _('Completed'), 'Cancelled' => _('Cancelled'));

function employee_list()
{
	$items = array('' => _('All Employees'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Assigned To").':', 'employee_id', @$_POST['employee_id'], employee_list());
array_selector_row(_("Status").':', 'status', @$_POST['status'], $statuses);
end_table();
end_form();

$result = get_tasks(@$_POST['employee_id'] ?: null, @$_POST['status'] ?: null);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Assigned To'), _('Assigned By'), _('Title'), _('Description'), _('Due Date'), _('Priority'), _('Status'), _('Remarks'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['assigned_by_first_name'].' '.$row['assigned_by_last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['due_date'] ? sql2date($row['due_date']) : '');
	label_cell(htmlspecialchars($row['priority'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
