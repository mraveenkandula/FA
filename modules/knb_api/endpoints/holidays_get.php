<?php
/*
	GET /modules/knb_api/endpoints/holidays_get.php
	Authorization: Bearer <token>
	-> { "holidays": [ {id, name, holiday_date}, ... ] }

	Wraps get_all_holidays() from modules/knb_hrm/manage/holidays_db.inc.
	Company-wide calendar, not employee-scoped (matches TechCloud's
	holiday_details.php) - still requires a valid bearer token like every
	other endpoint, just no per-employee filtering of the result set.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/holidays_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$result = get_all_holidays(false);
$holidays = array();
while ($row = db_fetch_assoc($result))
	$holidays[] = $row;

api_json(array('holidays' => $holidays));
