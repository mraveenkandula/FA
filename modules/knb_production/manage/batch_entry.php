<?php
$page_security = 'SA_WORKORDERENTRY';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Production Batch Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/batch_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

$stages = array('RM_TO_SFG' => _('Raw Material -> Semi-Finished Goods'), 'SFG_TO_FG' => _('Semi-Finished Goods -> Finished Goods'));

function employee_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (!check_num('input_qty', 0.0001))
	{
		display_error(_("Input quantity must be a positive number."));
		set_focus('input_qty');
		return false;
	}
	if (!check_num('output_qty', 0))
	{
		display_error(_("Output quantity must be a non-negative number."));
		set_focus('output_qty');
		return false;
	}
	return true;
}

if (isset($_POST['AddBatch']) && can_process() && check_csrf_token())
{
	add_production_batch(
		date2sql($_POST['batch_date']), $_POST['stage'], $_POST['input_item'], input_num('input_qty'),
		$_POST['output_item'], input_num('output_qty'), $_POST['fat_content'], $_POST['moisture_percent'],
		$_POST['incharge_employee_id'], $_POST['remarks']
	);
	$yield = input_num('input_qty') > 0 ? round((input_num('output_qty') / input_num('input_qty')) * 100, 2) : 0;
	display_notification(sprintf(_('Batch recorded. Yield: %s%%'), $yield));
	unset($_POST);
}

if (!isset($_POST['batch_date']) || $_POST['batch_date'] == '')
	$_POST['batch_date'] = Today();

start_form();
start_table(TABLESTYLE2);
date_row(_("Batch Date").':', 'batch_date');
array_selector_row(_("Production Stage").':', 'stage', @$_POST['stage'], $stages);
echo "<tr>"; stock_items_list_cells(_("Input Item (raw material / SFG)").':', 'input_item', null); echo "</tr>";
amount_row(_("Input Quantity").':', 'input_qty');
echo "<tr>"; stock_items_list_cells(_("Output Item (SFG / finished ghee)").':', 'output_item', null); echo "</tr>";
amount_row(_("Output Quantity").':', 'output_qty');
text_row_ex(_("Fat Content %").':', 'fat_content', 10);
text_row_ex(_("Moisture %").':', 'moisture_percent', 10);
array_selector_row(_("Incharge Employee").':', 'incharge_employee_id', @$_POST['incharge_employee_id'], employee_list());
textarea_row(_("Remarks").':', 'remarks', null, 40, 3);
end_table(1);

submit_center('AddBatch', _("Record Batch"));
end_form();
end_page();
