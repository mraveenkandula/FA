<?php
/**********************************************************************
	Added for TechCloud parity - see docs/techcloud-parity-gap-analysis.md
	(Banking and General Ledger tab). Maps to TechCloud's
	purchasing/inquiry/supp_payment_inq.php.

	Read-only inquiry over existing FA core supplier payment data
	(0_supp_trans rows of type ST_SUPPAYMENT or ST_BANKPAYMENT). No new
	tables, no GL posting - a plain SELECT.
***********************************************************************/
$page_security = 'SA_OPEN';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/purchasing/includes/db/supp_payment_db.inc");

$js = "";
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Supplier Payments Inquiry"), false, false, "", $js);

start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();
supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);
date_cells(_("From:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'TransToDate');
submit_cells('Search', _("Search"), '', _('Refresh Inquiry'), 'default');
end_row();
end_table();
end_form();

$result = get_supplier_payments_for_inquiry(get_post('supplier_id'), get_post('TransAfterDate'),
	get_post('TransToDate'));

start_table(TABLESTYLE, "width='95%'");
$th = array(_("Type"), _("#"), _("Reference"), _("Supplier"), _("Supplier's Reference"),
	_("Date"), _("Due Date"), _("Currency"), _("Amount"), "", "");
table_header($th);

$k = 0;
$total = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($systypes_array[$row['type']]);
	label_cell($row['trans_no'], "align='right'");
	label_cell(htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['supp_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['supp_reference'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['tran_date']));
	label_cell($row['due_date'] != '0000-00-00' && $row['due_date'] ? sql2date($row['due_date']) : '');
	label_cell($row['curr_code'], "align='center'");
	amount_cell($row['amount']);
	label_cell(get_trans_view_str($row['type'], $row['trans_no']));
	label_cell(print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance"), true, ST_SUPPAYMENT, ICON_PRINT));
	end_row();
	$total += $row['amount'];
	$k++;
}

if (!$k)
{
	end_table();
	display_note(_("No supplier payments found for the selected criteria."), 0, 1);
}
else
{
	start_row("class='inquirybg' style='font-weight:bold'");
	label_cell(_("Total"), "colspan='8' align='right'");
	amount_cell($total);
	label_cell("", "colspan='2'");
	end_row();
	end_table();
}

end_page();
