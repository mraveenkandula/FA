<?php
/*
	POST /modules/knb_api/endpoints/tada_claim_post.php
	Authorization: Bearer <token>
	{ "claim_date": "YYYY-MM-DD", "category": "TA", "amount": 450.00,
	  "description": "Route/reason, e.g. Adoni <-> Kurnool beat visit" }
	-> { "saved": true }

	TA/DA MANUAL claim entry - see tada_claim_get.php's header for the full
	"why this reuses hr_expense_claims instead of a new table" reasoning and
	the "why no automated formula" deferral note (same accountant-sign-off
	caution as Payroll processing in techcloud-parity-gap-analysis.md).

	Wraps add_expense_claim() with claim_type='TADA', always for the
	authenticated employee. Always lands as 'Pending' (same as Expense
	Claims) - approval happens via the existing office-side
	expense_claim_approval.php screen, which now also shows a Type column
	(see that file) so an approver can tell TA/DA and Expense claims apart in
	the same pending-claims queue.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/expense_claim_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$claim_date = trim((string)@$input['claim_date']);
if ($claim_date === '')
	$claim_date = date('Y-m-d');
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $claim_date))
	api_error('claim_date must be in YYYY-MM-DD format');

$category = trim((string)@$input['category']);
$allowed_categories = array('TA', 'DA');
if ($category !== '' && !in_array($category, $allowed_categories, true))
	api_error('category must be one of: '.implode(', ', $allowed_categories));

if (!isset($input['amount']) || !is_numeric($input['amount']) || (float)$input['amount'] <= 0)
	api_error('amount must be a positive number');
$amount = (float)$input['amount'];

$description = @$input['description'];

add_expense_claim($employee['id'], $claim_date, $category !== '' ? $category : null, $amount, $description, 'TADA');

api_json(array('saved' => true), 201);
