<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Customer Distribution Mapping"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/customer_mapping_db.inc");
include_once(__DIR__ . "/territories_db.inc");
include_once(__DIR__ . "/towns_db.inc");
include_once(__DIR__ . "/beats_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

$person_types = array('Super Stockist' => _('Super Stockist'), 'Distributor' => _('Distributor'), 'Retailer' => _('Retailer'));

function territory_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_territories(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function town_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_towns(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function beat_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_beats(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function employee_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

if (list_updated('customer_id'))
{
	unset($_POST['person_type']); unset($_POST['territory_id']); unset($_POST['town_id']);
	unset($_POST['beat_id']); unset($_POST['sales_employee_id']);
}

if (isset($_POST['SaveMapping']) && !empty($_POST['customer_id']) && check_csrf_token())
{
	save_customer_distribution(
		$_POST['customer_id'], $_POST['person_type'], $_POST['territory_id'],
		$_POST['town_id'], $_POST['beat_id'], $_POST['sales_employee_id']
	);
	display_notification(_('Customer distribution mapping saved.'));
}

start_form();
start_table(TABLESTYLE2);
customer_list_row(_("Customer").':', 'customer_id', null, false, true);

if (!empty($_POST['customer_id']) && !isset($_POST['person_type']))
{
	$existing = get_customer_distribution($_POST['customer_id']);
	if ($existing)
	{
		$_POST['person_type'] = $existing['person_type'];
		$_POST['territory_id'] = $existing['territory_id'];
		$_POST['town_id'] = $existing['town_id'];
		$_POST['beat_id'] = $existing['beat_id'];
		$_POST['sales_employee_id'] = $existing['sales_employee_id'];
	}
}

array_selector_row(_("Person Type").':', 'person_type', @$_POST['person_type'], $person_types);
array_selector_row(_("Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
array_selector_row(_("Town").':', 'town_id', @$_POST['town_id'], town_list());
array_selector_row(_("Beat").':', 'beat_id', @$_POST['beat_id'], beat_list());
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
end_table();

if (!empty($_POST['customer_id']))
	submit_center('SaveMapping', _("Save Mapping"));

end_form();

end_page();
