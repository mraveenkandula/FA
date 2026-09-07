<?php
/*
	GET /modules/knb_api/endpoints/task_get.php?status=Pending
	Authorization: Bearer <token>
	-> { "tasks": [ {id, assigned_to, assigned_by, title, description, due_date,
	                  priority, status, remarks, created_date, completed_date,
	                  first_name, last_name, assigned_by_first_name, assigned_by_last_name}, ... ] }

	Wraps get_tasks() from modules/knb_hrm/manage/task_entry_db.inc, scoped
	to tasks assigned to the authenticated employee.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/manage/task_entry_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$status = trim((string)@$_GET['status']);

$result = get_tasks($employee['id'], $status !== '' ? $status : null);
$tasks = array();
while ($row = db_fetch_assoc($result))
	$tasks[] = $row;

api_json(array('tasks' => $tasks));
