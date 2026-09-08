<?php
/*
	GET /modules/knb_api/endpoints/customer_receipts_get.php?customer_id=N&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "receipts": [ {trans_no, tran_date, customer_name, debtor_no,
		reference, amount, entered_by}, ... ] }

	Read-only listing of customer payment receipts (0_debtor_trans rows of
	type ST_CUSTPAYMENT, written by write_customer_payment() in
	sales/includes/db/payment_db.inc), wrapping the existing
	get_customer_payments_for_inquiry() (sales/includes/db/payment_db.inc,
	added earlier for TechCloud parity - see
	gl/inquiry/customer_payments_inquiry.php and
	docs/techcloud-parity-gap-analysis.md) rather than duplicating that SQL.

	This is the "Customer Receipts" half of the pair deferred in
	docs/mobile-app-reverse-engineering.md as "same FA session/$Refs/
	audit-trail blocker as sales_order_post.php". Re-verified specifically
	for receipts (not assumed from the sales-order case): *creating* a
	receipt still hits that exact blocker -
	sales/customer_payments.php's read_customer_data() calls
	$Refs->get_next(ST_CUSTPAYMENT, ...), and includes/references.inc's
	_parse_next() unconditionally reads
	$_SESSION['wa_current_user']->user/pos (line ~98-99, no isset() guard,
	regardless of whether this install's ST_CUSTPAYMENT refline pattern
	actually uses {UU}/{P}) - so creation is NOT built here, same
	conclusion, independently confirmed. But the read side has no such
	dependency at all: get_customer_payments_for_inquiry() is a plain
	SELECT against 0_debtor_trans/0_debtors_master/0_audit_trail/0_users,
	no $Refs, no session - genuinely safe to expose, so it is.

	No employee scoping column exists to filter "receipts this employee
	collected" (0_debtor_trans has no employee_id/sales_employee_id column,
	confirmed via DESCRIBE) - same situation already documented for
	sales_order_get.php, which takes customer_id as its filter for the same
	reason. This endpoint does the same: customer_id is optional (omit for
	all customers' receipts in the date range), reference/date range filter
	the same way the existing gl/inquiry/customer_payments_inquiry.php page
	does.

	"Distributor Payment Approvals" (the reference app's separate
	Pending/Approved/Rejected workflow screen) is deliberately NOT built
	as part of this endpoint: 0_debtor_trans.approve_status does exist in
	this install's schema, but confirmed via direct SELECT that every
	single ST_CUSTPAYMENT row (all 118 in local dev DB) has
	approve_status=0 - core FA's own write_customer_payment() never sets
	it to anything else, so there is no real Pending/Approved/Rejected
	variation to list; building an "approval" screen against a column that
	never actually varies for this transaction type would be exactly the
	kind of guessed-at, no-real-backing feature this task explicitly
	avoids. The plain receipts listing below is the genuinely-supported
	subset of that reference screen.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once($path_to_root . '/sales/includes/db/payment_db.inc');
require_once($path_to_root . '/includes/date_functions.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$date_from_iso = trim((string)@$_GET['date_from']);
$date_to_iso = trim((string)@$_GET['date_to']);
if ($date_from_iso !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from_iso))
	api_error('date_from must be in YYYY-MM-DD format');
if ($date_to_iso !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to_iso))
	api_error('date_to must be in YYYY-MM-DD format');

// get_customer_payments_for_inquiry()'s $from/$to are passed straight into
// date2sql() internally, which (like add_leave_entry()) expects the
// install's configured *display* date format, not ISO - convert the same
// way api_date_to_display() already does for the other endpoints that hit
// this exact pattern.
$date_from = $date_from_iso !== '' ? api_date_to_display($date_from_iso) : '';
$date_to = $date_to_iso !== '' ? api_date_to_display($date_to_iso) : '';

$customer_id = trim((string)@$_GET['customer_id']);
$ref = trim((string)@$_GET['reference']);

$result = get_customer_payments_for_inquiry($ref, $date_from, $date_to, false);

$receipts = array();
while ($row = db_fetch_assoc($result))
{
	if ($customer_id !== '' && (string)$row['debtor_no'] !== $customer_id)
		continue;
	$receipts[] = array(
		'trans_no' => (int)$row['trans_no'],
		// tran_date is a native MySQL DATE column - already plain ISO
		// YYYY-MM-DD as returned by db_fetch_assoc(); sql2date() would
		// wrongly convert this to the install's *display* format instead
		// (see the same fix/comment in stock_verification_get.php).
		'tran_date' => $row['tran_date'],
		'customer_name' => trim($row['counterparty']),
		'debtor_no' => (int)$row['debtor_no'],
		'reference' => $row['reference'],
		'amount' => (float)$row['amount'],
		'entered_by' => $row['entered_by'],
	);
}

api_json(array('receipts' => $receipts));
