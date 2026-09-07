<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Notice Periods"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/notice_period_db.inc");
include_once(__DIR__ . "/employee_db.inc");

simple_page_mode(true);

// employee_id is nullable: a row with no employee is a company-wide notice
// period policy (confirmed by the one pre-existing row, which has
// employee_id NULL), distinct from a notice period assigned to a specific
// employee.
function employee_list()
{
	$items = array('' => _('-- Company-wide (all employees) --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (!check_num('notice_days', 0))
	{
		display_error(_("Notice days must be a non-negative number."));
		set_focus('notice_days');
		return false;
	}
	if (strlen(@$_POST['start_date']) == 0)
	{
		display_error(_("The start date cannot be empty."));
		set_focus('start_date');
		return false;
	}
	if (strlen(@$_POST['end_date']) == 0)
	{
		display_error(_("The end date cannot be empty."));
		set_focus('end_date');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_notice_period(@$_POST['employee_id'], input_num('notice_days'), $_POST['start_date'], $_POST['end_date']);
	display_notification(_('New notice period has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_notice_period($selected_id, @$_POST['employee_id'], input_num('notice_days'), $_POST['start_date'], $_POST['end_date']);
	display_notification(_('Selected notice period has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_notice_period($selected_id);
	display_notification(_('Selected notice period has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	unset($_POST);
}

$result = get_all_notice_periods();

start_table(TABLESTYLE, "width='70%'");
$th = array(_('Employee'), _('Notice Days'), _('Start Date'), _('End Date'), '', '');
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($myrow['employee_id'] ? htmlspecialchars(trim($myrow['first_name'].' '.$myrow['last_name']), ENT_QUOTES, 'UTF-8') : _('Company-wide'));
	label_cell($myrow["notice_days"]);
	label_cell($myrow["start_date"] ? sql2date($myrow["start_date"]) : '');
	label_cell($myrow["end_date"] ? sql2date($myrow["end_date"]) : '');
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
end_table();

start_form();
start_table(TABLESTYLE2);

if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$myrow = get_notice_period($selected_id);
		$_POST['employee_id'] = $myrow["employee_id"];
		$_POST['notice_days'] = $myrow["notice_days"];
		$_POST['start_date'] = $myrow["start_date"] ? sql2date($myrow["start_date"]) : '';
		$_POST['end_date'] = $myrow["end_date"] ? sql2date($myrow["end_date"]) : '';
	}
	hidden('selected_id', $selected_id);
}

array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list());
text_row_ex(_("Notice Days").':', 'notice_days', 10);
date_row(_("Start Date").':', 'start_date');
date_row(_("End Date").':', 'end_date');

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
