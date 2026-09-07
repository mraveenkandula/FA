<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Schemes Note"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/scheme_note_db.inc");
include_once($path_to_root . "/modules/knb_inventory_ext/manage/classification_db.inc");

$person_types = array('' => _('-- all --'), 'Super Stockist' => _('Super Stockist'), 'Distributor' => _('Distributor'), 'Retailer' => _('Retailer'));

simple_page_mode(true);

function brand_list()
{
	$items = array('' => _('-- select brand --'));
	$result = get_all_brands(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

function can_process()
{
	if (empty($_POST['brand_id']))
	{
		display_error(_("Select a brand."));
		return false;
	}
	if (empty($_POST['stock_id']))
	{
		display_error(_("Select an item."));
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_scheme_note($_POST['brand_id'], $_POST['stock_id'], $_POST['person_type']);
	display_notification(_('New scheme note has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_scheme_note($selected_id, $_POST['brand_id'], $_POST['stock_id'], $_POST['person_type']);
	display_notification(_('Selected scheme note has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_scheme_note($selected_id);
	display_notification(_('Selected scheme note has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	$sav_filter = get_post('filter_brand_id');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
	$_POST['filter_brand_id'] = $sav_filter;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Select a Brand").':', 'filter_brand_id', @$_POST['filter_brand_id'], brand_list(), array('select_submit' => true));
end_table();

$result = get_all_scheme_notes(check_value('show_inactive'), @$_POST['filter_brand_id'] ?: null);

start_table(TABLESTYLE, "width='70%'");
$th = array(_('Brand'), _('Item'), _('Role'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["brand_name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["item_description"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["person_type"], ENT_QUOTES, 'UTF-8'));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_scheme_notes', 'id');
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
		$myrow = get_scheme_note($selected_id);
		$_POST['brand_id'] = $myrow["brand_id"];
		$_POST['stock_id'] = $myrow["stock_id"];
		$_POST['person_type'] = $myrow["person_type"];
	}
	hidden('selected_id', $selected_id);
}

array_selector_row(_("Brand").':', 'brand_id', @$_POST['brand_id'], brand_list());
echo "<tr>"; stock_items_list_cells(_("Item").':', 'stock_id', @$_POST['stock_id']); echo "</tr>";
array_selector_row(_("Role").':', 'person_type', @$_POST['person_type'], $person_types);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
