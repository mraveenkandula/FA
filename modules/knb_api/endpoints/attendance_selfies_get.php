<?php
/*
	GET /modules/knb_api/endpoints/attendance_selfies_get.php?date=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "selfies": [ {id, att_date, punch_action, url, uploaded_at}, ... ] }

	Read-only history of the signed-in employee's OWN punch-in/punch-out
	selfies (attendance_selfie_post.php writes these; get_attendance_selfies()
	in modules/knb_api/includes/attendance_selfie_db.inc already existed as
	the natural read counterpart to that write path, but had no endpoint -
	same "wire up existing dead code" situation as add_outlet_photo() had
	before tonight's camera/media pass). Always scoped to the authenticated
	employee's own employee_id - there is no "view someone else's selfies"
	mode here; that's what attendance_inquiry.php (web, HR/admin-facing) is
	for, which shows every employee's selfies via the same underlying table.

	`date` is optional (omit for full history, newest first); when given,
	filters to that one day, matching get_attendance_selfies()'s own
	$att_date param shape.

	`url` is the root-relative file_path (e.g.
	"company/0/images/attendance_selfies/xxx.jpg", per api_upload.inc's
	doc comment on that shape) - directly usable by the mobile app as
	"<baseUrl>/<url>" since ApiConfig.baseUrl already points at this same
	install's origin.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/attendance_selfie_db.inc');
require_once($path_to_root . '/includes/date_functions.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$date_iso = trim((string)@$_GET['date']);
if ($date_iso !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_iso))
	api_error('date must be in YYYY-MM-DD format');

// att_date is a native DATE column - db_escape()'d straight through as ISO,
// no date2sql()/sql2date() involved (that trap only applies to FA's own
// *display*-format date fields, not this one - see stock_verification_get.php's
// doc comment for the general rule this endpoint is deliberately following).
$result = get_attendance_selfies($employee['employee_id'], $date_iso !== '' ? $date_iso : null);

$selfies = array();
while ($row = db_fetch_assoc($result))
{
	$selfies[] = array(
		'id' => (int)$row['id'],
		'att_date' => $row['att_date'],
		'punch_action' => $row['punch_action'],
		'url' => $row['file_path'],
		'uploaded_at' => $row['uploaded_at'],
	);
}

api_json(array('selfies' => $selfies));
