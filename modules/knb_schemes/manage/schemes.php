<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Schemes Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/schemes_db.inc");
include_once($path_to_root . "/modules/knb_inventory_ext/manage/classification_db.inc");
include_once($path_to_root . "/modules/knb_distribution/manage/territories_db.inc");

$person_types = array('' => _('-- all --'), 'Super Stockist' => _('Super Stockist'), 'Distributor' => _('Distributor'), 'Retailer' => _('Retailer'));
// Confirmed against the real TechCloud commercial agreement's Promotion
// Management scope (Price Off, Percentage Off, Bill value promotions,
// Buy-Get promotions) - QTY_SLAB is the one type this module already had.
$promotion_types = array(
	'QTY_SLAB' => _('Quantity Slab (buy N units, get reward)'),
	'PRICE_OFF' => _('Price Off (flat amount off per unit, at a qty slab)'),
	'PERCENT_OFF' => _('Percentage Off (% off, at a qty slab)'),
	'BILL_VALUE' => _('Bill Value (spend threshold on one invoice, get reward)'),
	'BUY_GET' => _('Buy-Get (buy X units, get Y units free)'),
);
$SLAB_COUNT = 8;

function brand_list()
{
	$items = array('' => _('-- all brands --'));
	$result = get_all_brands(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}
function state_list()
{
	$items = array('' => _('-- all states --'));
	$result = get_distinct_states();
	while ($row = db_fetch($result)) $items[$row['state']] = $row['state'];
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
	if (empty($_POST['start_date']) || empty($_POST['end_date']))
	{
		display_error(_("Start date and end date are required."));
		return false;
	}
	$has_slab = false;
	$slab_fields = array('slab_target', 'scheme_text', 'discount_percent', 'discount_amount',
		'bill_value_threshold', 'buy_qty', 'get_qty');
	for ($i = 1; $i <= 8; $i++)
		foreach ($slab_fields as $f)
			if (trim(@$_POST["{$f}_$i"]) !== '')
				$has_slab = true;
	if (!$has_slab)
	{
		display_error(_("Enter at least one slab."));
		return false;
	}
	return true;
}

function collect_slabs()
{
	global $SLAB_COUNT;
	$slabs = array();
	for ($i = 1; $i <= $SLAB_COUNT; $i++)
	{
		$slabs[] = array(
			'slab_label' => trim(@$_POST["slab_label_$i"]),
			'slab_target_qty' => trim(@$_POST["slab_target_$i"]) !== '' ? input_num("slab_target_$i") : '',
			'scheme_text' => trim(@$_POST["scheme_text_$i"]),
			'discount_percent' => trim(@$_POST["discount_percent_$i"]) !== '' ? input_num("discount_percent_$i") : '',
			'discount_amount' => trim(@$_POST["discount_amount_$i"]) !== '' ? input_num("discount_amount_$i") : '',
			'bill_value_threshold' => trim(@$_POST["bill_value_threshold_$i"]) !== '' ? input_num("bill_value_threshold_$i") : '',
			'buy_qty' => trim(@$_POST["buy_qty_$i"]) !== '' ? input_num("buy_qty_$i") : '',
			'get_qty' => trim(@$_POST["get_qty_$i"]) !== '' ? input_num("get_qty_$i") : '',
		);
	}
	return $slabs;
}

function load_scheme_into_post($id)
{
	$myrow = get_scheme($id);
	$_POST['scheme_id'] = $id;
	$_POST['brand_id'] = $myrow['brand_id'];
	$_POST['person_type'] = $myrow['person_type'];
	$_POST['state'] = $myrow['state'];
	$_POST['territory_id'] = $myrow['territory_id'];
	$_POST['start_date'] = sql2date($myrow['start_date']);
	$_POST['end_date'] = sql2date($myrow['end_date']);
	$_POST['promotion_type'] = $myrow['promotion_type'];
	foreach (get_scheme_slabs($id) as $slab)
	{
		$_POST["slab_label_{$slab['slab_no']}"] = $slab['slab_label'];
		$_POST["slab_target_{$slab['slab_no']}"] = $slab['slab_target_qty'];
		$_POST["scheme_text_{$slab['slab_no']}"] = $slab['scheme_text'];
		$_POST["discount_percent_{$slab['slab_no']}"] = $slab['discount_percent'];
		$_POST["discount_amount_{$slab['slab_no']}"] = $slab['discount_amount'];
		$_POST["bill_value_threshold_{$slab['slab_no']}"] = $slab['bill_value_threshold'];
		$_POST["buy_qty_{$slab['slab_no']}"] = $slab['buy_qty'];
		$_POST["get_qty_{$slab['slab_no']}"] = $slab['get_qty'];
	}
}

if (isset($_POST['AddScheme']) && can_process() && check_csrf_token())
{
	add_scheme($_POST['brand_id'], $_POST['person_type'], $_POST['state'], $_POST['territory_id'],
		$_POST['start_date'], $_POST['end_date'], collect_slabs(), $_POST['promotion_type']);
	display_notification(_('Scheme added.'));
	unset($_POST);
}
elseif (isset($_POST['UpdateScheme']) && can_process() && check_csrf_token())
{
	update_scheme($_POST['scheme_id'], $_POST['brand_id'], $_POST['person_type'], $_POST['state'], $_POST['territory_id'],
		$_POST['start_date'], $_POST['end_date'], collect_slabs(), $_POST['promotion_type']);
	display_notification(_('Scheme updated.'));
	unset($_POST);
}
elseif (isset($_POST['CancelEdit']))
{
	unset($_POST);
}
else
{
	$delete_id = find_submit('Delete_');
	if ($delete_id != -1 && check_csrf_token())
	{
		delete_scheme($delete_id);
		display_notification(_('Scheme deleted.'));
		unset($_POST);
	}
	else
	{
		$edit_id = find_submit('Edit_');
		if ($edit_id != -1 && check_csrf_token())
			load_scheme_into_post($edit_id);
	}
}

if (!isset($_POST['start_date']) || $_POST['start_date'] == '')
{
	$_POST['start_date'] = Today();
	$_POST['end_date'] = Today();
}

start_form();

if (!empty($_POST['scheme_id']))
	hidden('scheme_id', $_POST['scheme_id']);

start_table(TABLESTYLE2);
array_selector_row(_("Brand").':', 'brand_id', @$_POST['brand_id'], brand_list());
array_selector_row(_("Person Type").':', 'person_type', @$_POST['person_type'], $person_types);
array_selector_row(_("State").':', 'state', @$_POST['state'], state_list());
array_selector_row(_("Sales Territory").':', 'territory_id', @$_POST['territory_id'], territory_list());
array_selector_row(_("Promotion Type").':', 'promotion_type', @$_POST['promotion_type'] ?: 'QTY_SLAB', $promotion_types);
date_row(_("Start Date").':', 'start_date');
date_row(_("End Date").':', 'end_date');
end_table();

display_note(_("Fill in only the columns that matter for the Promotion Type chosen above - e.g. a Bill Value promotion uses Bill Value Threshold and leaves Slab Target Qty blank; a Buy-Get promotion uses Buy Qty/Get Qty and leaves the others blank."), 0, 1);

start_table(TABLESTYLE, "width='98%'");
$th = array(_('Slab #'), _('Label'), _('Target Qty'), _('Discount %'), _('Discount Amt'),
	_('Bill Value Threshold'), _('Buy Qty'), _('Get Qty (free)'), _('Reward / Notes'));
table_header($th);
$k = 0;
for ($i = 1; $i <= $SLAB_COUNT; $i++)
{
	alt_table_row_color($k);
	label_cell($i);
	text_cells(null, "slab_label_$i", @$_POST["slab_label_$i"], 12);
	small_amount_cells(null, "slab_target_$i", @$_POST["slab_target_$i"]);
	small_amount_cells(null, "discount_percent_$i", @$_POST["discount_percent_$i"]);
	small_amount_cells(null, "discount_amount_$i", @$_POST["discount_amount_$i"]);
	small_amount_cells(null, "bill_value_threshold_$i", @$_POST["bill_value_threshold_$i"]);
	small_amount_cells(null, "buy_qty_$i", @$_POST["buy_qty_$i"]);
	small_amount_cells(null, "get_qty_$i", @$_POST["get_qty_$i"]);
	text_cells(null, "scheme_text_$i", @$_POST["scheme_text_$i"], 30);
	end_row();
}
end_table(1);

if (empty($_POST['scheme_id']))
	submit_center('AddScheme', _("Add Scheme"));
else
{
	submit_center('UpdateScheme', _("Update Scheme"));
	submit_center('CancelEdit', _("Cancel"));
}

function slab_summary_line($promotion_type, $slab)
{
	switch ($promotion_type)
	{
		case 'PRICE_OFF':
			return $slab['slab_target_qty'].' units -> '._('flat').' '.$slab['discount_amount'].' off';
		case 'PERCENT_OFF':
			return $slab['slab_target_qty'].' units -> '.$slab['discount_percent'].'% off';
		case 'BILL_VALUE':
			return _('Bill >=').' '.$slab['bill_value_threshold'].' -> '.$slab['scheme_text'];
		case 'BUY_GET':
			return _('Buy').' '.$slab['buy_qty'].' '._('Get').' '.$slab['get_qty'].' '._('free');
		default:
			return $slab['slab_target_qty'].' -> '.$slab['scheme_text'];
	}
}

display_heading(_("Existing Schemes"));
$result = get_all_schemes(true);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Brand'), _('Person Type'), _('State'), _('Territory'), _('Type'), _('Start'), _('End'), _('Slabs'), '', '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['brand_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['person_type'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['state'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['territory_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['promotion_type'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['start_date']));
	label_cell(sql2date($row['end_date']));

	$slab_lines = array();
	foreach (get_scheme_slabs($id) as $slab)
		$slab_lines[] = htmlspecialchars(slab_summary_line($row['promotion_type'], $slab), ENT_QUOTES, 'UTF-8');
	label_cell(implode('<br>', $slab_lines));

	label_cell("<input type='submit' name='Edit_$id' value='"._('Edit')."' class='inputsubmit'>");
	label_cell("<input type='submit' name='Delete_$id' value='"._('Delete')."' class='inputsubmit'>");
	end_row();
}
end_table();

end_form();
end_page();
