<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Leave Types"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/leave_types_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen(trim($_POST['name'])) == 0)
	{
		display_error(_("The leave type name cannot be empty."));
		set_focus('name');
		return false;
	}
	if (strlen(trim($_POST['code'])) == 0)
	{
		display_error(_("The leave type code cannot be empty."));
		set_focus('code');
		return false;
	}
	if (!check_num('days_confirmed', 0) || !check_num('days_probation', 0))
	{
		display_error(_("Days allotted must be non-negative numbers."));
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_leave_type($_POST['name'], $_POST['code'], input_num('days_confirmed'), input_num('days_probation'));
	display_notification(_('New leave type has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_leave_type($selected_id, $_POST['name'], $_POST['code'], input_num('days_confirmed'), input_num('days_probation'));
	display_notification(_('Selected leave type has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete')
{
	if (key_in_foreign_table($selected_id, 'knb_leave_entries', 'leave_type_id'))
		display_error(_("Cannot delete this leave type because leave entries exist against it."));
	else
	{
		delete_leave_type($selected_id);
		display_notification(_('Selected leave type has been deleted'));
	}
	$Mode = 'RESET';
}
if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_leave_types(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='70%'");
$th = array(_('Leave Type Name'), _('Code'), _('Days (Confirmed)'), _('Days (Probation)'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["code"], ENT_QUOTES, 'UTF-8'));
	amount_cell($myrow["days_confirmed"]);
	amount_cell($myrow["days_probation"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_leave_types', 'id');
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

start_table(TABLESTYLE2);
if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$myrow = get_leave_type($selected_id);
		$_POST['name'] = $myrow["name"];
		$_POST['code'] = $myrow["code"];
		$_POST['days_confirmed'] = $myrow["days_confirmed"];
		$_POST['days_probation'] = $myrow["days_probation"];
	}
	hidden('selected_id', $selected_id);
}
text_row_ex(_("Leave Type Name").':', 'name', 40);
text_row_ex(_("Code").':', 'code', 10);
amount_row(_("Days Allotted (Confirmed Employee)").':', 'days_confirmed');
amount_row(_("Days Allotted (Probation Employee)").':', 'days_probation');
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
