<?php
/*
	GET /modules/knb_api/endpoints/sales_order_get.php?customer_id=N&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD&limit=20
	Authorization: Bearer <token>
	-> { "orders": [ {order_no, reference, ord_date, delivery_date, deliver_to,
		delivery_address, total, debtor_no, name, branch_code,
		lines: [{stk_code, description, quantity, unit_price, discount_percent,
			qty_done}, ...]}, ... ] }

	Lists core FA sales orders (0_sales_orders, trans_type=ST_SALESORDER) -
	NOT a knb_* custom module. Reuses get_sales_order_header() and
	get_sales_order_details() from sales/includes/db/sales_order_db.inc
	exactly as sales_order_entry.php's own Cart::read() does, rather than
	re-deriving the header JOIN (customer/branch/sales-type/tax-group) by
	hand.

	customer_id is required and is the only filter. NOT employee-scoped:
	0_sales_orders in this install's local dev DB does carry a "user_id"
	column with real-looking values (confirmed via DESCRIBE + sample rows),
	but that is TechCloud's own imported production schema/data (same
	caveat as the approve_status columns documented in
	techcloud-parity-gap-analysis.md's "Pending GL Transactions" section) -
	add_sales_order() in this fork (read in full before writing this
	endpoint) never populates user_id/person_type/role_id/area_code/
	beat_id/etc. for orders it creates. Using that column to mean "orders
	this employee created" would only ever match the one-time imported
	historical rows, never anything created going forward - so per the task
	brief's own fallback instruction, this endpoint filters by customer_id
	instead of inventing an employee-linkage that doesn't functionally
	exist for this fork's own write path. (No sales-order-creation endpoint
	exists yet in this fork either - see mobile-app-reverse-engineering.md's
	note on sales_order_post.php being deferred - so today every row this
	could return is TechCloud-imported data.)
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once($path_to_root . '/sales/includes/db/sales_order_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$customer_id = trim((string)@$_GET['customer_id']);
if ($customer_id === '' || !is_numeric($customer_id))
	api_error('customer_id is required');

$date_from = trim((string)@$_GET['date_from']);
$date_to = trim((string)@$_GET['date_to']);
if ($date_from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from))
	api_error('date_from must be in YYYY-MM-DD format');
if ($date_to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))
	api_error('date_to must be in YYYY-MM-DD format');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
if ($limit <= 0 || $limit > 200)
	$limit = 20;

$sql = "SELECT order_no FROM ".TB_PREF."sales_orders
	WHERE trans_type = ".ST_SALESORDER."
	AND debtor_no = ".db_escape($customer_id);
if ($date_from !== '')
	$sql .= " AND ord_date >= ".db_escape($date_from);
if ($date_to !== '')
	$sql .= " AND ord_date <= ".db_escape($date_to);
$sql .= " ORDER BY order_no DESC LIMIT ".(int)$limit;

$result = db_query($sql, "could not retrieve sales orders");

$orders = array();
while ($row = db_fetch_assoc($result))
{
	$header = get_sales_order_header($row['order_no'], ST_SALESORDER);
	if (!$header)
		continue;

	$lines = array();
	$details = get_sales_order_details($row['order_no'], ST_SALESORDER);
	while ($line = db_fetch_assoc($details))
		$lines[] = $line;

	$orders[] = array(
		'order_no' => (int)$header['order_no'],
		'reference' => $header['reference'],
		'ord_date' => $header['ord_date'],
		'delivery_date' => $header['delivery_date'],
		'deliver_to' => $header['deliver_to'],
		'delivery_address' => $header['delivery_address'],
		'total' => (float)$header['total'],
		'debtor_no' => (int)$header['debtor_no'],
		'name' => $header['name'],
		'branch_code' => (int)$header['branch_code'],
		'lines' => $lines,
	);
}

api_json(array('orders' => $orders));
