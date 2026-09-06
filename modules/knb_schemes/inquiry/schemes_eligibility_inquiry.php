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

	start_table(TABLESTYLE, "width='50%'");
	$th = array(_('Slab'), _('Target Qty (cases)'), _('Scheme'));
	table_header($th);
	$k = 0;
	foreach ($slabs as $slab)
	{
		alt_table_row_color($k);
		label_cell($slab['slab_no']);
		amount_cell($slab['slab_target_qty']);
		label_cell(htmlspecialchars($slab['scheme_text'], ENT_QUOTES, 'UTF-8'));
		end_row();
	}
	end_table(1);

	display_heading(_("Customer Achievement"));
	$result = get_scheme_achievement($scheme);
	start_table(TABLESTYLE, "width='70%'");
	$th = array(_('Customer'), _('Total Qty Ordered'), _('Slab Reached'), _('Scheme Earned'));
	table_header($th);
	$k = 0;
	while ($row = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
		amount_cell($row['total_qty']);

		// pick the highest qualifying target, not just the last one in slab_no
		// order, so it doesn't depend on slabs being entered lowest-to-highest.
		$best_target = -1;
		$reached_label = '';
		$reached_text = '';
		foreach ($slabs as $slab)
		{
			if ($slab['slab_target_qty'] > 0 && $row['total_qty'] >= $slab['slab_target_qty']
				&& $slab['slab_target_qty'] > $best_target)
			{
				$best_target = $slab['slab_target_qty'];
				$reached_label = $slab['slab_no'];
				$reached_text = $slab['scheme_text'];
			}
		}
		label_cell($reached_label);
		label_cell(htmlspecialchars($reached_text, ENT_QUOTES, 'UTF-8'));
		end_row();
	}
	end_table();
}

end_page();
