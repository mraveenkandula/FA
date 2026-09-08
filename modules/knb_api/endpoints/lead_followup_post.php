<?php
/*
	POST /modules/knb_api/endpoints/lead_followup_post.php
	Authorization: Bearer <token>
	{ "debtor_no": N, "status": "Contacted", "follow_up_date": "YYYY-MM-DD",
	  "notes": "..." }
	-> { "id": N }

	Logs one follow-up touch against a lead and moves its current status
	forward (see lead_db.inc's add_lead_followup()). debtor_no must be a
	real customer (checked below); follow_up_date and notes are both
	optional - a rep can log a bare status change with no scheduled next
	date or notes if that's all there is to record.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/lead_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();

$debtor_no = isset($input['debtor_no']) ? (int)$input['debtor_no'] : 0;
if ($debtor_no <= 0)
	api_error('debtor_no is required');

$status = trim((string)@$input['status']);
if (!in_array($status, knb_lead_valid_statuses(), true))
	api_error('status must be one of: '.implode(', ', knb_lead_valid_statuses()));

$follow_up_date = trim((string)@$input['follow_up_date']);
if ($follow_up_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow_up_date))
	api_error('follow_up_date must be in YYYY-MM-DD format');

$notes = trim((string)@$input['notes']);

$exists = db_query("SELECT debtor_no FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($debtor_no),
	"could not check customer");
if (!db_fetch($exists))
	api_error('No such customer', 404);

$id = add_lead_followup($debtor_no, $employee['id'], $status, $follow_up_date !== '' ? $follow_up_date : null,
	$notes !== '' ? $notes : null);

api_json(array('id' => (int)$id), 201);
