<?php
/*
	Provisioning for the mobile app login credentials that live on
	0_hr_employees.mobile_username/mobile_password_hash (added by
	modules/knb_api's schema - see modules/knb_api/sql/knb_api.sql). This
	file is the only place in the app that is allowed to write those two
	columns; modules/knb_api itself only ever reads them (login.php).

	The plaintext password is never stored, logged or returned by any
	function here except as the direct return value of
	set_mobile_credentials() at the moment it's generated - the caller
	(mobile_credentials.php) shows it once and discards it. There is no
	"reveal password" path; re-provisioning always generates a brand new
	password.
*/

function get_mobile_credential_employees($search = '')
{
	$sql = "SELECT id, emp_code, first_name, last_name, mobile, mobile_username
		FROM ".TB_PREF."hr_employees
		WHERE !inactive";
	if ($search !== '')
		$sql .= " AND (first_name LIKE ".db_escape('%'.$search.'%').
			" OR last_name LIKE ".db_escape('%'.$search.'%').
			" OR emp_code LIKE ".db_escape('%'.$search.'%').")";
	$sql .= " ORDER BY first_name, last_name";
	return db_query($sql, "could not retrieve employees");
}

function get_employee_for_mobile_credentials($id)
{
	$sql = "SELECT id, emp_code, first_name, last_name, mobile, mobile_username
		FROM ".TB_PREF."hr_employees WHERE id=".db_escape($id);
	$result = db_query($sql, "could not retrieve employee");
	return db_fetch($result);
}

// Uniqueness is enforced here (checked before every insert/update) as well
// as by a UNIQUE index on the column itself (see
// modules/knb_hrm/sql/knb_hrm_mobile_credentials.sql) - belt and braces,
// matching how modules/knb_distributor_portal's portal_username is
// checked in distributor_access.php before set_portal_credentials().
function mobile_username_exists($username, $exclude_employee_id = null)
{
	$sql = "SELECT id FROM ".TB_PREF."hr_employees WHERE mobile_username=".db_escape($username);
	if ($exclude_employee_id !== null)
		$sql .= " AND id != ".db_escape($exclude_employee_id);
	$result = db_query($sql, "could not check mobile username");
	return db_fetch($result) !== false;
}

// Suggests firstname_lastname (lower-cased, non-alphanumerics collapsed to
// underscores); falls back to the employee code, then disambiguates with a
// numeric suffix if the base is already taken by someone else. This is
// only ever a *suggestion* shown in an editable field - the office user is
// free to type something else before saving.
function suggest_mobile_username($employee)
{
	$base = strtolower(trim($employee['first_name'].'_'.$employee['last_name']));
	$base = preg_replace('/[^a-z0-9]+/', '_', $base);
	$base = trim($base, '_');
	if ($base === '')
		$base = 'emp_'.$employee['emp_code'];

	$candidate = $base;
	$suffix = 1;
	while (mobile_username_exists($candidate, $employee['id']))
	{
		$suffix++;
		$candidate = $base.$suffix;
	}
	return $candidate;
}

// Alphabet excludes visually ambiguous characters (0/O, 1/l/I) since this
// is read off a screen once and typed into a phone by someone else.
function generate_mobile_password($length = 12)
{
	$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	$max = strlen($alphabet) - 1;
	$password = '';
	for ($i = 0; $i < $length; $i++)
		$password .= $alphabet[random_int(0, $max)];
	return $password;
}

// Sets/replaces both mobile_username and mobile_password_hash and returns
// the plaintext password that was just generated, for one-time display by
// the caller. Never call this expecting to retrieve an existing password -
// there isn't one to retrieve, only ever a freshly generated one.
function set_mobile_credentials($employee_id, $username)
{
	$password = generate_mobile_password();
	$hash = password_hash($password, PASSWORD_DEFAULT);
	$sql = "UPDATE ".TB_PREF."hr_employees SET mobile_username=".db_escape($username).",
		mobile_password_hash=".db_escape($hash)."
		WHERE id=".db_escape($employee_id);
	db_query($sql, "could not set mobile credentials");
	return $password;
}
