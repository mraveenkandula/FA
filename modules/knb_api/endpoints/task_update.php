<?php
/*
	POST /modules/knb_api/endpoints/task_update.php
	Authorization: Bearer <token>
	{ "id": N, "status": "In Progress" | "Completed", "remarks": "..." }
	-> { "saved": true }

	Wraps set_task_status() from modules/knb_hrm/manage/task_entry_db.inc.
	Mirrors task_update.php's own access-control convention (an employee -
	there, whoever is selected in the office-staff web UI; here, the
	authenticated token holder - may only update a task assigned to them):
	get_task($id) is read first and the task's assigned_to is checked
	against $employee['id'] before calling set_task_status(), so a client
	can't move someone else's task by guessing an id.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/task_entry_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
if (empty($input['id']))
	api_error('id is required');
$id = (int)$input['id'];

$status = trim((string)@$input['status']);
$allowed_statuses = array('Pending', 'In Progress', 'Completed');
if (!in_array($status, $allowed_statuses, true))
	api_error('status must be one of: '.implode(', ', $allowed_statuses));

$task = get_task($id);
if (!$task)
	api_error('Task not found', 404);
if ((int)$task['assigned_to'] !== (int)$employee['id'])
	api_error('You may only update your own tasks', 403);

$remarks = @$input['remarks'];

set_task_status($id, $status, $remarks);

api_json(array('saved' => true));
