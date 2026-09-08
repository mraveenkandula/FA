<?php
/*
	GET /modules/knb_api/endpoints/outlet_photos_get.php?customer_id=N
	Authorization: Bearer <token>
	-> { "photos": [ {id, debtor_no, photo_type, file_path, uploaded_by_employee_id,
		uploaded_by_name, uploaded_date}, ... ] }

	Read-only photo history for one outlet, newest first. Wraps
	get_outlet_photos() (modules/knb_api/includes/outlet_db.inc), the read
	counterpart to outlet_photo_post.php.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/outlet_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$customer_id = trim((string)@$_GET['customer_id']);
if ($customer_id === '' || !is_numeric($customer_id))
	api_error('customer_id is required and must be numeric');

$result = get_outlet_photos((int)$customer_id);
$photos = array();
while ($row = db_fetch_assoc($result))
{
	$photos[] = array(
		'id' => (int)$row['id'],
		'debtor_no' => (int)$row['debtor_no'],
		'photo_type' => $row['photo_type'],
		'file_path' => $row['file_path'],
		'uploaded_by_employee_id' => isset($row['uploaded_by_employee_id']) ? (int)$row['uploaded_by_employee_id'] : null,
		'uploaded_by_name' => trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')),
		// uploaded_date is a native MySQL DATETIME column - passed through
		// as-is, same reasoning documented in stock_verification_get.php/
		// customer_receipts_get.php for why sql2date() must NOT be used here.
		'uploaded_date' => $row['uploaded_date'],
	);
}

api_json(array('photos' => $photos));
