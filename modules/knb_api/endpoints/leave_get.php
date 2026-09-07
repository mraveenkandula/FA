<?php
/*
	GET /modules/knb_api/endpoints/leave_get.php?status=Pending
	Authorization: Bearer <token>
	-> { "requests": [ {id, employee_id, leave_type_id, leave_type_name, leave_type_code,
	                      leave_from, leave_to, leave_days, session_type, reason, status,
	                      rejection_reason, applied_date}, ... ],
	     "leave_types": [ {id, name, code, days_confirmed, days_probation}, ... ] }

	Wraps get_leave_entries() from modules/knb_hrm/manage/leave_entry_db.inc
	(which already supports an employee_id filter, unlike expense claims -
	used directly here, scoped to the authenticated employee) and
	get_all_leave_types() from leave_types_db.inc so a mobile client can
	populate a leave-type picker in the same call.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/leave_entry_db.inc');
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/leave_types_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$status = trim((string)@$_GET['status']);

$result = get_leave_entries($status !== '' ? $status : null, $employee['id']);
$requests = array();
while ($row = db_fetch_assoc($result))
	$requests[] = $row;

$types_result = get_all_leave_types(false);
$leave_types = array();
while ($row = db_fetch_assoc($types_result))
	$leave_types[] = $row;

api_json(array('requests' => $requests, 'leave_types' => $leave_types));
