<?php
/*
	GET /modules/knb_api/endpoints/expense_claim_get.php?status=Pending
	Authorization: Bearer <token>
	-> { "claims": [ {id, employee_id, claim_date, category, amount, description, status, approved_by, approved_date}, ... ] }

	Wraps get_expense_claims() from modules/knb_hrm/manage/expense_claim_db.inc.
	That function only filters by status, not employee - it's built for the
	office-side approval screen which lists everyone's claims. This
	endpoint reuses it as-is and filters the result set down to the
	authenticated employee's own rows in PHP, rather than modifying the
	underlying query (out of scope for this module).

	claim_type is hardcoded to 'Expense' here (get_expense_claims()'s second
	arg, added for TA/DA - see that function's own doc comment) so this
	endpoint's contract is unchanged: TA/DA claims (claim_type='TADA', see
	tada_claim_get.php) never appear in the existing mobile Expense Claims
	screen, same as before that column existed.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/expense_claim_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$status = trim((string)@$_GET['status']);
$result = get_expense_claims($status !== '' ? $status : null, 'Expense');

$claims = array();
while ($row = db_fetch_assoc($result))
	if ((int)$row['employee_id'] === (int)$employee['id'])
		$claims[] = $row;

api_json(array('claims' => $claims));
