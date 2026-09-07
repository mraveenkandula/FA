<?php
$page_security = 'SA_KNB_TASK_ASSIGN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Assign Task"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/task_entry_db.inc");
include_once(__DIR__ . "/employee_db.inc");

$priorities = array('Low' => _('Low'), 'Medium' => _('Medium'), 'High' => _('High'));

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['assigned_to']))
	{
		display_error(_("Select an employee to assign the task to."));
		return false;
	}
	if (trim(@$_POST['title']) === '')
	{
		display_error(_("Enter a task title."));
		return false;
	}
	return true;
}

if (isset($_POST['AssignTask']) && can_process() && check_csrf_token())
{
	add_task($_POST['assigned_to'], @$_POST['assigned_by'] ?: null,
		$_POST['title'], $_POST['description'], $_POST['due_date'], $_POST['priority']);
	display_notification(_('Task assigned.'));
	unset($_POST);
}

if (!isset($_POST['priority']))
	$_POST['priority'] = 'Medium';

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Assigned By").':', 'assigned_by', @$_POST['assigned_by'], employee_list());
array_selector_row(_("Assign To").':', 'assigned_to', @$_POST['assigned_to'], employee_list());
text_row(_("Title").':', 'title', @$_POST['title'], 50, 200);
textarea_row(_("Description").':', 'description', null, 40, 3);
date_row(_("Due Date").':', 'due_date');
array_selector_row(_("Priority").':', 'priority', @$_POST['priority'], $priorities);
end_table(1);

submit_center('AssignTask', _("Assign Task"));
end_form();
end_page();
