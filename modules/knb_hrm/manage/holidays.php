<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Holidays"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/holidays_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen(@$_POST['name']) == 0)
	{
		display_error(_("The holiday name cannot be empty."));
		set_focus('name');
		return false;
	}
	if (strlen(@$_POST['holiday_date']) == 0)
	{
		display_error(_("The holiday date cannot be empty."));
		set_focus('holiday_date');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_holiday($_POST['name'], $_POST['holiday_date']);
	display_notification(_('New holiday has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_holiday($selected_id, $_POST['name'], $_POST['holiday_date']);
	display_notification(_('Selected holiday has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_holiday($selected_id);
	display_notification(_('Selected holiday has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_holidays(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='40%'");

$th = array(_('Holiday Name'), _('Holiday Date'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($myrow["name"]);
	label_cell(sql2date($myrow["holiday_date"]));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_holidays', 'id');
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
		$myrow = get_holiday($selected_id);
		$_POST['name'] = $myrow["name"];
		$_POST['holiday_date'] = sql2date($myrow["holiday_date"]);
	}
	hidden('selected_id', $selected_id);
}

text_row_ex(_("Holiday Name").':', 'name', 40);
date_row(_("Holiday Date").':', 'holiday_date');

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
