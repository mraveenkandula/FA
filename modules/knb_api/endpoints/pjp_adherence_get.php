<?php
/*
	GET /modules/knb_api/endpoints/pjp_adherence_get.php?days=7
	Authorization: Bearer <token>
	-> { "days": [ {plan_date, planned_count, visited_count, adherence_pct}, ... ],
		"summary": {days, date_from, date_to, total_planned, total_visited, adherence_pct} }

	READ-ONLY "effective monitoring of PJP" (Permanent Journey Plan) - plan-vs-
	actual adherence over the last `days` days (7 or 30; anything else falls
	back to 7), for the authenticated employee.

	Reuses 0_knb_journey_plan (the same table journey_plan_get.php/
	journey_plan_db.inc already read/wrote) directly rather than a new table -
	this is pure reporting over existing data, no new schema needed.

	Adherence definition, matched exactly to how journey_plan_screen.dart's
	own "mark visited" already works (read that screen's doc comment first):
	add_journey_plan() is insert-only, so "visited" is represented by a
	second plan row for the same beat_id/date with remarks containing
	"visited" (case-insensitive) - there is no separate visited/status column
	to flip. So per day: planned_count = distinct beat_id among all plan rows
	that day (a plan with no beat_id can't be matched against a visited
	follow-up by beat_id, so those are excluded from the ratio - same
	limitation the mobile screen's own distance/geofencing feature already
	has for beat-less plans); visited_count = distinct beat_id among rows
	that day whose remarks contains "visited". adherence_pct is
	visited_count/planned_count*100, rounded to 1 decimal, or null for a day
	with no beat-scoped plans (avoids a misleading 0% for "nothing was
	planned" vs a genuine "planned but not visited").
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
if ($days !== 7 && $days !== 30)
	$days = 7;

$date_to = date('Y-m-d');
$date_from = date('Y-m-d', strtotime($date_to.' -'.($days - 1).' days'));

$sql = "SELECT plan_date,
		COUNT(DISTINCT CASE WHEN beat_id IS NOT NULL THEN beat_id END) AS planned_count,
		COUNT(DISTINCT CASE WHEN beat_id IS NOT NULL AND LOWER(remarks) LIKE '%visited%' THEN beat_id END) AS visited_count
	FROM ".TB_PREF."knb_journey_plan
	WHERE sales_employee_id = ".db_escape($employee['id'])."
	AND plan_date >= ".db_escape($date_from)."
	AND plan_date <= ".db_escape($date_to)."
	GROUP BY plan_date
	ORDER BY plan_date DESC";
$result = db_query($sql, "could not get journey plan adherence");

$days_out = array();
$total_planned = 0;
$total_visited = 0;
while ($row = db_fetch_assoc($result))
{
	$planned = (int)$row['planned_count'];
	$visited = (int)$row['visited_count'];
	$total_planned += $planned;
	$total_visited += $visited;
	$days_out[] = array(
		'plan_date' => $row['plan_date'],
		'planned_count' => $planned,
		'visited_count' => $visited,
		'adherence_pct' => $planned > 0 ? round(($visited / $planned) * 100, 1) : null,
	);
}

api_json(array(
	'days' => $days_out,
	'summary' => array(
		'days' => $days,
		'date_from' => $date_from,
		'date_to' => $date_to,
		'total_planned' => $total_planned,
		'total_visited' => $total_visited,
		'adherence_pct' => $total_planned > 0 ? round(($total_visited / $total_planned) * 100, 1) : null,
	),
));
