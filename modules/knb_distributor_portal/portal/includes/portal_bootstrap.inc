<?php
/*
	Lightweight bootstrap for the distributor-facing portal pages. A
	distributor is a customer (debtors_master), not an FA user, so this
	deliberately does not go through FA's normal cookie/session stack
	(includes/session.inc) - that assumes a row in 0_users with the full
	FA permission/menu model, which a distributor doesn't have and
	shouldn't get. Mirrors modules/knb_api/includes/api_bootstrap.inc's
	approach of bringing up just enough of core to reuse db_query() and a
	few real FA helpers (get_qoh_on_date, date formatting), but renders
	HTML and keeps its own session instead of returning JSON.
*/

if (!defined('TB_PREF'))
	define('TB_PREF', '0_');

$path_to_root = dirname(dirname(dirname(dirname(__DIR__))));

if (!defined('VARLIB_PATH'))
	define('VARLIB_PATH', $path_to_root.'/tmp');
if (!defined('VARLOG_PATH'))
	define('VARLOG_PATH', $path_to_root.'/tmp');

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
	exit('Database connection failed.');
}
db_query("SET sql_mode = ''");

include_once($path_to_root . '/includes/prefs/sysprefs.inc');
$SysPrefs = new sys_prefs();
$SysPrefs->refresh();

include_once($path_to_root . '/includes/types.inc');
include_once($path_to_root . '/includes/date_functions.inc');
include_once($path_to_root . '/includes/db/sql_functions.inc');
include_once($path_to_root . '/includes/db/inventory_db.inc');

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
			{
				http_response_code(500);
				exit('A database error occurred.');
			}
		}
		return $db_error;
	}
}

session_name('knb_portal');
session_start();

function portal_csrf_token()
{
	if (empty($_SESSION['portal_csrf']))
		$_SESSION['portal_csrf'] = bin2hex(random_bytes(16));
	return $_SESSION['portal_csrf'];
}

function portal_csrf_check()
{
	return isset($_POST['csrf_token']) && !empty($_SESSION['portal_csrf'])
		&& hash_equals($_SESSION['portal_csrf'], $_POST['csrf_token']);
}

/*
	Redirects to login.php if not authenticated. Returns the logged-in
	distributor's debtors_master row (plus their own stock location's
	loc_code, joined from 0_locations.debtor_no) on success.
*/
function portal_require_login()
{
	if (empty($_SESSION['portal_debtor_no']))
	{
		header('Location: login.php');
		exit;
	}

	$result = db_query("SELECT dm.debtor_no, dm.name, dm.debtor_ref, l.loc_code
		FROM ".TB_PREF."debtors_master dm
		LEFT JOIN ".TB_PREF."locations l ON l.debtor_no = dm.debtor_no
		WHERE dm.debtor_no=".db_escape($_SESSION['portal_debtor_no'])." AND dm.portal_active=1",
		"could not load distributor");
	$customer = db_fetch($result);
	if (!$customer)
	{
		session_destroy();
		header('Location: login.php');
		exit;
	}
	return $customer;
}

function portal_page_start($title)
{
	echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title>';
	echo '<style>
		body { font-family: -apple-system, Arial, sans-serif; background: #FBF7EE; margin: 0; color: #333; }
		.knb-header { background: #6E0000; color: #fff; padding: 14px 16px; }
		.knb-header h1 { font-size: 18px; margin: 0; }
		.knb-header .sub { font-size: 13px; opacity: 0.85; }
		.knb-body { padding: 16px; max-width: 900px; margin: 0 auto; }
		table { width: 100%; border-collapse: collapse; background: #fff; font-size: 14px; }
		th, td { padding: 8px 10px; border-bottom: 1px solid #e5ddc8; text-align: left; }
		th { background: #F2E9D0; }
		input[type=text], input[type=password], input[type=number] { padding: 8px; font-size: 14px; width: 100%; box-sizing: border-box; border: 1px solid #C9BFA0; border-radius: 4px; }
		button, .knb-btn { background: #C8102E; color: #fff; border: none; padding: 10px 18px; border-radius: 4px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; }
		.knb-card { background: #fff; border: 1px solid #C9BFA0; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
		.knb-error { color: #6E0000; margin-bottom: 12px; }
		a.knb-logout { float: right; color: #fff; font-size: 13px; }
	</style></head><body>';
}

function portal_page_end()
{
	echo '</body></html>';
}
