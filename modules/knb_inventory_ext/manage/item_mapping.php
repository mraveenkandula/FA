<?php
$page_security = 'SA_ITEM';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Item Classification Mapping"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/classification_db.inc");

function sub_category_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_sub_categories(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function brand_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_brands(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function capacity_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_capacities(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

if (list_updated('stock_id'))
{
	unset($_POST['sub_category_id']); unset($_POST['brand_id']); unset($_POST['capacity_id']);
}

if (isset($_POST['SaveMapping']) && !empty($_POST['stock_id']) && check_csrf_token())
{
	save_item_classification($_POST['stock_id'], $_POST['sub_category_id'], $_POST['brand_id'], $_POST['capacity_id']);
	display_notification(_('Item classification saved.'));
}

start_form();
start_table(TABLESTYLE2);
echo "<tr>"; stock_items_list_cells(_("Item").':', 'stock_id', null, false, true); echo "</tr>";

if (!empty($_POST['stock_id']) && !isset($_POST['sub_category_id']))
{
	$existing = get_item_classification($_POST['stock_id']);
	if ($existing)
	{
		$_POST['sub_category_id'] = $existing['sub_category_id'];
		$_POST['brand_id'] = $existing['brand_id'];
		$_POST['capacity_id'] = $existing['capacity_id'];
	}
}

array_selector_row(_("Sub Category").':', 'sub_category_id', @$_POST['sub_category_id'], sub_category_list());
array_selector_row(_("Brand").':', 'brand_id', @$_POST['brand_id'], brand_list());
array_selector_row(_("Capacity").':', 'capacity_id', @$_POST['capacity_id'], capacity_list());
end_table();

if (!empty($_POST['stock_id']))
	submit_center('SaveMapping', _("Save Mapping"));

end_form();
end_page();
