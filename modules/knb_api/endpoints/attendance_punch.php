<?php
/*
	POST /modules/knb_api/endpoints/attendance_punch.php
	Authorization: Bearer <token>
	{ "action": "in" | "out", "date": "YYYY-MM-DD" (optional, defaults to today),
	  "lat": 17.12345, "lng": 78.12345, "remarks": "..." }
	-> { "status": "Present", "check_in": "09:03:00", "check_out": null, "date": "2026-09-07" }

	Wraps modules/knb_hrm/manage/attendance_db.inc's save_attendance()/
	get_attendance_for_date() - the same functions attendance_entry.php's
	bulk office-staff entry screen uses, but scoped to a single employee
	(the authenticated token holder) and driven by a punch action instead
	of a free-text HH:MM field. Punching "in" sets check_in to now and
	marks status Present (preserving any existing check_out for the day);
	punching "out" sets check_out to now (preserving any existing
	check_in). save_attendance() is a REPLACE, so each punch re-saves the
	full day's row.

	PJP geofence enforcement (the user's own request: "attendance should be
	punched only near to beat location, it should show message as out of
	PJP location"): lat/lng are now accepted params (same required-pair
	validation as geo_tracking_post.php). If get_journey_plans() returns ANY
	entry for this employee/date whose beat resolves to a 0_sales_towns row
	with real coordinates ("checkable" plans - a plan with no beat, or a
	beat with no town/coords, can't be checked and is skipped, same
	null-handling as journey_plan_get.php), the submitted lat/lng must be
	within PJP_GEOFENCE_RADIUS_KM of at least one of them or the punch is
	rejected. The 5.0 km figure is not invented here - it's the exact same
	documented default journey_plan_screen.dart already uses client-side
	(_geofenceRadiusKm), for the same reason: 0_sales_towns has no per-town
	radius column to read a real one from.

	If there is no plan for today, or no plan resolves to real coordinates,
	the punch is allowed through unchecked - an employee assigned no beat
	for the day (or an office-staff employee with no PJP concept at all)
	must still be able to punch in/out; this mirrors the reference app's
	geofencing being a per-visit Journey Plan feature, not a blanket
	Attendance requirement. This IS a real enforcement point (unlike the
	advisory-only outlet-proximity check elsewhere, which was deliberately
	advisory because outlet GPS data is sparse/unreliable) - beat/town
	coordinates are far more complete, and the client-side check in
	attendance_screen.dart is only a fast pre-network UX layer; a
	modified/rooted client that skips that check, or omits lat/lng
	outright, still gets rejected here.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/attendance_db.inc');
require_once(dirname(__DIR__, 2) . '/knb_sales_ext/manage/journey_plan_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$action = strtolower(trim((string)@$input['action']));
if (!in_array($action, array('in', 'out'), true))
	api_error("action must be 'in' or 'out'");

$date = trim((string)@$input['date']);
if ($date === '')
	$date = date('Y-m-d');
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
	api_error('date must be in YYYY-MM-DD format');

if (!isset($input['lat']) || $input['lat'] === '' || !isset($input['lng']) || $input['lng'] === '')
	api_error('lat and lng are required');
$lat = (float)$input['lat'];
$lng = (float)$input['lng'];
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)
	api_error('lat/lng out of range');

const PJP_GEOFENCE_RADIUS_KM = 5.0;

/* Haversine great-circle distance in km - matches the mobile client's use
   of Geolocator.distanceBetween() (also great-circle) closely enough for a
   multi-km geofence radius; no external dependency needed for this. */
function pjp_distance_km($lat1, $lng1, $lat2, $lng2)
{
	$earth_radius_km = 6371.0;
	$d_lat = deg2rad($lat2 - $lat1);
	$d_lng = deg2rad($lng2 - $lng1);
	$a = sin($d_lat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lng / 2) ** 2;
	return $earth_radius_km * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

$plan_result = get_journey_plans($employee['id'], $date, $date);
$plans = array();
$beat_ids = array();
while ($row = db_fetch_assoc($plan_result))
{
	$plans[] = $row;
	if (!empty($row['beat_id']))
		$beat_ids[(int)$row['beat_id']] = true;
}

$town_by_beat = array();
if (!empty($beat_ids))
{
	$sql = "SELECT b.id AS beat_id, t.lat, t.lng
		FROM ".TB_PREF."sales_beats b
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = b.town_id
		WHERE b.id IN (".implode(',', array_map('intval', array_keys($beat_ids))).")";
	$town_result = db_query($sql, "could not get beat town coordinates");
	while ($town_row = db_fetch_assoc($town_result))
		$town_by_beat[(int)$town_row['beat_id']] = $town_row;
}

$checkable_towns = array();
foreach ($plans as $plan)
{
	if (empty($plan['beat_id']))
		continue;
	$town = @$town_by_beat[(int)$plan['beat_id']];
	if (!$town || $town['lat'] === null || $town['lat'] === '' || $town['lng'] === null || $town['lng'] === '')
		continue;
	$checkable_towns[] = array('lat' => (float)$town['lat'], 'lng' => (float)$town['lng']);
}

if (!empty($checkable_towns))
{
	$nearest_km = null;
	foreach ($checkable_towns as $town)
	{
		$dist_km = pjp_distance_km($lat, $lng, $town['lat'], $town['lng']);
		if ($nearest_km === null || $dist_km < $nearest_km)
			$nearest_km = $dist_km;
	}
	if ($nearest_km > PJP_GEOFENCE_RADIUS_KM)
		api_error('Out of PJP location - you are '.round($nearest_km, 1).' km from your assigned beat. '
			.'Move within '.round(PJP_GEOFENCE_RADIUS_KM).' km of it to punch '.$action.'.', 403);
}

$remarks = @$input['remarks'];
$now = date('H:i:s');

$existing = get_attendance_for_date($date);
$cur = isset($existing[$employee['id']]) ? $existing[$employee['id']] : null;

$check_in = $cur['check_in'] ?? null;
$check_out = $cur['check_out'] ?? null;

if ($action === 'in')
	$check_in = $now;
else // 'out'
	$check_out = $now;

$status = 'Present';
save_attendance($date, $employee['id'], $status, $check_in, $check_out, $remarks !== null ? $remarks : @$cur['remarks']);

api_json(array(
	'date' => $date,
	'status' => $status,
	'check_in' => $check_in,
	'check_out' => $check_out,
));
