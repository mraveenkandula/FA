<?php
/*
	GET /modules/knb_api/endpoints/employee_target_get.php?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "targets": [ {id, sales_employee_id, period_month, target_amount,
		notes, actual_sales, achievement_pct, incentive_pct}, ... ] }

	Wraps get_sales_targets() from modules/knb_sales_ext/manage/target_db.inc,
	scoped to the authenticated employee - the "Target vs Achievement"
	Salesmatic feature. date_from/date_to filter on period_month (a DATE
	column storing the first of each target month); omit both for all of
	this employee's targets.

	Achievement: modules/knb_sales_ext/inquiry/incentive_inquiry.php already
	computes this exact figure (get_actual_sales() + get_incentive_pct(),
	read in full before writing this endpoint) - net invoiced sales for
	customers assigned to the employee via 0_customer_distribution, for the
	target's period month, matched against 0_knb_incentive_tiers. Per the
	task's own "check whether an achievement inquiry already exists" - one
	does, so this reuses that exact logic rather than inventing a new
	calculation. It could not be reused by inclusion: both functions are
	defined inline inside incentive_inquiry.php, a page script gated by
	session.inc/page_security/page() that executes immediately at include
	time (not a reusable db.inc), and this task is explicitly scoped to
	modules/knb_api/ only - modules/knb_sales_ext/ was read for reference
	but not modified to extract them. api_get_actual_sales()/
	api_get_incentive_pct() below are therefore a deliberate, documented
	copy of that page's two functions (same SQL, same
	customer_distribution/debtor_trans/knb_incentive_tiers tables,
	sign-convention comment preserved), not new business logic.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_sales_ext/manage/target_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$date_from = trim((string)@$_GET['date_from']);
$date_to = trim((string)@$_GET['date_to']);
if ($date_from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from))
	api_error('date_from must be in YYYY-MM-DD format');
if ($date_to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))
	api_error('date_to must be in YYYY-MM-DD format');

/*
	Copied from incentive_inquiry.php's get_actual_sales() - see file
	header. ABS() sidesteps FA's debtor_trans sign convention for the
	invoice type specifically (matches the pattern core's own sales
	reports use), restricted to ST_SALESINVOICE only.
*/
function api_get_actual_sales($sales_employee_id, $period_month)
{
	$period_end = date('Y-m-t', strtotime($period_month));
	$sql = "SELECT SUM(ABS(dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_freight_tax - dt.ov_discount)) AS total
		FROM ".TB_PREF."debtor_trans dt
		JOIN ".TB_PREF."customer_distribution cd ON cd.debtor_no = dt.debtor_no
		WHERE dt.type = ".ST_SALESINVOICE."
		AND cd.sales_employee_id = ".db_escape($sales_employee_id)."
		AND dt.tran_date >= ".db_escape($period_month)."
		AND dt.tran_date <= ".db_escape($period_end);
	$result = db_query($sql, "could not get actual sales");
	$row = db_fetch($result);
	return (float)$row['total'];
}

/*
	Copied from incentive_inquiry.php's get_incentive_pct() - see file
	header.
*/
function api_get_incentive_pct($achievement_pct)
{
	$sql = "SELECT incentive_pct FROM ".TB_PREF."knb_incentive_tiers
		WHERE !inactive AND ".db_escape($achievement_pct)." >= min_achievement_pct
		AND ".db_escape($achievement_pct)." <= max_achievement_pct
		ORDER BY min_achievement_pct DESC LIMIT 1";
	$result = db_query($sql, "could not get incentive tier");
	$row = db_fetch($result);
	return $row ? (float)$row['incentive_pct'] : 0;
}

$result = get_sales_targets($employee['id'], $date_from !== '' ? $date_from : null, $date_to !== '' ? $date_to : null);

$targets = array();
while ($row = db_fetch_assoc($result))
{
	$target_amount = (float)$row['target_amount'];
	$actual_sales = api_get_actual_sales($employee['id'], $row['period_month']);
	$achievement_pct = $target_amount > 0 ? round(($actual_sales / $target_amount) * 100, 2) : null;

	$targets[] = array(
		'id' => (int)$row['id'],
		'sales_employee_id' => (int)$row['sales_employee_id'],
		'period_month' => $row['period_month'],
		'target_amount' => $target_amount,
		'notes' => $row['notes'],
		'actual_sales' => $actual_sales,
		'achievement_pct' => $achievement_pct,
		'incentive_pct' => $achievement_pct !== null ? api_get_incentive_pct($achievement_pct) : null,
	);
}

api_json(array('targets' => $targets));
