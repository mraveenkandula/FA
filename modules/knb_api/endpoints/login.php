<?php
/*
	POST /modules/knb_api/endpoints/login.php
	{ "username": "...", "password": "..." }
	-> { "token": "...", "employee_id": N, "name": "..." }
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$username = trim(@$input['username']);
$password = @$input['password'];

if ($username === '' || $password === null || $password === '')
	api_error('username and password are required');

$result = db_query("SELECT * FROM ".TB_PREF."hr_employees
	WHERE mobile_username=".db_escape($username)." AND !inactive",
	"could not look up employee");
$employee = db_fetch_assoc($result);

if (!$employee || !$employee['mobile_password_hash'] || !password_verify($password, $employee['mobile_password_hash']))
	api_error('Invalid username or password', 401);

$token = bin2hex(random_bytes(32));
db_query("INSERT INTO ".TB_PREF."knb_api_tokens (employee_id, token, created_at, expires_at)
	VALUES (".db_escape($employee['id']).",".db_escape($token).", NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))",
	"could not create api token");

api_json(array(
	'token' => $token,
	'employee_id' => (int)$employee['id'],
	'name' => trim($employee['first_name'].' '.$employee['last_name']),
));
