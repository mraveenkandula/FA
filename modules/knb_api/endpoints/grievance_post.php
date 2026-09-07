<?php
/*
	POST /modules/knb_api/endpoints/grievance_post.php
	Authorization: Bearer <token>
	{ "subject": "...", "message": "..." }
	-> { "id": N }

	Wraps the new add_grievance() in modules/knb_api/includes/grievance_db.inc
	(0_knb_grievances had no write path before this - see that file's
	header comment). name/email are always taken from the authenticated
	employee's own hr_employees row, never client-supplied, so a grievance
	can't be filed under someone else's identity. Falls back to the
	employee's emp_code for `name` if first/last name are both blank, and
	requires at least one of email/official_email to be set (matches the
	filtering grievance_get.php relies on to show it back to them).
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/grievance_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$subject = trim((string)@$input['subject']);
$message = trim((string)@$input['message']);
if ($subject === '' || $message === '')
	api_error('subject and message are required');

$email = trim((string)@$employee['email']) ?: trim((string)@$employee['official_email']);
if ($email === '')
	api_error('Your employee record has no email on file - a grievance cannot be filed without one to identify it by later', 422);

$name = trim($employee['first_name'].' '.$employee['last_name']);
if ($name === '')
	$name = $employee['emp_code'];

$id = add_grievance($name, $email, $subject, $message);

api_json(array('id' => (int)$id), 201);
