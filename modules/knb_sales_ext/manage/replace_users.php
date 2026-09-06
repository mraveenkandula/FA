<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Replace Sales Employee"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/territories_db.inc");

function employee_list()
{
	$items = array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}
function territory_list()
{
	$items = array('' => _('-- all territories --'));
	$result = get_all_territories(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

function can_process()
{
	if (empty($_POST['old_employee_id']) || empty($_POST['new_employee_id']))
	{
		display_error(_("Select both the old and new sales employee."));
		return false;
	}
	if ($_POST['old_employee_id'] == $_POST['new_employee_id'])
	{
		display_error(_("Old and new sales employee cannot be the same."));
		return false;
	}
	return true;
}

if (isset($_POST['Replace']) && can_process() && check_csrf_token())
{
	$sql = "UPDATE ".TB_PREF."customer_distribution SET sales_employee_id=".db_escape($_POST['new_employee_id'])."
		WHERE sales_employee_id=".db_escape($_POST['old_employee_id']);
	if (!empty($_POST['territory_id']))
		$sql .= " AND territory_id=".db_escape($_POST['territory_id']);
	db_query($sql, "could not replace sales employee");
	$affected = db_num_affected_rows();
	display_notification(sprintf(_('%d customer(s) reassigned.'), $affected));
	unset($_POST);
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Replace customers currently assigned to").':', 'old_employee_id', @$_POST['old_employee_id'], employee_list());
array_selector_row(_("With").':', 'new_employee_id', @$_POST['new_employee_id'], employee_list());
array_selector_row(_("Limit to Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
end_table(1);

submit_center('Replace', _("Replace"));
end_form();
end_page();
