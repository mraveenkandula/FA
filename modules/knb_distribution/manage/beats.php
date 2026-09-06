<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Beats"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/beats_db.inc");
include_once(__DIR__ . "/towns_db.inc");

simple_page_mode(true);

function town_list()
{
	$items = array();
	$result = get_all_towns(true);
	while ($row = db_fetch($result))
		$items[$row['id']] = $row['name'];
	return $items;
}

function can_process()
{
	if (strlen($_POST['name']) == 0)
	{
		display_error(_("The beat name cannot be empty."));
		set_focus('name');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_beat($_POST['name'], $_POST['town_id']);
	display_notification(_('New beat has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_beat($selected_id, $_POST['name'], $_POST['town_id']);
	display_notification(_('Selected beat has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete')
{
	if (key_in_foreign_table($selected_id, 'customer_distribution', 'beat_id'))
		display_error(_("Cannot delete this beat because customers are mapped to it."));
	else
	{
		delete_beat($selected_id);
		display_notification(_('Selected beat has been deleted'));
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

$result = get_all_beats(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='50%'");
$th = array(_('Beat Name'), _('Town'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["town_name"], ENT_QUOTES, 'UTF-8'));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_beats', 'id');
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
		$myrow = get_beat($selected_id);
		$_POST['name'] = $myrow["name"];
		$_POST['town_id'] = $myrow["town_id"];
	}
	hidden('selected_id', $selected_id);
}
text_row_ex(_("Beat Name").':', 'name', 40);
array_selector_row(_("Town").':', 'town_id', @$_POST['town_id'], town_list());
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
