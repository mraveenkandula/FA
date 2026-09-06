<?php
/*
	GST Summary Report - GSTR-1/GSTR-3B, confirmed as a real gap against the
	actual TechCloud commercial agreement (Finance Management: "GSTR - 1",
	"GSTR - 3B"). This gives an accountant/CA the real figures needed to
	file - it does not generate the GSTN portal's JSON upload format, which
	is a much larger, more exacting spec than a first pass here should
	attempt.

	Known gap, called out rather than faked: stock_master has no HSN code
	column (neither ours nor TechCloud's real export had one), so the HSN-
	wise summary section of GSTR-1 can't be built from this data - it needs
	an HSN master added to items first.
*/

// GSTR-3B: outward taxable value + tax liability, split IGST vs CGST/SGST
// by matching the real tax_types names (see 0_tax_types) rather than
// guessing account codes.
function get_gstr3b_summary($from_date, $to_date)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$row = db_fetch_row(db_query(
		"SELECT COUNT(*), COALESCE(SUM(ov_amount),0), COALESCE(SUM(ov_freight),0)
		 FROM ".TB_PREF."debtor_trans
		 WHERE type=".ST_SALESINVOICE." AND tran_date BETWEEN '$from' AND '$to'"));
	$s = array(
		'invoice_count' => (int)$row[0],
		'taxable_value' => round((float)$row[1] + (float)$row[2], 2),
	);

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(td.amount),0) FROM ".TB_PREF."trans_tax_details td
		 JOIN ".TB_PREF."tax_types tt ON tt.id = td.tax_type_id
		 WHERE td.trans_type=".ST_SALESINVOICE." AND td.tran_date BETWEEN '$from' AND '$to'
		 AND tt.name LIKE 'IGST%'"));
	$s['igst'] = round((float)$row[0], 2);

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(td.amount),0) FROM ".TB_PREF."trans_tax_details td
		 JOIN ".TB_PREF."tax_types tt ON tt.id = td.tax_type_id
		 WHERE td.trans_type=".ST_SALESINVOICE." AND td.tran_date BETWEEN '$from' AND '$to'
		 AND tt.name LIKE 'CGST%'"));
	$s['cgst'] = round((float)$row[0], 2);

	$row = db_fetch_row(db_query(
		"SELECT COALESCE(SUM(td.amount),0) FROM ".TB_PREF."trans_tax_details td
		 JOIN ".TB_PREF."tax_types tt ON tt.id = td.tax_type_id
		 WHERE td.trans_type=".ST_SALESINVOICE." AND td.tran_date BETWEEN '$from' AND '$to'
		 AND tt.name LIKE 'SGST%'"));
	$s['sgst'] = round((float)$row[0], 2);

	$s['total_tax'] = round($s['igst'] + $s['cgst'] + $s['sgst'], 2);
	$s['total_invoice_value'] = round($s['taxable_value'] + $s['total_tax'], 2);

	return $s;
}

// GSTR-1 B2B: only customers with a real GSTIN on file (most of ours don't
// - small retailers are typically unregistered/composition, which is
// realistic, not a data problem), invoice-wise as GSTR-1 requires.
function get_gstr1_b2b($from_date, $to_date)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	return db_query(
		"SELECT dt.trans_no, dt.reference, dt.tran_date, dm.name AS customer_name, dm.tax_id AS gstin,
		 dt.ov_amount, dt.ov_gst, dt.ov_freight, dt.ov_freight_tax,
		 (dt.ov_amount+dt.ov_gst+dt.ov_freight+dt.ov_freight_tax) AS total
		 FROM ".TB_PREF."debtor_trans dt
		 JOIN ".TB_PREF."debtors_master dm ON dm.debtor_no = dt.debtor_no
		 WHERE dt.type=".ST_SALESINVOICE." AND dt.tran_date BETWEEN '$from' AND '$to'
		 AND dm.tax_id IS NOT NULL AND dm.tax_id != ''
		 ORDER BY dt.tran_date, dt.trans_no");
}

// GSTR-1 B2C: unregistered customers (real consumers don't need per-
// invoice detail in GSTR-1, just a total). Deliberately not broken down
// by tax rate here - a CGST+SGST pair both carry the same `rate` value
// (e.g. 2.5+2.5 for a real 5% slab), so grouping raw trans_tax_details by
// tax_types.rate would mislabel a 5% slab as two "2.5%" buckets rather
// than showing the true combined rate. A correct per-slab breakdown needs
// per-invoice rate aggregation, not a flat GROUP BY - left as one honest
// total rather than a misleading split.
function get_gstr1_b2c_summary($from_date, $to_date)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$row = db_fetch_row(db_query(
		"SELECT COUNT(*), COALESCE(SUM(dt.ov_amount+dt.ov_freight),0), COALESCE(SUM(dt.ov_gst+dt.ov_freight_tax),0)
		 FROM ".TB_PREF."debtor_trans dt
		 JOIN ".TB_PREF."debtors_master dm ON dm.debtor_no = dt.debtor_no
		 WHERE dt.type=".ST_SALESINVOICE." AND dt.tran_date BETWEEN '$from' AND '$to'
		 AND (dm.tax_id IS NULL OR dm.tax_id = '')"));
	return array(
		'invoice_count' => (int)$row[0],
		'taxable_value' => round((float)$row[1], 2),
		'tax_amount' => round((float)$row[2], 2),
	);
}
