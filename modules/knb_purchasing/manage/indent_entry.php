<?php
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Purchase Indent Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/indent_db.inc");
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
	if (strlen($_POST['item_description']) == 0)
	{
		display_error(_("The item description cannot be empty."));
		set_focus('item_description');
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

if (isset($_POST['AddIndent']) && can_process() && check_csrf_token())
{
	add_indent(
		$_POST['item_description'], input_num('qty'), $_POST['uom'], $_POST['requested_by'],
		date2sql($_POST['requested_date']), $_POST['required_by_date'] ? date2sql($_POST['required_by_date']) : null,
		$_POST['remarks']
	);
	display_notification(_('Purchase indent submitted for approval.'));
	unset($_POST);
}

if (!isset($_POST['requested_date']) || $_POST['requested_date'] == '')
	$_POST['requested_date'] = Today();

start_form();
start_table(TABLESTYLE2);
text_row_ex(_("Item Description").':', 'item_description', 60);
amount_row(_("Quantity").':', 'qty');
text_row_ex(_("Unit of Measure").':', 'uom', 20);
array_selector_row(_("Requested By").':', 'requested_by', @$_POST['requested_by'], employee_list());
date_row(_("Requested Date").':', 'requested_date');
date_row(_("Required By").':', 'required_by_date');
textarea_row(_("Remarks").':', 'remarks', null, 40, 3);
end_table(1);

submit_center('AddIndent', _("Submit Indent"));
end_form();
end_page();
