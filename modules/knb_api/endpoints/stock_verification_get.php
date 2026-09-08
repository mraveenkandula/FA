<?php
/*
	GET /modules/knb_api/endpoints/stock_verification_get.php
	GET /modules/knb_api/endpoints/stock_verification_get.php?verification_id=N
	Authorization: Bearer <token>

	No verification_id: -> { "verifications": [ {id, verify_date, loc_code,
		employee_id, first_name, last_name, remarks, finalized,
		adjustment_trans_no}, ... ] } - the authenticated employee's own
		verifications only (started by them), newest first.

	With verification_id: -> { "verification": {...same shape...},
		"items": [ {id, verification_id, stock_id, description, system_qty,
		counted_qty, variance_qty}, ... ] }
		403s if the verification wasn't started by the authenticated employee.

	Wraps modules/knb_stores_ext/manage/stock_verification_db.inc's
	get_all_verifications()/get_verification()/get_verification_items() -
	the "Stock Verification" Salesmatic feature (per-outlet/location physical
	stock count vs system quantity). Deliberately does NOT expose
	finalize_verification(): that function calls $Refs->get_next(ST_INVADJUST, ...)
	which (see includes/references.inc _parse_next()) unconditionally reads
	$_SESSION['wa_current_user']->user/pos - the same session dependency
	documented for sales_order_post.php in
	docs/mobile-app-reverse-engineering.md. The count-entry/list part below
	has no such dependency (verified by reading every function in
	stock_verification_db.inc other than finalize_verification()) and is
	safe to expose as-is; finalizing (posting the inventory adjustment)
	stays a web-only action for an office user, same as before.

	get_all_verifications() itself has no employee filter (it's built for
	an office-wide list) - this filters to the authenticated employee's own
	rows in PHP, the same pattern expense_claim_get.php uses for
	get_expense_claims().
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_stores_ext/manage/stock_verification_db.inc');
require_once($path_to_root . '/includes/db/inventory_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

function verification_row_out($row)
{
	return array(
		'id' => (int)$row['id'],
		// verify_date is a native MySQL DATE column - already plain ISO
		// YYYY-MM-DD as returned by db_fetch_assoc(), same as every other
		// endpoint in this API (see leave_get.php, which passes its own
		// DATE columns through unconverted for the same reason). sql2date()
		// would wrongly convert this to the install's *display* format
		// instead (verified: produces "00/00/0000"-style output) - that
		// conversion is only correct when feeding a value back into a core
		// FA function that expects display format, like get_qoh_on_date()
		// below, not for this API's own JSON contract.
		'verify_date' => $row['verify_date'],
		'loc_code' => $row['loc_code'],
		'employee_id' => isset($row['employee_id']) ? (int)$row['employee_id'] : null,
		'employee_name' => trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')),
		'remarks' => $row['remarks'],
		'finalized' => (bool)$row['finalized'],
		'adjustment_trans_no' => isset($row['adjustment_trans_no']) ? (int)$row['adjustment_trans_no'] : null,
	);
}

$verification_id = trim((string)@$_GET['verification_id']);

if ($verification_id !== '')
{
	if (!is_numeric($verification_id))
		api_error('verification_id must be numeric');

	$verification = get_verification((int)$verification_id);
	if (!$verification)
		api_error('Verification not found', 404);
	if ((int)$verification['employee_id'] !== (int)$employee['id'])
		api_error('This verification was not started by you', 403);

	// get_verification() (unlike get_all_verifications()) doesn't join
	// hr_employees - fill in first_name/last_name from the already-verified
	// authenticated employee row instead of a second query, since the
	// 403 check above guarantees they're the same person.
	$verification['first_name'] = $employee['first_name'];
	$verification['last_name'] = $employee['last_name'];

	$items = array();
	$result = get_verification_items((int)$verification_id);
	while ($row = db_fetch_assoc($result))
	{
		$items[] = array(
			'id' => (int)$row['id'],
			'verification_id' => (int)$row['verification_id'],
			'stock_id' => $row['stock_id'],
			'description' => $row['description'],
			'system_qty' => (float)$row['system_qty'],
			'counted_qty' => (float)$row['counted_qty'],
			'variance_qty' => (float)$row['variance_qty'],
		);
	}

	api_json(array('verification' => verification_row_out($verification), 'items' => $items));
}

$loc_code = trim((string)@$_GET['loc_code']);
$result = get_all_verifications($loc_code !== '' ? $loc_code : null);
$verifications = array();
while ($row = db_fetch_assoc($result))
{
	if ((int)$row['employee_id'] !== (int)$employee['id'])
		continue;
	$verifications[] = verification_row_out($row);
}

api_json(array('verifications' => $verifications));
