<?php
/*
	GET /modules/knb_api/endpoints/lead_followups_get.php?debtor_no=N
	Authorization: Bearer <token>
	-> { "followups": [ {id, debtor_no, employee_id, employee_name, status,
		follow_up_date, notes, created_at}, ... ] }

	Full follow-up history for one lead (newest first) - the log behind
	leads_get.php's current-status summary. Not scoped to "my leads" only:
	any authenticated field employee can read a lead's history (matches
	leads_get.php's own read scope being informational, not a security
	boundary - the mobile API has no per-territory row-level auth anywhere
	else either).
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/lead_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$debtor_no = isset($_GET['debtor_no']) ? (int)$_GET['debtor_no'] : 0;
if ($debtor_no <= 0)
	api_error('debtor_no is required');

$result = get_lead_followups($debtor_no);

$followups = array();
while ($row = db_fetch_assoc($result))
{
	$name = trim($row['first_name'].' '.$row['last_name']);
	$followups[] = array(
		'id' => (int)$row['id'],
		'debtor_no' => (int)$row['debtor_no'],
		'employee_id' => $row['employee_id'] !== null ? (int)$row['employee_id'] : null,
		'employee_name' => $name !== '' ? $name : null,
		'status' => $row['status'],
		// follow_up_date/created_at are native DATE/DATETIME columns - see
		// lead_db.inc's header comment for why these pass through as-is.
		'follow_up_date' => $row['follow_up_date'],
		'notes' => $row['notes'],
		'created_at' => $row['created_at'],
	);
}

api_json(array('followups' => $followups));
