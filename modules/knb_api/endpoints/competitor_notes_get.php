<?php
/*
	GET /modules/knb_api/endpoints/competitor_notes_get.php
	GET /modules/knb_api/endpoints/competitor_notes_get.php?debtor_no=N
	GET /modules/knb_api/endpoints/competitor_notes_get.php?mine=1
	Authorization: Bearer <token>
	-> { "notes": [ {id, employee_id, employee_name, debtor_no, outlet_name,
		competitor_name, note, file_path, created_at}, ... ] }

	Plain listing, newest first - no analysis/aggregation (see the doc
	comment on competitor_note_post.php for why that's deliberately out of
	scope here). debtor_no filters to one outlet's sightings; mine=1 filters
	to the authenticated employee's own notes; both, either, or neither may
	be given (the unfiltered case lists every rep's notes, same
	office-wide-by-default shape stock_verification_get.php's
	get_all_verifications() uses before its own employee filter).
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/competitor_note_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$debtor_no = trim((string)@$_GET['debtor_no']);
if ($debtor_no !== '' && !is_numeric($debtor_no))
	api_error('debtor_no must be numeric');

$mine = !empty($_GET['mine']);

$result = get_competitor_notes($mine ? (int)$employee['id'] : null, $debtor_no !== '' ? (int)$debtor_no : null);

$notes = array();
while ($row = db_fetch_assoc($result))
{
	$notes[] = array(
		'id' => (int)$row['id'],
		'employee_id' => (int)$row['employee_id'],
		'employee_name' => trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')),
		'debtor_no' => isset($row['debtor_no']) ? (int)$row['debtor_no'] : null,
		'outlet_name' => $row['outlet_name'],
		'competitor_name' => $row['competitor_name'],
		'note' => $row['note'],
		'file_path' => $row['file_path'],
		// created_at is a native MySQL DATETIME column - passed through
		// as-is, same reasoning as outlet_photos_get.php's uploaded_date.
		'created_at' => $row['created_at'],
	);
}

api_json(array('notes' => $notes));
