<?php
/*
	POST /modules/knb_api/endpoints/geo_tracking_post.php
	Authorization: Bearer <token>
	{ "lat": 17.12345, "lng": 78.12345, "accuracy_m": 12, "remarks": "..." }
	-> { "saved": true }

	Wraps save_geo_ping() from modules/knb_hrm/manage/geo_tracking_db.inc -
	a single location point logged against the authenticated employee.
	This is the live-tracking punch-in-to-punch-out log the reference app's
	add_att_location.php covers; the mobile client is expected to call this
	repeatedly (e.g. every N minutes) while an employee is clocked in.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/geo_tracking_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
if (!isset($input['lat']) || $input['lat'] === '' || !isset($input['lng']) || $input['lng'] === '')
	api_error('lat and lng are required');

$lat = (float)$input['lat'];
$lng = (float)$input['lng'];
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)
	api_error('lat/lng out of range');

$accuracy_m = isset($input['accuracy_m']) && $input['accuracy_m'] !== '' ? (int)$input['accuracy_m'] : null;
$remarks = @$input['remarks'];

save_geo_ping($employee['id'], $lat, $lng, $accuracy_m, $remarks);

api_json(array('saved' => true), 201);
