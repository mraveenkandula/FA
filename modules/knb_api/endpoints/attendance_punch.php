<?php
/*
	POST /modules/knb_api/endpoints/attendance_punch.php
	Authorization: Bearer <token>
	{ "action": "in" | "out", "date": "YYYY-MM-DD" (optional, defaults to today), "remarks": "..." }
	-> { "status": "Present", "check_in": "09:03:00", "check_out": null, "date": "2026-09-07" }

	Wraps modules/knb_hrm/manage/attendance_db.inc's save_attendance()/
	get_attendance_for_date() - the same functions attendance_entry.php's
	bulk office-staff entry screen uses, but scoped to a single employee
	(the authenticated token holder) and driven by a punch action instead
	of a free-text HH:MM field. Punching "in" sets check_in to now and
	marks status Present (preserving any existing check_out for the day);
	punching "out" sets check_out to now (preserving any existing
	check_in). save_attendance() is a REPLACE, so each punch re-saves the
	full day's row.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/attendance_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$action = strtolower(trim((string)@$input['action']));
if (!in_array($action, array('in', 'out'), true))
	api_error("action must be 'in' or 'out'");

$date = trim((string)@$input['date']);
if ($date === '')
	$date = date('Y-m-d');
elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
	api_error('date must be in YYYY-MM-DD format');

$remarks = @$input['remarks'];
$now = date('H:i:s');

$existing = get_attendance_for_date($date);
$cur = isset($existing[$employee['id']]) ? $existing[$employee['id']] : null;

$check_in = $cur['check_in'] ?? null;
$check_out = $cur['check_out'] ?? null;

if ($action === 'in')
	$check_in = $now;
else // 'out'
	$check_out = $now;

$status = 'Present';
save_attendance($date, $employee['id'], $status, $check_in, $check_out, $remarks !== null ? $remarks : @$cur['remarks']);

api_json(array(
	'date' => $date,
	'status' => $status,
	'check_in' => $check_in,
	'check_out' => $check_out,
));
