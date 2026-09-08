<?php
/*
	GET /modules/knb_api/endpoints/journey_plan_get.php?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "plans": [ {id, sales_employee_id, plan_date, beat_id, beat_name,
		remarks, town_id, town_name, town_lat, town_lng}, ... ] }

	Wraps get_journey_plans() from modules/knb_sales_ext/manage/journey_plan_db.inc,
	scoped to the authenticated employee. Defaults to today only (a
	beat/PJP plan is inherently day-scoped); pass date_from/date_to for a
	wider range.

	town_lat/town_lng (previously not returned - see the "Journey Plan
	geofencing" gap in docs/mobile-app-reverse-engineering.md) are added
	here via beat_id -> 0_sales_beats.town_id -> 0_sales_towns.lat/lng.
	Confirmed via DESCRIBE + SELECT against local dev DB: 3126 of 3601 rows
	in 0_sales_towns have a real, non-zero lat/lng (not empty/unused as the
	original deferral worried) - a real join over real data, not a schema
	change. get_journey_plans() itself wasn't touched (out of scope, and
	its LEFT JOINs already read cleanly); this endpoint adds one more LEFT
	JOIN of its own from beat_id, same pattern as the two the underlying
	function already does for employee/beat name.

	Note: 0_sales_towns has no per-town radius/area column (unlike the
	reference TechCloud app's "Town Radius (km)"/"Area (sq km)" fields) -
	that's a genuinely different, richer schema on their side, not
	something recoverable from ours. The mobile client applies a single
	fixed default radius (see journey_plan_screen.dart) rather than
	fabricating a per-town radius figure that doesn't exist in this data.
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
$beat_ids = array();
while ($row = db_fetch_assoc($result))
{
	$plans[] = $row;
	if (!empty($row['beat_id']))
		$beat_ids[(int)$row['beat_id']] = true;
}

// One extra lookup query for all beats referenced by this result set,
// rather than a per-row query - $beat_ids is typically tiny (a handful of
// distinct beats per employee/date range).
$town_by_beat = array();
if (!empty($beat_ids))
{
	$sql = "SELECT b.id AS beat_id, t.id AS town_id, t.name AS town_name, t.lat, t.lng
		FROM ".TB_PREF."sales_beats b
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = b.town_id
		WHERE b.id IN (".implode(',', array_map('intval', array_keys($beat_ids))).")";
	$town_result = db_query($sql, "could not get beat town coordinates");
	while ($town_row = db_fetch_assoc($town_result))
		$town_by_beat[(int)$town_row['beat_id']] = $town_row;
}

foreach ($plans as &$plan)
{
	$town = !empty($plan['beat_id']) ? @$town_by_beat[(int)$plan['beat_id']] : null;
	$plan['town_id'] = $town && $town['town_id'] !== null ? (int)$town['town_id'] : null;
	$plan['town_name'] = $town ? $town['town_name'] : null;
	$plan['town_lat'] = $town && $town['lat'] !== null && $town['lat'] !== '' ? (float)$town['lat'] : null;
	$plan['town_lng'] = $town && $town['lng'] !== null && $town['lng'] !== '' ? (float)$town['lng'] : null;
}
unset($plan);

api_json(array('plans' => $plans));
