<?php
/*
	GET /modules/knb_api/endpoints/visit_frequency_get.php?days=30
	Authorization: Bearer <token>
	-> { "window_days": 30, "date_from": "...", "date_to": "...",
		"outlets": [ {debtor_no, name, order_count, last_order_date}, ... ] }

	READ-ONLY "visit frequency" - for the authenticated employee, how often
	each outlet in their beat has actually been visited, over a recent
	window (days=7 or 30, default 30; anything else falls back to 30).

	Data source decision (per task brief - "pick whichever real data exists,
	and say clearly which you used"): 0_knb_journey_plan (see
	pjp_adherence_get.php) only carries beat_id, not a specific outlet/
	debtor_no - a plan/visit is beat-scoped, not outlet-scoped, confirmed by
	reading journey_plan_db.inc/the table's own schema in full. There is no
	per-outlet visit record anywhere in this data. So this uses sales order
	dates (0_sales_orders, trans_type=ST_SALESORDER) as the per-outlet
	proxy instead - real, dated, outlet-level activity, same table
	sales_order_get.php already reads.

	Outlet scope: 0_customer_distribution.sales_employee_id ties an outlet
	(debtor_no) to the rep who owns it - the exact same join
	employee_target_get.php's copied get_actual_sales() already uses
	(cd.debtor_no = dt.debtor_no AND cd.sales_employee_id = ...), just
	against sales_orders instead of debtor_trans invoices here. Per
	sales_order_get.php's own doc comment, 0_sales_orders has no reliable
	per-order employee/creator column in this fork (add_sales_order() never
	populates user_id) - so "this employee's outlets" comes from
	customer_distribution, and every order for those outlets in the window
	counts, regardless of who entered it. That's consistent with there being
	no order-creation endpoint in this fork yet (all order rows are
	TechCloud-imported historical data) - this reports "how often was this
	assigned outlet actually ordered from", not "how often did I personally
	sell to it".

	Verified against local dev data: 0_customer_distribution.sales_employee_id
	is populated for only 3 of 3461 rows - a real data-entry gap, not a bug
	here (documented in mobile-app-reverse-engineering.md's write-up of this
	feature). Most employees will see an empty or near-empty outlet list
	until outlets are actually assigned to reps in customer_distribution.
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

$sql = "SELECT cd.debtor_no, d.name,
		COUNT(so.order_no) AS order_count,
		MAX(so.ord_date) AS last_order_date
	FROM ".TB_PREF."customer_distribution cd
	JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
	LEFT JOIN ".TB_PREF."sales_orders so ON so.debtor_no = cd.debtor_no
		AND so.trans_type = ".ST_SALESORDER."
		AND so.ord_date >= ".db_escape($date_from)."
		AND so.ord_date <= ".db_escape($date_to)."
	WHERE cd.sales_employee_id = ".db_escape($employee['id'])."
	GROUP BY cd.debtor_no, d.name
	ORDER BY order_count DESC, d.name ASC";
$result = db_query($sql, "could not get visit frequency");

$outlets = array();
while ($row = db_fetch_assoc($result))
{
	$outlets[] = array(
		'debtor_no' => (int)$row['debtor_no'],
		'name' => $row['name'],
		'order_count' => (int)$row['order_count'],
		'last_order_date' => $row['last_order_date'],
	);
}

api_json(array(
	'window_days' => $days,
	'date_from' => $date_from,
	'date_to' => $date_to,
	'outlets' => $outlets,
));
