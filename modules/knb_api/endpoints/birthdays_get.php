<?php
/*
	GET /modules/knb_api/endpoints/birthdays_get.php?days_ahead=30
	Authorization: Bearer <token>
	-> { "employees": [ {id, name, department, dob, days_until}, ... ],
	     "customers": [ {debtor_no, name, address, phone, town_name,
	         person_type, dob, days_until}, ... ] }

	Company-wide "Birthdays & Anniversaries" listing - the gap documented in
	docs/mobile-app-reverse-engineering.md ("Employee/Customer Birthdays...
	no endpoint lists other employees'/customers' dob company-wide").
	employee_profile_get.php deliberately only returns the *authenticated*
	employee's own row (per this API's trust model for employee data); this
	is a separate, intentionally company-wide endpoint, matching the
	reference app's own "Birthdays & Anniversaries" screen and this fork's
	existing web page modules/knb_distribution/inquiry/customer_birthday_list.php
	(also company-wide, filtered only by town).

	Both sides match on month/day only (not year), "days_until" counting
	forward from today to the next occurrence - same semantics as
	customer_birthday_list.php's own get_upcoming_birthdays(), copied
	verbatim below for the customer side (that function is defined inline
	inside a page script gated by session.inc/page(), not in a reusable
	db.inc - same situation already documented for employee_target_get.php's
	api_get_actual_sales()/api_get_incentive_pct()).

	Employee side: 0_hr_employees.dob (confirmed via DESCRIBE, well
	populated - 135/139 active employees have a real dob in local dev DB).
	Customer side: 0_customer_distribution.date_of_birth (same table
	customer_birthday_list.php reads, joined to debtors_master for the
	name) - confirmed via DESCRIBE/SELECT that the column exists and the
	query works, though only a small number of rows have a real value
	populated in local dev DB today; the feature is fully wired regardless
	of how much of the imported customer data happens to carry a DOB.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$days_ahead = trim((string)@$_GET['days_ahead']);
$days_ahead = ($days_ahead !== '' && is_numeric($days_ahead)) ? (int)$days_ahead : 30;
if ($days_ahead < 0 || $days_ahead > 366)
	api_error('days_ahead must be between 0 and 366');

// Same "next occurrence of this month/day" expression
// customer_birthday_list.php's get_upcoming_birthdays() uses, generalised
// to any DATE column via $column.
function birthdays_next_occurrence_sql($column)
{
	return "IF(
			DATE_ADD($column, INTERVAL (YEAR(CURDATE()) - YEAR($column)) YEAR) >= CURDATE(),
			DATE_ADD($column, INTERVAL (YEAR(CURDATE()) - YEAR($column)) YEAR),
			DATE_ADD($column, INTERVAL (YEAR(CURDATE()) - YEAR($column) + 1) YEAR)
		)";
}

$next_occurrence = birthdays_next_occurrence_sql('e.dob');
$sql = "SELECT e.id, e.first_name, e.last_name, e.dob,
		DATEDIFF($next_occurrence, CURDATE()) AS days_until
	FROM ".TB_PREF."hr_employees e
	WHERE e.dob IS NOT NULL AND e.dob <> '0000-00-00' AND e.inactive = 0
	HAVING days_until <= ".db_escape($days_ahead)."
	ORDER BY days_until";
$result = db_query($sql, "could not get employee birthdays");

$employees = array();
while ($row = db_fetch_assoc($result))
{
	$employees[] = array(
		'id' => (int)$row['id'],
		'name' => trim($row['first_name'].' '.$row['last_name']),
		// dob is a native MySQL DATE column - already plain ISO YYYY-MM-DD;
		// sql2date() would wrongly convert to display format instead (see
		// the same fix/comment in stock_verification_get.php).
		'dob' => $row['dob'],
		'days_until' => (int)$row['days_until'],
	);
}

/*
	Copied from customer_birthday_list.php's get_upcoming_birthdays() - see
	file header. Not scoped to the authenticated employee's own customers:
	matches that existing web page's own company-wide behaviour (filterable
	only by town there, no employee filter either) - a field rep genuinely
	benefits from seeing all upcoming customer birthdays, not just their
	own book, the same way the reference app's own combined screen does.
*/
$next_occurrence_c = birthdays_next_occurrence_sql('cd.date_of_birth');
$sql = "SELECT d.debtor_no, d.name AS customer_name, d.address, cd.phone,
		t.name AS town_name, cd.date_of_birth, cd.person_type,
		DATEDIFF($next_occurrence_c, CURDATE()) AS days_until
	FROM ".TB_PREF."customer_distribution cd
	JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
	LEFT JOIN ".TB_PREF."sales_towns t ON t.id = cd.town_id
	WHERE cd.date_of_birth IS NOT NULL AND cd.date_of_birth <> '0000-00-00'
	HAVING days_until <= ".db_escape($days_ahead)."
	ORDER BY days_until";
$result = db_query($sql, "could not get customer birthdays");

$customers = array();
while ($row = db_fetch_assoc($result))
{
	$customers[] = array(
		'debtor_no' => (int)$row['debtor_no'],
		'name' => $row['customer_name'],
		'address' => $row['address'],
		'phone' => $row['phone'],
		'town_name' => $row['town_name'],
		'person_type' => $row['person_type'],
		'dob' => $row['date_of_birth'],
		'days_until' => (int)$row['days_until'],
	);
}

api_json(array('employees' => $employees, 'customers' => $customers));
