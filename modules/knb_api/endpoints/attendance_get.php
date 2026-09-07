<?php
/*
	GET /modules/knb_api/endpoints/attendance_get.php?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
	Authorization: Bearer <token>
	-> { "records": [ {employee_id, att_date, status, check_in, check_out, remarks, emp_code, first_name, last_name}, ... ] }

	Wraps get_attendance_report() from modules/knb_hrm/manage/attendance_db.inc,
	always scoped to the authenticated employee (never a client-supplied
	employee id). Defaults to the last 30 days through today when no range
	is given.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/attendance_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$date_from = trim((string)@$_GET['date_from']);
$date_to = trim((string)@$_GET['date_to']);
if ($date_to === '')
	$date_to = date('Y-m-d');
if ($date_from === '')
	$date_from = date('Y-m-d', strtotime('-30 days', strtotime($date_to)));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))
	api_error('date_from/date_to must be in YYYY-MM-DD format');

$result = get_attendance_report($date_from, $date_to, $employee['id']);
$records = array();
while ($row = db_fetch_assoc($result))
	$records[] = $row;

api_json(array('records' => $records));
