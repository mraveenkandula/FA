<?php
/*
	POST /modules/knb_api/endpoints/outlet_photo_post.php
	Authorization: Bearer <token>
	multipart/form-data:
		customer_id  - debtor_no (required)
		photo_type   - "Board" | "Chiller" | "Display" | "Other" (required -
		               matches the four values documented in this install's
		               own schema comment on 0_knb_outlet_photos.photo_type,
		               see modules/knb_api/sql/knb_api.sql)
		photo        - the photo file (required)
	-> { "saved": true, "id": N }

	Wires up add_outlet_photo() (modules/knb_api/includes/outlet_db.inc),
	which existed with zero callers before this endpoint - this is the
	merchandising-photo ("Board/Chiller/Display" placement proof) and
	chiller-asset-tracking ("Other" catch-all, e.g. an asset condition
	photo) half of the Salesmatic marketing-sheet gap, built as photo
	documentation against the existing table rather than a separate
	asset/serial-number ledger the schema gives no evidence of.

	Not JSON body - same reasoning as attendance_selfie_post.php's doc
	comment: multipart fields come from $_POST/$_FILES, not api_input().
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/api_upload.inc');
require_once(__DIR__ . '/../includes/outlet_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$customer_id = trim((string)@$_POST['customer_id']);
if ($customer_id === '' || !is_numeric($customer_id))
	api_error('customer_id is required and must be numeric');

$photo_type = trim((string)@$_POST['photo_type']);
$allowed_types = array('Board', 'Chiller', 'Display', 'Other');
if (!in_array($photo_type, $allowed_types, true))
	api_error('photo_type must be one of: '.implode(', ', $allowed_types));

$exists = db_query("SELECT debtor_no FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape((int)$customer_id),
	"could not check outlet");
if (!db_fetch_assoc($exists))
	api_error('Outlet/customer not found', 404);

$file_path = api_handle_photo_upload('photo', 'outlet_photos', 'outlet'.(int)$customer_id);

$id = add_outlet_photo((int)$customer_id, $photo_type, $file_path, $employee['id']);

api_json(array('saved' => true, 'id' => $id), 201);
