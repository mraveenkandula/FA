<?php
/*
	POST /modules/knb_api/endpoints/journey_plan_post.php
	Authorization: Bearer <token>
	{ "plan_date": "YYYY-MM-DD", "beat_id": N (optional), "remarks": "..." }
	-> { "saved": true }

	Wraps add_journey_plan() from modules/knb_sales_ext/manage/journey_plan_db.inc,
	always for the authenticated employee (sales_employee_id is never
	client-supplied). add_journey_plan() is insert-only (no update-by-id in
	the underlying db.inc), matching journey_plan_entry.php's own behavior -
	"create/update a plan entry" here means adding a new plan row for the
	given date, same as the web form.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_sales_ext/manage/journey_plan_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$plan_date = trim((string)@$input['plan_date']);
if ($plan_date === '')
	$plan_date = date('Y-m-d');
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan_date))
	api_error('plan_date must be in YYYY-MM-DD format');

$beat_id = isset($input['beat_id']) && $input['beat_id'] !== '' ? (int)$input['beat_id'] : null;
$remarks = @$input['remarks'];

add_journey_plan($employee['id'], $plan_date, $beat_id, $remarks);

api_json(array('saved' => true), 201);
