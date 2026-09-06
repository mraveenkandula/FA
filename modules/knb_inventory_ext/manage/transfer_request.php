<?php
$page_security = 'SA_LOCATIONTRANSFER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Stock Transfer Request"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/transfer_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['from_location']) || empty($_POST['to_location']))
	{
		display_error(_("Both from and to locations are required."));
		return false;
	}
	if ($_POST['from_location'] == $_POST['to_location'])
	{
		display_error(_("From and to locations must be different."));
		return false;
	}
	if (!check_num('qty', 0.0001))
	{
		display_error(_("Quantity must be a positive number."));
		set_focus('qty');
		return false;
	}
	return true;
}

if (isset($_POST['AddRequest']) && can_process() && check_csrf_token())
{
	add_transfer_request(
		$_POST['from_location'], $_POST['to_location'], $_POST['stock_id'], input_num('qty'),
		$_POST['requested_by'], date2sql($_POST['requested_date']), $_POST['remarks']
	);
	display_notification(_('Stock transfer request submitted for approval.'));
	unset($_POST);
}

if (!isset($_POST['requested_date']) || $_POST['requested_date'] == '')
	$_POST['requested_date'] = Today();

start_form();
start_table(TABLESTYLE2);
locations_list_row(_("From Location").':', 'from_location', @$_POST['from_location']);
locations_list_row(_("To Location").':', 'to_location', @$_POST['to_location']);
echo "<tr>"; stock_items_list_cells(_("Item").':', 'stock_id', null); echo "</tr>";
amount_row(_("Quantity").':', 'qty');
array_selector_row(_("Requested By").':', 'requested_by', @$_POST['requested_by'], employee_list());
date_row(_("Requested Date").':', 'requested_date');
textarea_row(_("Remarks").':', 'remarks', null, 40, 3);
end_table(1);

submit_center('AddRequest', _("Submit Request"));
end_form();
end_page();
