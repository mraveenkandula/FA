<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Named "Update Task Status", not "My Tasks" - this page runs under a
// shared/office-staff FA web login, not a per-employee session (see the
// comment in task_entry.php: hr_employees has no link to 0_users, and
// employees authenticate to the mobile app separately). Selecting which
// employee's tasks to update here follows the exact same convention as
// leave_entry.php/expense_claim_entry.php elsewhere in this module -
// identity is asserted by page access (SA_OPEN, i.e. any FA login), not
// verified against a logged-in employee, because there is no such
// employee-level web session to verify against.
page(_($help_context = "Update Task Status"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/task_entry_db.inc");
include_once(__DIR__ . "/employee_db.inc");

function employee_list()
{
	$items = array('' => _('Select Employee'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

if (list_updated('employee_id'))
	$Ajax->activate('tasks');

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list(), array('select_submit' => true));
end_table();

if (!empty($_POST['employee_id']))
{
	$progress_id = find_submit('Progress_');
	if ($progress_id != -1 && check_csrf_token())
	{
		set_task_status($progress_id, 'In Progress');
		display_notification(_('Task marked in progress.'));
	}
	$complete_id = find_submit('Complete_');
	if ($complete_id != -1 && check_csrf_token())
	{
		set_task_status($complete_id, 'Completed', @$_POST['remarks_'.$complete_id]);
		display_notification(_('Task marked completed.'));
	}

	div_start('tasks');
	display_heading(_("Tasks"));
	$result = get_tasks($_POST['employee_id']);
	start_table(TABLESTYLE, "width='95%'");
	$th = array(_('Title'), _('Description'), _('Due Date'), _('Priority'), _('Assigned By'), _('Status'), _('Remarks'), '');
	table_header($th);
	$k = 0;
	while ($row = db_fetch($result))
	{
		$id = $row['id'];
		alt_table_row_color($k);
		label_cell(htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
		label_cell($row['due_date'] ? sql2date($row['due_date']) : '');
		label_cell(htmlspecialchars($row['priority'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars(trim($row['assigned_by_first_name'].' '.$row['assigned_by_last_name']), ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
		if ($row['status'] == 'Pending' || $row['status'] == 'In Progress')
		{
			$actions = '';
			if ($row['status'] == 'Pending')
				$actions .= "<input type='submit' name='Progress_$id' value='"._('Start')."' class='inputsubmit'> ";
			$actions .= "<input type='submit' name='Complete_$id' value='"._('Complete')."' class='inputsubmit'>";
			label_cell($actions);
		}
		else
			label_cell('');
		end_row();
	}
	end_table();
	div_end();
}

end_form();
end_page();
