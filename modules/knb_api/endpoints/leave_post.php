<?php
/*
	POST /modules/knb_api/endpoints/leave_post.php
	Authorization: Bearer <token>
	{ "leave_type_id": N, "leave_from": "YYYY-MM-DD", "leave_to": "YYYY-MM-DD",
	  "leave_days": 1.5, "session_type": "Full Day", "reason": "..." }
	-> { "saved": true }

	Wraps add_leave_entry() from modules/knb_hrm/manage/leave_entry_db.inc,
	always for the authenticated employee. New requests always land as
	status 'Pending' by the underlying function itself - approval happens
	separately via leave_approval.php, unchanged by this endpoint.

	NOTE: unlike every other date field in this API, add_leave_entry()
	converts leave_from/leave_to with date2sql() ITSELF, which expects a
	date in the install's configured *display* format, not ISO - see
	api_date_to_display() in api_bootstrap.inc for why/how this is
	converted here without touching leave_entry_db.inc.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/leave_entry_db.inc');
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/leave_types_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();

if (empty($input['leave_type_id']))
	api_error('leave_type_id is required');
$leave_type_id = (int)$input['leave_type_id'];
if (!get_leave_type($leave_type_id))
	api_error('Unknown leave_type_id', 404);

$leave_from = trim((string)@$input['leave_from']);
$leave_to = trim((string)@$input['leave_to']);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $leave_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $leave_to))
	api_error('leave_from/leave_to are required in YYYY-MM-DD format');

if (!isset($input['leave_days']) || !is_numeric($input['leave_days']) || (float)$input['leave_days'] <= 0)
	api_error('leave_days must be a positive number');
$leave_days = (float)$input['leave_days'];

$session_type = trim((string)@$input['session_type']);
$allowed_sessions = array('Full Day', 'First Half', 'Second Half');
if ($session_type !== '' && !in_array($session_type, $allowed_sessions, true))
	api_error('session_type must be one of: '.implode(', ', $allowed_sessions));

$reason = @$input['reason'];

add_leave_entry(
	$employee['id'],
	$leave_type_id,
	api_date_to_display($leave_from),
	api_date_to_display($leave_to),
	$leave_days,
	$session_type !== '' ? $session_type : null,
	$reason
);

api_json(array('saved' => true), 201);
