<?php
$page_security = 'SA_ITEM';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Item Sub Categories"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/classification_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen($_POST['name']) == 0)
	{
		display_error(_("The name cannot be empty."));
		set_focus('name');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_sub_category($_POST['name']);
	display_notification(_('New sub category has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_sub_category($selected_id, $_POST['name']);
	display_notification(_('Selected sub category has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete')
{
	if (key_in_foreign_table($selected_id, 'item_classification', 'sub_category_id'))
		display_error(_("Cannot delete - items are assigned to this sub category."));
	else
	{
		delete_sub_category($selected_id);
		display_notification(_('Selected sub category has been deleted'));
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

$result = get_all_sub_categories(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='40%'");
$th = array(_('Sub Category Name'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'item_sub_categories', 'id');
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
		$myrow = get_sub_category($selected_id);
		$_POST['name'] = $myrow["name"];
	}
	hidden('selected_id', $selected_id);
}
text_row_ex(_("Sub Category Name").':', 'name', 40);
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
