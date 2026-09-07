<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Shift Types"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/shift_types_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen(@$_POST['code']) == 0)
	{
		display_error(_("The shift type code cannot be empty."));
		set_focus('code');
		return false;
	}
	if (strlen(@$_POST['name']) == 0)
	{
		display_error(_("The shift type name cannot be empty."));
		set_focus('name');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_shift_type($_POST['code'], $_POST['name']);
	display_notification(_('New shift type has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_shift_type($selected_id, $_POST['code'], $_POST['name']);
	display_notification(_('Selected shift type has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_shift_type($selected_id);
	display_notification(_('Selected shift type has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_shift_types(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='30%'");

$th = array(_('Shift Type Code'), _('Shift Type Name'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["code"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_shift_types', 'id');
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
		$myrow = get_shift_type($selected_id);
		$_POST['code'] = $myrow["code"];
		$_POST['name'] = $myrow["name"];
	}
	hidden('selected_id', $selected_id);
}

text_row_ex(_("Shift Type Code").':', 'code', 10);
text_row_ex(_("Shift Type Name").':', 'name', 40);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
