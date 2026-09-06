<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Customers by Territory/Beat"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/customer_mapping_db.inc");
include_once(__DIR__ . "/../manage/territories_db.inc");
include_once(__DIR__ . "/../manage/beats_db.inc");

function territory_list()
{
	$items = array('' => _('All Territories'));
	$result = get_all_territories(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function beat_list()
{
	$items = array('' => _('All Beats'));
	$result = get_all_beats(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
array_selector_row(_("Beat").':', 'beat_id', @$_POST['beat_id'], beat_list());
end_table();
end_form();

$result = get_customers_by_area(@$_POST['territory_id'], @$_POST['beat_id']);

start_table(TABLESTYLE, "width='90%'");
$th = array(_('Customer'), _('Person Type'), _('Territory'), _('Town'), _('Beat'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['person_type'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['territory_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['town_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['beat_name'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
