<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Towns"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/towns_db.inc");
include_once(__DIR__ . "/territories_db.inc");

simple_page_mode(true);

function territory_list()
{
	$items = array();
	$result = get_all_territories(true);
	while ($row = db_fetch($result))
		$items[$row['id']] = $row['name'];
	return $items;
}

function can_process()
{
	if (strlen($_POST['name']) == 0)
	{
		display_error(_("The town name cannot be empty."));
		set_focus('name');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_town($_POST['name'], $_POST['territory_id'], $_POST['district'], $_POST['state']);
	display_notification(_('New town has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_town($selected_id, $_POST['name'], $_POST['territory_id'], $_POST['district'], $_POST['state']);
	display_notification(_('Selected town has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete')
{
	if (key_in_foreign_table($selected_id, 'sales_beats', 'town_id'))
		display_error(_("Cannot delete this town because beats are assigned to it."));
	else
	{
		delete_town($selected_id);
		display_notification(_('Selected town has been deleted'));
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

$result = get_all_towns(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='70%'");
$th = array(_('Town Name'), _('Territory'), _('District'), _('State'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["territory_name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["district"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["state"], ENT_QUOTES, 'UTF-8'));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_towns', 'id');
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
		$myrow = get_town($selected_id);
		$_POST['name'] = $myrow["name"];
		$_POST['territory_id'] = $myrow["territory_id"];
		$_POST['district'] = $myrow["district"];
		$_POST['state'] = $myrow["state"];
	}
	hidden('selected_id', $selected_id);
}
text_row_ex(_("Town Name").':', 'name', 40);
array_selector_row(_("Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
text_row_ex(_("District").':', 'district', 40);
text_row_ex(_("State").':', 'state', 40);
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
