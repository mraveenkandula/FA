<?php
$page_security = 'SA_INVENTORYADJUSTMENT';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Stock Verification"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_adjust_db.inc");
include_once(__DIR__ . "/stock_verification_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('-- none --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

if (isset($_POST['NewVerification']))
	unset($_POST);

$verification_id = get_post('verification_id');

if (!$verification_id)
{
	if (isset($_POST['StartVerification']) && check_csrf_token())
	{
		$verification_id = start_verification($_POST['verify_date'], $_POST['Location'],
			$_POST['employee_id'], $_POST['remarks']);
	}
	else
	{
		if (!isset($_POST['verify_date']) || $_POST['verify_date'] == '')
			$_POST['verify_date'] = Today();

		start_form();
		start_table(TABLESTYLE2);
		date_row(_("Verification Date").':', 'verify_date');
		locations_list_row(_("Location").':', 'Location', null);
		array_selector_row(_("Counted By").':', 'employee_id', @$_POST['employee_id'], employee_list());
		text_row_ex(_("Remarks").':', 'remarks', 60);
		end_table(1);
		submit_center('StartVerification', _("Start Verification"));
		end_form();
		end_page();
		return;
	}
}

$verification = get_verification($verification_id);

if (!$verification['finalized'])
{
	if (isset($_POST['AddItem']) && check_num('counted_qty', 0) && check_csrf_token())
	{
		add_verification_item($verification_id, $_POST['stock_id'], input_num('counted_qty'));
		display_notification(_('Item counted.'));
	}
	if (isset($_POST['Finalize']) && check_csrf_token())
	{
		$adj_id = finalize_verification($verification_id);
		$verification = get_verification($verification_id);
		display_notification($adj_id
			? sprintf(_('Verification finalized. Inventory Adjustment #%d posted for the variances found.'), $adj_id)
			: _('Verification finalized. No variance found - no adjustment needed.'));
	}
}

start_form();
hidden('verification_id', $verification_id);

display_note(sprintf(_('Verification #%d - %s - %s'), $verification_id,
	sql2date($verification['verify_date']), $verification['loc_code']), 0, 1);

if (!$verification['finalized'])
{
	start_table(TABLESTYLE2);
	echo "<tr>"; stock_items_list_cells(_("Item").':', 'stock_id', null); echo "</tr>";
	amount_row(_("Counted Quantity").':', 'counted_qty');
	end_table(1);
	submit_center('AddItem', _("Add Counted Item"));
}
else
	display_note(_('This verification has been finalized.'), 0, 1);

start_table(TABLESTYLE, "width='80%'");
$th = array(_('Item'), _('System Qty'), _('Counted Qty'), _('Variance'));
table_header($th);
$k = 0;
$result = get_verification_items($verification_id);
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['system_qty']);
	amount_cell($row['counted_qty']);
	amount_cell($row['variance_qty']);
	end_row();
}
end_table();

if (!$verification['finalized'])
	submit_center('Finalize', _("Finalize && Post Adjustment"));
submit_center('NewVerification', _("Start a New Verification"));

end_form();
end_page();
