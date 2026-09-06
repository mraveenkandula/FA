<?php
/*
	GST Summary Report (GSTR-1/GSTR-3B) - the real figures for a
	period, not the GSTN portal's JSON upload format. Confirmed as a real
	gap against the actual TechCloud commercial agreement's Finance
	Management scope ("GSTR - 1", "GSTR - 3B").
*/
$page_security = 'SA_GLANALYTIC';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "GST Summary Report"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/gst_summary_db.inc");

start_form();
start_table(TABLESTYLE2);
date_row(_("From:"), 'from_date');
date_row(_("To:"), 'to_date');
end_table(1);
submit_center('Go', _('Show Report'));
end_form();

if (isset($_POST['from_date']) && isset($_POST['to_date']))
{
	$from_date = $_POST['from_date'];
	$to_date = $_POST['to_date'];

	display_note(_("GSTR-3B Summary"), 1, 1);
	$s = get_gstr3b_summary($from_date, $to_date);
	start_table(TABLESTYLE, "width='60%'");
	label_row(_("Invoices"), $s['invoice_count']);
	label_row(_("Taxable Value"), number_format2($s['taxable_value'], 2));
	label_row(_("IGST"), number_format2($s['igst'], 2));
	label_row(_("CGST"), number_format2($s['cgst'], 2));
	label_row(_("SGST"), number_format2($s['sgst'], 2));
	label_row(_("Total Tax Liability"), number_format2($s['total_tax'], 2));
	label_row(_("Total Invoice Value"), number_format2($s['total_invoice_value'], 2));
	end_table(1);

	display_note(_("GSTR-1: B2C Summary (unregistered customers)"), 1, 1);
	$b2c = get_gstr1_b2c_summary($from_date, $to_date);
	start_table(TABLESTYLE, "width='60%'");
	label_row(_("Invoices"), $b2c['invoice_count']);
	label_row(_("Taxable Value"), number_format2($b2c['taxable_value'], 2));
	label_row(_("Tax Amount"), number_format2($b2c['tax_amount'], 2));
	end_table(1);

	display_note(_("GSTR-1: B2B Invoices (registered customers)"), 1, 1);
	$result = get_gstr1_b2b($from_date, $to_date);
	start_table(TABLESTYLE, "width='95%'");
	table_header(array(_('Invoice #'), _('Reference'), _('Date'), _('Customer'), _('GSTIN'),
		_('Taxable Value'), _('Tax'), _('Total')));
	$k = 0;
	$any = false;
	while ($row = db_fetch($result))
	{
		$any = true;
		alt_table_row_color($k);
		label_cell($row['trans_no']);
		label_cell(htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'));
		label_cell(sql2date($row['tran_date']));
		label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['gstin'], ENT_QUOTES, 'UTF-8'));
		amount_cell($row['ov_amount'] + $row['ov_freight']);
		amount_cell($row['ov_gst'] + $row['ov_freight_tax']);
		amount_cell($row['total']);
		end_row();
	}
	end_table(1);
	if (!$any)
		display_note(_("No B2B (GSTIN-registered customer) invoices in this period."), 0, 1);

	display_note(_("HSN-wise summary is not available - stock items have no HSN code recorded. Add an HSN field to item master before this section can be built."), 1, 1);
}

end_page();
