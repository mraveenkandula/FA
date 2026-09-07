<?php
/*
	GET /modules/knb_api/endpoints/stock_items_get.php?customer_id=N&location=LC001&search=text
	Authorization: Bearer <token>
	-> { "items": [ {stock_id, description, long_description, units, mb_flag,
		price, currency, sales_type_id, qty_on_hand}, ... ] }

	Backs the "Stock position-based ordering" Salesmatic feature - lets a
	mobile client see item names/prices/stock levels before calling
	sales_order_get.php or (once built) a sales-order-creation endpoint.

	customer_id is required: item prices depend on the customer's assigned
	sales_type (price list) and currency (core FA's price-list mechanism -
	see get_price() in sales/includes/sales_db.inc), exactly as
	sales_order_entry.php's add_to_order()/get_customer_details_to_order()
	resolve prices once a customer is selected. There is no
	customer-independent "the" price for an item.

	location (optional loc_code, e.g. "LC001"): scopes qty_on_hand to one
	stock location via get_qoh_on_date(). Omitted = qty_on_hand summed
	across all locations (get_qoh_on_date()'s own behaviour when $location
	is null).

	search (optional): case-insensitive substring match against stock_id or
	description.

	The base item list (stock_id/description/units/mb_flag, filtered to
	non-inactive, sellable, non-fixed-asset items) is a direct SELECT
	against stock_master - no existing function returns exactly this shape
	(get_items() in inventory/includes/db/items_db.inc only filters by the
	fixed_asset column, not inactive/no_sale; get_items_search() is closer
	but is wired to $_GET['description']/$_GET['parent'] search-box
	semantics, not a plain list). Price and quantity - the two fields with
	real business logic - reuse the existing, already-correct functions
	exactly as core FA's own item/order screens do: get_price()
	(sales/includes/sales_db.inc, the same price-list resolution
	sales_order_entry.php's add_to_order() uses) and get_qoh_on_date()
	(includes/db/inventory_db.inc, the same running-balance calculation
	inventory/inquiry/stock_status.php uses).
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once($path_to_root . '/sales/includes/sales_db.inc');
require_once($path_to_root . '/includes/db/inventory_db.inc');
require_once($path_to_root . '/sales/includes/db/customers_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$customer_id = trim((string)@$_GET['customer_id']);
if ($customer_id === '' || !is_numeric($customer_id))
	api_error('customer_id is required');

$customer = get_customer($customer_id);
if (!$customer)
	api_error('Customer not found', 404);

$currency = $customer['curr_code'];
$sales_type_id = $customer['sales_type'];

$location = trim((string)@$_GET['location']);
$search = trim((string)@$_GET['search']);

$sql = "SELECT stock_id, description, long_description, units, mb_flag
	FROM ".TB_PREF."stock_master
	WHERE inactive = 0 AND no_sale = 0 AND mb_flag != 'F'";
if ($search !== '')
	$sql .= " AND (stock_id LIKE ".db_escape('%'.$search.'%')."
		OR description LIKE ".db_escape('%'.$search.'%').")";
$sql .= " ORDER BY description";

$result = db_query($sql, "could not retrieve stock items");

$items = array();
while ($row = db_fetch_assoc($result))
{
	// get_price()'s $date defaults to new_doc_date(), which reads/writes a
	// sticky date from $_SESSION['wa_current_user'] - fatals with no
	// session (verified locally). Passing today's date explicitly (the
	// only sane default for a stateless API call) skips that branch
	// entirely. get_price() -> get_exchange_rate_from_home_currency() ->
	// get_last_exchange_rate() internally calls date2sql() on this date,
	// which expects the install's configured *display* date format
	// (verified locally: MM/DD/YYYY), not ISO - passing ISO directly here
	// silently mis-parsed and broke the exchange-rate lookup (confirmed
	// via curl: fataled in get_exchange_rate_from_home_currency() because
	// no rate matched). Reuses api_date_to_display() (api_bootstrap.inc),
	// the same ISO->display conversion already relied on for
	// add_leave_entry(), rather than a second one-off conversion.
	$row['price'] = get_price($row['stock_id'], $currency, $sales_type_id, null, api_date_to_display(date('Y-m-d')));
	$row['currency'] = $currency;
	$row['sales_type_id'] = $sales_type_id;
	$row['qty_on_hand'] = get_qoh_on_date($row['stock_id'], $location !== '' ? $location : null);
	$items[] = $row;
}

api_json(array('items' => $items));
