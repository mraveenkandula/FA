<?php
/*
	GET /modules/knb_api/endpoints/leads_get.php
	Authorization: Bearer <token>
	-> { "leads": [ {debtor_no, name, phone, secondary_phone, person_type,
		territory_id, town_id, beat_id, gps_lat, gps_lng, status,
		last_follow_up_date, updated_at}, ... ] }

	"My leads" = customers this employee captured via outlet_create.php
	(0_customer_distribution.created_by_employee_id = the authenticated
	employee - see outlet_db.inc's create_outlet()), each annotated with its
	current status from the new lead follow-up pipeline (lead_db.inc).
	status defaults to 'New' for a lead with no follow-up logged yet.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/lead_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$result = get_leads_for_employee($employee['id']);

$leads = array();
while ($row = db_fetch_assoc($result))
{
	$leads[] = array(
		'debtor_no' => (int)$row['debtor_no'],
		'name' => $row['name'],
		'phone' => $row['phone'],
		'secondary_phone' => $row['secondary_phone'],
		'person_type' => $row['person_type'],
		'territory_id' => $row['territory_id'] !== null ? (int)$row['territory_id'] : null,
		'town_id' => $row['town_id'] !== null ? (int)$row['town_id'] : null,
		'beat_id' => $row['beat_id'] !== null ? (int)$row['beat_id'] : null,
		'gps_lat' => $row['gps_lat'] !== null ? (float)$row['gps_lat'] : null,
		'gps_lng' => $row['gps_lng'] !== null ? (float)$row['gps_lng'] : null,
		'status' => $row['status'],
		// last_follow_up_date/updated_at are native DATE/DATETIME columns -
		// already plain ISO/plain MySQL text as returned by
		// db_fetch_assoc(); no sql2date() conversion (see lead_db.inc's
		// header comment).
		'last_follow_up_date' => $row['last_follow_up_date'],
		'updated_at' => $row['updated_at'],
	);
}

api_json(array('leads' => $leads));
