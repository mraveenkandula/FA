<?php
/*
	GET /modules/knb_api/endpoints/geo_tracking_get.php?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "points": [ {employee_id, recorded_at, latitude, longitude, accuracy_m, remarks}, ... ] }

	Wraps get_geo_report() from modules/knb_hrm/manage/geo_tracking_db.inc,
	scoped to the authenticated employee. Defaults to today only.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/geo_tracking_db.inc');

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

$result = get_geo_report($date_from, $date_to, $employee['id']);
$points = array();
while ($row = db_fetch_assoc($result))
	$points[] = $row;

api_json(array('points' => $points));
