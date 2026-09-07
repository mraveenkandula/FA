<?php
/*
	GET /modules/knb_api/endpoints/journey_plan_get.php?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "plans": [ {id, sales_employee_id, plan_date, beat_id, beat_name, remarks}, ... ] }

	Wraps get_journey_plans() from modules/knb_sales_ext/manage/journey_plan_db.inc,
	scoped to the authenticated employee. Defaults to today only (a
	beat/PJP plan is inherently day-scoped); pass date_from/date_to for a
	wider range.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_sales_ext/manage/journey_plan_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$date_from = trim((string)@$_GET['date_from']);
$date_to = trim((string)@$_GET['date_to']);
if ($date_to === '')
	$date_to = date('Y-m-d');
if ($date_from === '')
	$date_from = $date_to;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))
	api_error('date_from/date_to must be in YYYY-MM-DD format');

$result = get_journey_plans($employee['id'], $date_from, $date_to);
$plans = array();
while ($row = db_fetch_assoc($result))
	$plans[] = $row;

api_json(array('plans' => $plans));
