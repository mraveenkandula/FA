<?php
/**********************************************************************
	Added for TechCloud parity - see docs/techcloud-parity-gap-analysis.md
	(Banking and General Ledger tab). Maps to TechCloud's
	gl/inquiry/customer_payments_inquiry.php.

	Read-only inquiry over existing FA core customer payment data
	(0_debtor_trans rows of type ST_CUSTPAYMENT, written by
	write_customer_payment() in sales/includes/db/payment_db.inc). No new
	tables, no GL posting - a plain SELECT.
***********************************************************************/
$page_security = 'SA_OPEN';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/sales/includes/db/payment_db.inc");

$js = "";
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Customer Payments Inquiry"), false, false, "", $js);

start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("Reference:"), 'Ref', '', null, _('Enter reference fragment or leave empty'));
date_cells(_("From:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'TransToDate');
check_cells(_("Show voided:"), 'ShowVoided');
submit_cells('Search', _("Search"), '', _('Refresh Inquiry'), 'default');
end_row();
end_table();
end_form();

$result = get_customer_payments_for_inquiry(get_post('Ref'), get_post('TransAfterDate'),
	get_post('TransToDate'), check_value('ShowVoided'));

start_table(TABLESTYLE, "width='95%'");
$th = array(_("Date"), _("Type"), _("#"), _("Customer"), _("Reference"), _("Amount"),
	_("Memo"), _("Entered By"), "", "");
table_header($th);

$k = 0;
$total = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['tran_date']));
	label_cell(_("Customer Payment"));
	label_cell($row['trans_no'], "align='right'");
	label_cell(htmlspecialchars(trim($row['counterparty']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['amount']);
	label_cell(htmlspecialchars(get_comments_string(ST_CUSTPAYMENT, $row['trans_no']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['entered_by'], ENT_QUOTES, 'UTF-8'));
	label_cell(get_trans_view_str(ST_CUSTPAYMENT, $row['trans_no']));
	label_cell(print_document_link($row['trans_no']."-".ST_CUSTPAYMENT, _("Print"), true, ST_CUSTPAYMENT, ICON_PRINT));
	end_row();
	$total += $row['amount'];
	$k++;
}

if (!$k)
{
	end_table();
	display_note(_("No customer payments found for the selected criteria."), 0, 1);
}
else
{
	start_row("class='inquirybg' style='font-weight:bold'");
	label_cell(_("Total"), "colspan='5' align='right'");
	amount_cell($total);
	label_cell("", "colspan='4'");
	end_row();
	end_table();
}

end_page();
