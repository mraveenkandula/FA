<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Journey Plan Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/journey_plan_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/beats_db.inc");

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
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
	if (empty($_POST['sales_employee_id']))
	{
		display_error(_("Select a sales employee."));
		return false;
	}
	return true;
}

if (isset($_POST['SavePlan']) && can_process() && check_csrf_token())
{
	add_journey_plan($_POST['sales_employee_id'], date2sql($_POST['plan_date']), $_POST['beat_id'], $_POST['remarks']);
	display_notification(_('Journey plan saved.'));
	unset($_POST);
}

if (!isset($_POST['plan_date']) || $_POST['plan_date'] == '')
	$_POST['plan_date'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
date_row(_("Plan Date").':', 'plan_date');
array_selector_row(_("Beat").':', 'beat_id', @$_POST['beat_id'], beat_list());
textarea_row(_("Remarks").':', 'remarks', null, 40, 2);
end_table(1);

submit_center('SavePlan', _("Save Plan"));
end_form();
end_page();
