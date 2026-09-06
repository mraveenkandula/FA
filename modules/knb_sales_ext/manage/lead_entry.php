<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Lead Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/lead_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/territories_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/towns_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/beats_db.inc");

function employee_list()
{
	$items = array('' => _('-- unassigned --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}
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

function can_process()
{
	if (strlen(trim($_POST['lead_name'])) == 0)
	{
		display_error(_("Lead name cannot be empty."));
		set_focus('lead_name');
		return false;
	}
	return true;
}

if (isset($_POST['AddLead']) && can_process() && check_csrf_token())
{
	add_sales_lead($_POST['lead_name'], $_POST['phone'], $_POST['address'], $_POST['territory_id'],
		$_POST['town_id'], $_POST['beat_id'], $_POST['assigned_employee_id'], $_POST['remarks'], date2sql(Today()));
	display_notification(_('Lead added.'));
	unset($_POST);
}

start_form();
start_table(TABLESTYLE2);
text_row_ex(_("Lead / Prospect Name").':', 'lead_name', 60);
text_row_ex(_("Phone").':', 'phone', 20);
text_row_ex(_("Address").':', 'address', 60);
array_selector_row(_("Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
array_selector_row(_("Town").':', 'town_id', @$_POST['town_id'], town_list());
array_selector_row(_("Beat").':', 'beat_id', @$_POST['beat_id'], beat_list());
array_selector_row(_("Assign To").':', 'assigned_employee_id', @$_POST['assigned_employee_id'], employee_list());
textarea_row(_("Remarks").':', 'remarks', null, 40, 2);
end_table(1);

submit_center('AddLead', _("Add Lead"));
end_form();
end_page();
