<?php
/*
	Lightweight bootstrap for the mobile API. FrontAccounting's normal
	page-load path (includes/session.inc) is built around a cookie-based
	browser login and pulls in the full menu/theme/user object graph - none
	of which a token-authenticated mobile client needs or can use. This
	connects to the DB and brings up just enough of core (db_query/db_escape
	plus a real SysPrefs object, since db_query() itself reads SysPrefs
	flags) to reuse our existing knb_* module functions from API endpoints.

	Every endpoint should include_once this file first, then call
	api_require_auth() before touching any employee-specific data.
*/

if (!defined('TB_PREF'))
	define('TB_PREF', '0_');

$path_to_root = dirname(dirname(dirname(__DIR__)));

if (!defined('VARLIB_PATH'))
	define('VARLIB_PATH', $path_to_root.'/tmp');
if (!defined('VARLOG_PATH'))
	define('VARLOG_PATH', $path_to_root.'/tmp');

// db_escape() calls this - normally defined in includes/session.inc, which
// pulls in the full cookie-login stack we're deliberately not using here.
// This install is UTF-8 throughout, so a fixed-encoding version is enough.
if (!function_exists('html_specials_encode'))
{
	function html_specials_encode($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}
}

include_once($path_to_root . '/includes/db/connect_db.inc');

global $db, $SysPrefs, $Ajax, $sql_queries, $db_connections, $db_last_inserted_id;
$sql_queries = '';
$Ajax = new stdClass();

// a minimal stand-in so db_query()'s own property reads (show_sql/
// sql_trail/go_debug) don't fatal before the real sys_prefs object below
// can be constructed - it needs the DB connection to already exist.
$SysPrefs = new stdClass();
$SysPrefs->show_sql = 0;
$SysPrefs->sql_trail = 0;
$SysPrefs->go_debug = 0;

include($path_to_root . '/config_db.php');
$conn = $db_connections[$def_coy];
$db = mysqli_connect($conn['host'], $conn['dbuser'], $conn['dbpassword'], $conn['dbname'],
	!empty($conn['port']) ? (int)$conn['port'] : 3306);
if (!$db)
{
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(array('error' => 'database connection failed'));
	exit;
}
db_query("SET sql_mode = ''");

include_once($path_to_root . '/includes/prefs/sysprefs.inc');
$SysPrefs = new sys_prefs();
$SysPrefs->refresh();

include_once($path_to_root . '/includes/types.inc');
include_once($path_to_root . '/includes/date_functions.inc');
include_once($path_to_root . '/includes/db/sql_functions.inc');

// db_query()'s own error path (check_db_error(), normally in
// includes/errors.inc) renders an HTML error box and calls end_page() -
// neither makes sense for a JSON API, so this replaces it with a plain
// JSON error response instead of pulling in the HTML error-rendering stack.
if (!function_exists('check_db_error'))
{
	function check_db_error($msg, $sql_statement, $exit_if_error = true, $rollback_if_error = true)
	{
		global $db;
		$db_error = db_error_no();
		if ($db_error != 0)
		{
			if ($rollback_if_error)
				@mysqli_query($db, "rollback");
			if ($exit_if_error)
				api_error($msg.': '.db_error_msg($db), 500);
		}
		return $db_error;
	}
}

function api_json($data, $status = 200)
{
	http_response_code($status);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

function api_error($message, $status = 400)
{
	api_json(array('error' => $message), $status);
}

function api_input()
{
	$raw = file_get_contents('php://input');
	$data = json_decode($raw, true);
	return is_array($data) ? $data : array();
}

/*
	Bearer-token auth against 0_knb_api_tokens. Returns the authenticated
	employee row (from hr_employees) on success; sends a 401 JSON error and
	exits otherwise.
*/
function api_require_auth()
{
	$header = null;
	if (function_exists('getallheaders'))
	{
		$headers = getallheaders();
		foreach ($headers as $k => $v)
			if (strtolower($k) == 'authorization')
				$header = $v;
	}
	if (!$header && isset($_SERVER['HTTP_AUTHORIZATION']))
		$header = $_SERVER['HTTP_AUTHORIZATION'];

	if (!$header || !preg_match('/^Bearer\s+(.+)$/i', trim($header), $m))
		api_error('Missing or malformed Authorization header', 401);

	$token = $m[1];
	$sql = "SELECT t.employee_id, e.* FROM ".TB_PREF."knb_api_tokens t
		JOIN ".TB_PREF."hr_employees e ON e.id = t.employee_id
		WHERE t.token=".db_escape($token)."
		AND (t.expires_at IS NULL OR t.expires_at > NOW())";
	$result = db_query($sql, "could not validate api token");
	$employee = db_fetch_assoc($result);
	if (!$employee)
		api_error('Invalid or expired token', 401);

	return $employee;
}
