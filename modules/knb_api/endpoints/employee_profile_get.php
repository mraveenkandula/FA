<?php
/*
	GET /modules/knb_api/endpoints/employee_profile_get.php
	Authorization: Bearer <token>
	-> { "profile": {id, emp_code, first_name, last_name, gender, department_id,
	                   designation_id, mobile, email, dob, hire_date, inactive,
	                   employment_status, pan_no, aadhaar_no, bank_name,
	                   bank_account_number, ifsc_code, pf_no, esi_no, uan_no,
	                   father_name, mother_name, blood_group, emergency_contact,
	                   official_email, zone_id, branch_name} }

	api_require_auth() already fetches and returns the full hr_employees
	row (it's how the token is validated), so this doesn't even need
	get_employee() from modules/knb_hrm/manage/employee_db.inc - it's the
	same row, just with mobile_username/mobile_password_hash stripped
	before returning it to the client that owns it.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$profile = $employee;
unset($profile['mobile_username'], $profile['mobile_password_hash']);

api_json(array('profile' => $profile));
