<?php
/*
	GET /modules/knb_api/endpoints/tada_claim_get.php?status=Pending
	Authorization: Bearer <token>
	-> { "claims": [ {id, employee_id, claim_date, category, amount, description,
		status, approved_by, approved_date, claim_type}, ... ] }

	TA/DA (Travel Allowance / Dearness Allowance) MANUAL claim entry - the
	Salesmatic parity item. Modelled directly on expense_claim_get.php (read
	that file first): same table (0_hr_expense_claims), same
	get_expense_claims()/employee-scoping approach, differing only in the
	claim_type filter ('TADA' instead of 'Expense', see
	expense_claim_db.inc's ensure_expense_claim_schema()/claim_type doc
	comment) and the category vocabulary (TA/DA, not
	Travel/Fuel/Food/Accommodation/Other).

	Deliberately NOT built: any "automated calculation based on productivity
	criteria" - that is a financial/payroll-adjacent formula (mileage rates,
	attendance-linked DA slabs, etc.) that needs accountant sign-off, exactly
	the same reasoning docs/techcloud-parity-gap-analysis.md's "Summary and
	suggested priority order" section already gives for deferring Payroll
	processing ("implementing those without review risks corrupting financial
	statements ... a worse outcome than leaving them open with a clear,
	field-level spec"). This endpoint is manual entry only, same risk tier as
	Expense Claims - no GL/financial posting logic anywhere in this path.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/expense_claim_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$status = trim((string)@$_GET['status']);
$result = get_expense_claims($status !== '' ? $status : null, 'TADA');

$claims = array();
while ($row = db_fetch_assoc($result))
	if ((int)$row['employee_id'] === (int)$employee['id'])
		$claims[] = $row;

api_json(array('claims' => $claims));
