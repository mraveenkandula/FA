<?php
/*
	POST /modules/knb_api/endpoints/stock_verification_post.php
	Authorization: Bearer <token>

	{ "action": "start", "verify_date": "YYYY-MM-DD", "loc_code": "LC001", "remarks": "..." }
	-> { "verification_id": N }

	{ "action": "add_item", "verification_id": N, "stock_id": "123", "counted_qty": 45.5 }
	-> { "system_qty": 40, "counted_qty": 45.5, "variance_qty": 5.5 }

	Wraps start_verification()/add_verification_item()
	(modules/knb_stores_ext/manage/stock_verification_db.inc), always for
	the authenticated employee (employee_id is never client-supplied) -
	same trust model as the rest of this API. See stock_verification_get.php's
	header for why finalize_verification() (posting the inventory
	adjustment) is deliberately NOT exposed here - it needs a real FA
	session ($Refs->get_next()), same blocker as sales_order_post.php.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_stores_ext/manage/stock_verification_db.inc');
require_once($path_to_root . '/includes/db/inventory_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$action = strtolower(trim((string)@$input['action']));

if ($action === 'start')
{
	$verify_date = trim((string)@$input['verify_date']);
	if ($verify_date === '')
		$verify_date = date('Y-m-d');
	elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $verify_date))
		api_error('verify_date must be in YYYY-MM-DD format');

	$loc_code = trim((string)@$input['loc_code']);
	if ($loc_code === '')
		api_error('loc_code is required');
	$loc_check = db_query("SELECT loc_code FROM ".TB_PREF."locations WHERE loc_code=".db_escape($loc_code),
		"could not validate location");
	if (!db_fetch($loc_check))
		api_error('Unknown loc_code', 404);

	$remarks = @$input['remarks'];

	// start_verification() calls date2sql($verify_date) internally, which
	// (like add_leave_entry(), see leave_post.php/api_date_to_display()'s
	// own doc comment) expects the install's configured *display* date
	// format, not ISO - convert the same way, verified locally: passing
	// ISO straight through silently produced verify_date=0000-00-00.
	$id = start_verification(api_date_to_display($verify_date), $loc_code, $employee['id'], $remarks);
	api_json(array('verification_id' => (int)$id), 201);
}
elseif ($action === 'add_item')
{
	$verification_id = trim((string)@$input['verification_id']);
	if ($verification_id === '' || !is_numeric($verification_id))
		api_error('verification_id is required');

	$verification = get_verification((int)$verification_id);
	if (!$verification)
		api_error('Verification not found', 404);
	if ((int)$verification['employee_id'] !== (int)$employee['id'])
		api_error('This verification was not started by you', 403);
	if ($verification['finalized'])
		api_error('This verification has already been finalized');

	$stock_id = trim((string)@$input['stock_id']);
	if ($stock_id === '')
		api_error('stock_id is required');
	$stock_check = db_query("SELECT stock_id FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id),
		"could not validate stock item");
	if (!db_fetch($stock_check))
		api_error('Unknown stock_id', 404);

	if (!isset($input['counted_qty']) || !is_numeric($input['counted_qty']))
		api_error('counted_qty must be a number');
	$counted_qty = (float)$input['counted_qty'];
	if ($counted_qty < 0)
		api_error('counted_qty cannot be negative');

	add_verification_item((int)$verification_id, $stock_id, $counted_qty);

	// add_verification_item() doesn't return the row it just inserted -
	// re-derive the same system_qty/variance it computed internally so the
	// client can show immediate feedback without a second round-trip.
	$system_qty = get_qoh_on_date($stock_id, $verification['loc_code'], sql2date($verification['verify_date']));
	api_json(array(
		'system_qty' => (float)$system_qty,
		'counted_qty' => $counted_qty,
		'variance_qty' => $counted_qty - (float)$system_qty,
	), 201);
}
else
{
	api_error("action must be 'start' or 'add_item'");
}
