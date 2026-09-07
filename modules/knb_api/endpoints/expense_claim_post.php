<?php
/*
	POST /modules/knb_api/endpoints/expense_claim_post.php
	Authorization: Bearer <token>
	{ "claim_date": "YYYY-MM-DD", "category": "Travel", "amount": 250.00, "description": "..." }
	-> { "saved": true }

	Wraps add_expense_claim() from modules/knb_hrm/manage/expense_claim_db.inc,
	always for the authenticated employee. New claims are always inserted
	with status 'Pending' by the underlying function itself, same as the
	web form (expense_claim_entry.php) - approval happens separately via
	expense_claim_approval.php, unchanged by this endpoint.
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
$allowed_categories = array('Travel', 'Fuel', 'Food', 'Accommodation', 'Other');
if ($category !== '' && !in_array($category, $allowed_categories, true))
	api_error('category must be one of: '.implode(', ', $allowed_categories));

if (!isset($input['amount']) || !is_numeric($input['amount']) || (float)$input['amount'] <= 0)
	api_error('amount must be a positive number');
$amount = (float)$input['amount'];

$description = @$input['description'];

add_expense_claim($employee['id'], $claim_date, $category !== '' ? $category : null, $amount, $description);

api_json(array('saved' => true), 201);
