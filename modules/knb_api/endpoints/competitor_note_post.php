<?php
/*
	POST /modules/knb_api/endpoints/competitor_note_post.php
	Authorization: Bearer <token>
	multipart/form-data:
		competitor_name  - required, e.g. "Amul"
		note             - optional free text
		debtor_no        - optional (an outlet this sighting is tied to)
		photo            - optional photo file
	-> { "saved": true, "id": N }

	Competitor-activity capture: "I saw competitor X doing Y [at outlet Z],
	here's a photo". Deliberately NOT "market intelligence" - there is no
	analysis, aggregation, or dashboard here, only capture + a plain list
	(competitor_notes_get.php). That matches the task split: a lightweight
	analytics layer over this same table may be built later by a different
	slice of work, but is explicitly out of scope here.

	Accepts multipart (not JSON) even though photo is optional, so the
	client always posts the same way whether or not a photo is attached -
	same $_POST/$_FILES reasoning as attendance_selfie_post.php/
	outlet_photo_post.php.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/api_upload.inc');
require_once(__DIR__ . '/../includes/competitor_note_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$competitor_name = trim((string)@$_POST['competitor_name']);
if ($competitor_name === '')
	api_error('competitor_name is required');

$note = trim((string)@$_POST['note']);

$debtor_no = trim((string)@$_POST['debtor_no']);
if ($debtor_no !== '')
{
	if (!is_numeric($debtor_no))
		api_error('debtor_no must be numeric');
	$exists = db_query("SELECT debtor_no FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape((int)$debtor_no),
		"could not check outlet");
	if (!db_fetch_assoc($exists))
		api_error('Outlet/customer not found', 404);
	$debtor_no = (int)$debtor_no;
}
else
{
	$debtor_no = null;
}

$file_path = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE)
	$file_path = api_handle_photo_upload('photo', 'competitor_notes', 'emp'.$employee['id']);

$id = add_competitor_note($employee['id'], $debtor_no, $competitor_name, $note !== '' ? $note : null, $file_path);

api_json(array('saved' => true, 'id' => $id), 201);
