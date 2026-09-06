<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Schemes Eligibility Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/schemes_db.inc");

function scheme_list()
{
	$items = array('' => _('-- select a scheme --'));
	$result = get_all_schemes(true);
	while ($row = db_fetch($result))
		$items[$row['id']] = $row['brand_name'].' ('.sql2date($row['start_date']).' - '.sql2date($row['end_date']).')';
	return $items;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Scheme").':', 'scheme_id', @$_POST['scheme_id'], scheme_list());
end_table();
end_form();

if (!empty($_POST['scheme_id']))
{
	$scheme = get_scheme($_POST['scheme_id']);
	$slabs = get_scheme_slabs($_POST['scheme_id']);
	$type = $scheme['promotion_type'];

	label_row(_("Promotion Type").':', htmlspecialchars($type, ENT_QUOTES, 'UTF-8'));

	start_table(TABLESTYLE, "width='90%'");
	$th = array(_('Slab'), _('Target Qty'), _('Discount %'), _('Discount Amt'),
		_('Bill Value Threshold'), _('Buy Qty'), _('Get Qty'), _('Notes'));
	table_header($th);
	$k = 0;
	foreach ($slabs as $slab)
	{
		alt_table_row_color($k);
		label_cell($slab['slab_no']);
		amount_cell($slab['slab_target_qty']);
		label_cell($slab['discount_percent']);
		label_cell($slab['discount_amount']);
		label_cell($slab['bill_value_threshold']);
		label_cell($slab['buy_qty']);
		label_cell($slab['get_qty']);
		label_cell(htmlspecialchars($slab['scheme_text'], ENT_QUOTES, 'UTF-8'));
		end_row();
	}
	end_table(1);

	if ($type == 'BUY_GET')
	{
		display_note(_("Buy-Get promotions are configured above but eligibility isn't computed here yet - checking which specific line items on an order qualify (e.g. same item vs a different reward item) needs order-entry-time logic, not a historical achievement report like the other promotion types."), 0, 1);
	}
	elseif ($type == 'BILL_VALUE')
	{
		display_heading(_("Invoices vs Bill Value Threshold"));
		$result = get_scheme_bill_value_achievement($scheme);
		start_table(TABLESTYLE, "width='70%'");
		$th = array(_('Invoice #'), _('Customer'), _('Bill Value'), _('Slab Reached'), _('Reward Earned'));
		table_header($th);
		$k = 0;
		while ($row = db_fetch($result))
		{
			alt_table_row_color($k);
			label_cell($row['trans_no']);
			label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
			amount_cell($row['bill_value']);

			$best_slab = find_best_qualifying_slab($slabs, $row['bill_value'], 'bill_value_threshold');
			label_cell($best_slab ? $best_slab['slab_no'] : '');
			label_cell(htmlspecialchars($best_slab ? $best_slab['scheme_text'] : '', ENT_QUOTES, 'UTF-8'));
			end_row();
		}
		end_table();
	}
	else // QTY_SLAB, PRICE_OFF, PERCENT_OFF - all threshold-on-quantity checks
	{
		display_heading(_("Customer Achievement"));
		$result = get_scheme_achievement($scheme);
		start_table(TABLESTYLE, "width='80%'");
		$th = array(_('Customer'), _('Total Qty Ordered'), _('Slab Reached'), _('Reward Earned'));
		table_header($th);
		$k = 0;
		while ($row = db_fetch($result))
		{
			alt_table_row_color($k);
			label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
			amount_cell($row['total_qty']);

			$best_slab = find_best_qualifying_slab($slabs, $row['total_qty']);
			label_cell($best_slab ? $best_slab['slab_no'] : '');
			if ($best_slab && $type == 'PERCENT_OFF')
				label_cell($best_slab['discount_percent'].'% off');
			elseif ($best_slab && $type == 'PRICE_OFF')
				label_cell(number_format2($best_slab['discount_amount'], 2).' off');
			else
				label_cell(htmlspecialchars($best_slab ? $best_slab['scheme_text'] : '', ENT_QUOTES, 'UTF-8'));
			end_row();
		}
		end_table();
	}
}

end_page();
