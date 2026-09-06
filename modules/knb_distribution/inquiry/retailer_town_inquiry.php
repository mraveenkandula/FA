<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Retailer Town Details Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/towns_db.inc");
include_once(__DIR__ . "/../manage/territories_db.inc");
include_once(__DIR__ . "/../manage/beats_db.inc");

function state_list()
{
	$items = array('' => _('-- all states --'));
	$result = db_query("SELECT DISTINCT state FROM ".TB_PREF."sales_towns WHERE state IS NOT NULL AND state <> '' ORDER BY state",
		"could not get states");
	while ($row = db_fetch($result)) $items[$row['state']] = $row['state'];
	return $items;
}
function district_list()
{
	$items = array('' => _('-- all districts --'));
	$result = db_query("SELECT DISTINCT district FROM ".TB_PREF."sales_towns WHERE district IS NOT NULL AND district <> '' ORDER BY district",
		"could not get districts");
	while ($row = db_fetch($result)) $items[$row['district']] = $row['district'];
	return $items;
}
function town_list()
{
	$items = array('' => _('-- all towns --'));
	$result = get_all_towns(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function territory_list()
{
	$items = array('' => _('-- all territories --'));
	$result = get_all_territories(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function beat_list()
{
	$items = array('' => _('-- all beats --'));
	$result = get_all_beats(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

function get_retailer_town_details($state, $district, $town_id, $territory_id, $beat_id)
{
	$sql = "SELECT t.name AS town_name, t.district, cd.person_type, d.name AS retailer_name,
			cd.phone, b.name AS beat_name,
			(SELECT COUNT(DISTINCT b2.id) FROM ".TB_PREF."sales_beats b2 WHERE b2.town_id = t.id) AS beat_count
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = cd.town_id
		LEFT JOIN ".TB_PREF."sales_beats b ON b.id = cd.beat_id
		WHERE cd.person_type = 'Retailer'";
	if ($state)
		$sql .= " AND t.state=".db_escape($state);
	if ($district)
		$sql .= " AND t.district=".db_escape($district);
	if ($town_id)
		$sql .= " AND cd.town_id=".db_escape($town_id);
	if ($territory_id)
		$sql .= " AND cd.territory_id=".db_escape($territory_id);
	if ($beat_id)
		$sql .= " AND cd.beat_id=".db_escape($beat_id);
	$sql .= " ORDER BY t.name, d.name";
	return db_query($sql, "could not get retailer town details");
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("State").':', 'state', @$_POST['state'], state_list());
array_selector_row(_("District").':', 'district', @$_POST['district'], district_list());
array_selector_row(_("Town").':', 'town_id', @$_POST['town_id'], town_list());
array_selector_row(_("Sales Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
array_selector_row(_("Sales Beat").':', 'beat_id', @$_POST['beat_id'], beat_list());
end_table();
end_form();

$result = get_retailer_town_details(@$_POST['state'], @$_POST['district'], @$_POST['town_id'],
	@$_POST['territory_id'], @$_POST['beat_id']);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Town Name'), _('District'), _('No of Beats'), _('Retailer Name'), _('Phone'), _('Beat'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['town_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['district'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['beat_count']);
	label_cell(htmlspecialchars($row['retailer_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['beat_name'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
