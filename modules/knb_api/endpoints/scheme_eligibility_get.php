<?php
/*
	GET /modules/knb_api/endpoints/scheme_eligibility_get.php?customer_id=N
	Authorization: Bearer <token>
	-> { "customer": {debtor_no, name, person_type, territory_id, state},
	     "schemes": [ {scheme_id, brand_name, promotion_type, start_date,
	       end_date, progress_qty, progress_bill_value, slabs: [ {slab_no,
	       slab_label, slab_target_qty, discount_percent, discount_amount,
	       bill_value_threshold, buy_qty, get_qty, scheme_text, achieved}, ... ],
	       best_slab: {...} | null }, ... ] }

	Read-only mobile equivalent of
	modules/knb_schemes/inquiry/schemes_eligibility_inquiry.php, scoped to
	one customer instead of listing every customer against one scheme
	picked from a dropdown. Deliberately reuses that page's exact query
	functions (schemes_db.inc's get_scheme_achievement()/
	get_scheme_bill_value_achievement()/find_best_qualifying_slab()) with
	a $debtor_no filter added to each - not a re-derived copy of that SQL,
	per this task's explicit instruction not to reinvent the eligibility
	calculation.

	Informational only: this returns what schemes exist for the outlet and
	how far along each slab it is, nothing else. No discount is applied to
	any order and nothing is posted to the GL from here or anywhere else in
	this endpoint's call path.

	Scheme scope matching (person_type/territory/state) is against this
	customer's own 0_customer_distribution row - a customer with no such
	row (never created through outlet_create.php, e.g. an older customer
	from before this module existed) simply matches nothing and gets an
	empty schemes list back, not an error.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_schemes/manage/schemes_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if ($customer_id <= 0)
	api_error('customer_id is required');

$result = db_query("SELECT d.debtor_no, d.name, cd.person_type, cd.territory_id, tn.state
		FROM ".TB_PREF."debtors_master d
		LEFT JOIN ".TB_PREF."customer_distribution cd ON cd.debtor_no = d.debtor_no
		LEFT JOIN ".TB_PREF."sales_towns tn ON tn.id = cd.town_id
		WHERE d.debtor_no=".db_escape($customer_id),
	"could not get customer");
$customer = db_fetch_assoc($result);
if (!$customer)
	api_error('No such customer', 404);

$schemes = array();
$scheme_rows = get_active_schemes_for_customer($customer['person_type'], $customer['territory_id'], $customer['state']);
while ($scheme = db_fetch_assoc($scheme_rows))
{
	$slabs = get_scheme_slabs($scheme['id']);
	$type = $scheme['promotion_type'];

	$progress_qty = null;
	$progress_bill_value = null;
	$best_slab = null;
	$threshold_field = 'slab_target_qty';

	if ($type == 'BILL_VALUE')
	{
		$rows = get_scheme_bill_value_achievement($scheme, $customer['debtor_no']);
		$row = db_fetch_assoc($rows); // highest bill_value first (ORDER BY bill_value DESC)
		$progress_bill_value = $row ? (float)$row['bill_value'] : 0.0;
		$threshold_field = 'bill_value_threshold';
		$best_slab = find_best_qualifying_slab($slabs, $progress_bill_value, $threshold_field);
	}
	elseif ($type == 'BUY_GET')
	{
		// Same as the web inquiry page: Buy-Get eligibility needs
		// order-entry-time line matching, not a historical achievement
		// report - slabs are still returned below so the app can show
		// what the promotion offers, just with no computed progress.
	}
	else // QTY_SLAB, PRICE_OFF, PERCENT_OFF
	{
		$rows = get_scheme_achievement($scheme, $customer['debtor_no']);
		$row = db_fetch_assoc($rows);
		$progress_qty = $row ? (float)$row['total_qty'] : 0.0;
		$best_slab = find_best_qualifying_slab($slabs, $progress_qty, $threshold_field);
	}

	$slab_out = array();
	foreach ($slabs as $slab)
	{
		$threshold = isset($slab[$threshold_field]) ? (float)$slab[$threshold_field] : 0;
		$progress = $type == 'BILL_VALUE' ? $progress_bill_value : $progress_qty;
		$slab_out[] = array(
			'slab_no' => (int)$slab['slab_no'],
			'slab_label' => $slab['slab_label'],
			'slab_target_qty' => (float)$slab['slab_target_qty'],
			'discount_percent' => $slab['discount_percent'] !== null ? (float)$slab['discount_percent'] : null,
			'discount_amount' => $slab['discount_amount'] !== null ? (float)$slab['discount_amount'] : null,
			'bill_value_threshold' => $slab['bill_value_threshold'] !== null ? (float)$slab['bill_value_threshold'] : null,
			'buy_qty' => $slab['buy_qty'] !== null ? (float)$slab['buy_qty'] : null,
			'get_qty' => $slab['get_qty'] !== null ? (float)$slab['get_qty'] : null,
			'scheme_text' => $slab['scheme_text'],
			'achieved' => $progress !== null && $threshold > 0 && $progress >= $threshold,
		);
	}

	$schemes[] = array(
		'scheme_id' => (int)$scheme['id'],
		'brand_name' => $scheme['brand_name'],
		'promotion_type' => $type,
		// start_date/end_date are native MySQL DATE columns - already
		// plain ISO YYYY-MM-DD as returned by db_fetch_assoc(), same rule
		// as everywhere else in this API (see stock_verification_get.php's
		// doc comment) - no sql2date() conversion.
		'start_date' => $scheme['start_date'],
		'end_date' => $scheme['end_date'],
		'progress_qty' => $progress_qty,
		'progress_bill_value' => $progress_bill_value,
		'slabs' => $slab_out,
		'best_slab' => $best_slab ? array(
			'slab_no' => (int)$best_slab['slab_no'],
			'scheme_text' => $best_slab['scheme_text'],
		) : null,
	);
}

api_json(array(
	'customer' => array(
		'debtor_no' => (int)$customer['debtor_no'],
		'name' => $customer['name'],
		'person_type' => $customer['person_type'],
		'territory_id' => $customer['territory_id'] !== null ? (int)$customer['territory_id'] : null,
		'state' => $customer['state'],
	),
	'schemes' => $schemes,
));
