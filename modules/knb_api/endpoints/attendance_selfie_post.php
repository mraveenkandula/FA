<?php
/*
	POST /modules/knb_api/endpoints/attendance_selfie_post.php
	Authorization: Bearer <token>
	multipart/form-data:
		selfie   - the photo file (required, camera-captured JPEG/PNG on the
		           client - this endpoint itself can't tell a gallery pick
		           from a live camera shot, so "must be a live photo" is
		           enforced client-side by attendance_screen.dart using
		           ImageSource.camera only, same trust boundary GPS capture
		           already has for this app)
		date     - YYYY-MM-DD, optional, defaults to today
		action   - "in" | "out", optional, defaults to "in"
	-> { "saved": true, "id": N }

	Selfie-validated attendance: a photo captured alongside a punch
	(attendance_punch.php), NOT part of that endpoint itself. The app calls
	this as a second, best-effort request after the punch succeeds (see
	attendance_screen.dart) - punch success matters more than the photo,
	the same philosophy already used for geo_tracking_post.php alongside
	punches. This endpoint therefore does not touch 0_attendance at all and
	never fails a punch; a failure here only fails this one request.

	Unlike every other POST endpoint in this API, this one is NOT JSON body
	- api_input() (which decodes php://input as JSON) cannot see multipart
	fields, so form fields are read from $_POST directly, same as core FA's
	own file-upload pages (e.g. inventory/manage/items.php) do.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/api_upload.inc');
require_once(__DIR__ . '/../includes/attendance_selfie_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$date = trim((string)@$_POST['date']);
if ($date === '')
	$date = date('Y-m-d');
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
	api_error('date must be in YYYY-MM-DD format');

$action = strtolower(trim((string)@$_POST['action']));
if ($action === '')
	$action = 'in';
elseif (!in_array($action, array('in', 'out'), true))
	api_error("action must be 'in' or 'out'");

$file_path = api_handle_photo_upload('selfie', 'attendance_selfies', 'emp'.$employee['id']);

$id = save_attendance_selfie($employee['id'], $date, $action, $file_path);

api_json(array('saved' => true, 'id' => $id), 201);
