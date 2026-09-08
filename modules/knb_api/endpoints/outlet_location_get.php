<?php
/*
	GET /modules/knb_api/endpoints/outlet_location_get.php?customer_id=N
	Authorization: Bearer <token>
	-> { "debtor_no": N, "name": "...", "gps_lat": 17.5 | null, "gps_lng": 78.5 | null }

	Surfaces a single outlet's stored GPS coordinates (customer_distribution.
	gps_lat/gps_lng, set by outlet_create.php's create_outlet() when the
	outlet was registered via the New Outlet screen) so the mobile order
	screen can show a "near/far from outlet" indicator at order time (see
	orders_screen.dart's outlet-location check, and outlet_db.inc's
	get_outlet_location() doc comment for the schema this reads).

	gps_lat/gps_lng come back null for outlets that predate GPS capture, or
	that were created directly in core FA rather than via the New Outlet
	screen - that's expected, not an error; the client is expected to treat
	null as "no location on file", not as (0,0) or a failed check. There's
	no "list my outlets" endpoint in this fork (see orders_repository.dart's
	doc comment) so, matching sales_order_get.php/stock_items_get.php, this
	takes a single customer_id rather than returning a list.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
api_require_auth();
require_once(__DIR__ . '/../includes/outlet_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$customer_id = trim((string)@$_GET['customer_id']);
if ($customer_id === '' || !is_numeric($customer_id))
	api_error('customer_id is required');

$outlet = get_outlet_location($customer_id);
if (!$outlet)
	api_error('Outlet not found', 404);

api_json(array(
	'debtor_no' => (int)$outlet['debtor_no'],
	'name' => $outlet['name'],
	'gps_lat' => $outlet['gps_lat'] !== null ? (float)$outlet['gps_lat'] : null,
	'gps_lng' => $outlet['gps_lng'] !== null ? (float)$outlet['gps_lng'] : null,
));
