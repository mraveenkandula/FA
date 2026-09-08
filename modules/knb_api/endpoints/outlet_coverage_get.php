<?php
/*
	GET /modules/knb_api/endpoints/outlet_coverage_get.php?days=30
	Authorization: Bearer <token>
	-> { "window_days": 30, "date_from": "...", "date_to": "...",
		"summary": {total_outlets, covered_outlets, coverage_pct},
		"by_beat": [ {beat_id, beat_name, total_outlets, covered_outlets, coverage_pct}, ... ] }

	READ-ONLY outlet/distributor "coverage" - how many outlets exist in the
	authenticated employee's assigned territory/beat (0_customer_distribution,
	same table visit_frequency_get.php uses - read that endpoint's doc
	comment first for the full data-source reasoning) vs how many have
	actually been ordered from in a recent window (days=7 or 30, default 30).
	"Ordered from" is the same sales-order-dates-as-visit-proxy choice
	visit_frequency_get.php makes, for the same reason (journey_plan is
	beat-level only, no per-outlet record).

	A single query, grouped by beat_id, gives both the per-beat breakdown and
	(summed in PHP below) the overall summary - one simple aggregation, not
	two separate queries against the same rows. Outlets with no beat_id at
	all group under beat_id=null/"Unassigned" rather than being dropped, so
	the summary total always matches the sum of every outlet actually
	assigned to this employee.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
if ($days !== 7 && $days !== 30)
	$days = 30;

$date_to = date('Y-m-d');
$date_from = date('Y-m-d', strtotime($date_to.' -'.($days - 1).' days'));

$sql = "SELECT cd.beat_id, b.name AS beat_name,
		COUNT(DISTINCT cd.debtor_no) AS total_outlets,
		COUNT(DISTINCT CASE WHEN so.order_no IS NOT NULL THEN cd.debtor_no END) AS covered_outlets
	FROM ".TB_PREF."customer_distribution cd
	LEFT JOIN ".TB_PREF."sales_beats b ON b.id = cd.beat_id
	LEFT JOIN ".TB_PREF."sales_orders so ON so.debtor_no = cd.debtor_no
		AND so.trans_type = ".ST_SALESORDER."
		AND so.ord_date >= ".db_escape($date_from)."
		AND so.ord_date <= ".db_escape($date_to)."
	WHERE cd.sales_employee_id = ".db_escape($employee['id'])."
	GROUP BY cd.beat_id, b.name
	ORDER BY b.name IS NULL, b.name ASC";
$result = db_query($sql, "could not get outlet coverage");

$by_beat = array();
$total_outlets = 0;
$covered_outlets = 0;
while ($row = db_fetch_assoc($result))
{
	$total = (int)$row['total_outlets'];
	$covered = (int)$row['covered_outlets'];
	$total_outlets += $total;
	$covered_outlets += $covered;
	$by_beat[] = array(
		'beat_id' => $row['beat_id'] !== null ? (int)$row['beat_id'] : null,
		'beat_name' => $row['beat_name'],
		'total_outlets' => $total,
		'covered_outlets' => $covered,
		'coverage_pct' => $total > 0 ? round(($covered / $total) * 100, 1) : null,
	);
}

api_json(array(
	'window_days' => $days,
	'date_from' => $date_from,
	'date_to' => $date_to,
	'summary' => array(
		'total_outlets' => $total_outlets,
		'covered_outlets' => $covered_outlets,
		'coverage_pct' => $total_outlets > 0 ? round(($covered_outlets / $total_outlets) * 100, 1) : null,
	),
	'by_beat' => $by_beat,
));
