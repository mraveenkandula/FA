<?php
/*
	GET /modules/knb_api/endpoints/grievance_get.php
	Authorization: Bearer <token>
	-> { "grievances": [ {id, name, email, subject, message, submitted_at}, ... ] }

	Wraps get_grievances() from modules/knb_hrm/inquiry/hr_records_db.inc.
	0_knb_grievances has no employee_id column (verified against local dev
	DB - see includes/grievance_db.inc for the full note), so this filters
	the result set down to rows whose `email` matches the authenticated
	employee's own email or official_email, the closest identifying column
	the table actually has. An employee with neither email column set on
	their hr_employees row will always get an empty list here - a real
	limitation of the existing schema, not a bug in this endpoint.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(dirname(__DIR__, 2) . '/knb_hrm/inquiry/hr_records_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$my_emails = array_filter(array(
	strtolower(trim((string)@$employee['email'])),
	strtolower(trim((string)@$employee['official_email'])),
));

$result = get_grievances();
$grievances = array();
while ($row = db_fetch_assoc($result))
	if (in_array(strtolower(trim($row['email'])), $my_emails, true))
		$grievances[] = $row;

api_json(array('grievances' => $grievances));
