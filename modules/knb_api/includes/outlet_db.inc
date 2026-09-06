<?php
global $path_to_root;
require_once($path_to_root . '/sales/includes/db/customers_db.inc');
require_once($path_to_root . '/sales/includes/db/branches_db.inc');
require_once($path_to_root . '/admin/db/company_db.inc');

/*
	Candidate duplicates for a proposed new outlet: matched by exact phone/
	secondary-phone, a loose name match, or GPS proximity (Haversine,
	kilometres) to an already-registered outlet - same three signals the
	field-app demo described using for its "find nearby outlet" check.
*/
function search_duplicate_outlets($name, $phone, $lat, $lng, $radius_km = 0.5)
{
	$distance_expr = "NULL";
	if ($lat !== null && $lng !== null)
	{
		$distance_expr = "(6371 * ACOS(
			LEAST(1, GREATEST(-1,
				COS(RADIANS(".db_escape((float)$lat).")) * COS(RADIANS(cd.gps_lat)) *
					COS(RADIANS(cd.gps_lng) - RADIANS(".db_escape((float)$lng).")) +
				SIN(RADIANS(".db_escape((float)$lat).")) * SIN(RADIANS(cd.gps_lat))
			))
		))";
	}

	$sql = "SELECT d.debtor_no, d.name, cd.phone, cd.secondary_phone, cd.gps_lat, cd.gps_lng,
			$distance_expr AS distance_km
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		WHERE (";
	$conditions = array();
	if ($phone)
	{
		$conditions[] = "cd.phone=".db_escape($phone);
		$conditions[] = "cd.secondary_phone=".db_escape($phone);
	}
	if ($name)
		$conditions[] = "d.name LIKE ".db_escape('%'.$name.'%');
	if ($lat !== null && $lng !== null)
		$conditions[] = "$distance_expr <= ".db_escape((float)$radius_km);
	if (empty($conditions))
		return array();
	$sql .= implode(' OR ', $conditions).")";
	$sql .= " ORDER BY distance_km IS NULL, distance_km LIMIT 20";

	$result = db_query($sql, "could not search duplicate outlets");
	$rows = array();
	while ($row = db_fetch_assoc($result))
		$rows[] = $row;
	return $rows;
}

/*
	Creates a full working customer (debtors_master + a default branch, same
	as core's own "Add and Manage Customers" screen) plus the outlet-specific
	distribution/GPS/photo-support fields, in one transaction.
*/
function create_outlet($name, $phone, $secondary_phone, $gst_no, $lat, $lng,
	$person_type, $outlet_type, $territory_id, $town_id, $beat_id, $employee_id)
{
	begin_transaction();

	$cust_ref = 'OUT'.time().rand(10, 99);
	add_customer($name, $cust_ref, '', $gst_no ?: '', 'INR', 0, 0, 0, null, 0, 0, 1000, 1, '');
	$debtor_no = db_insert_id();

	add_branch($debtor_no, $name, $cust_ref, '', $employee_id ?: 0, null, 1, '',
		get_company_pref('default_sales_discount_act'), get_company_pref('debtors_act'),
		get_company_pref('default_prompt_payment_act'), 'DEF', '', 0, 1, '', '');

	db_query("REPLACE INTO ".TB_PREF."customer_distribution
		(debtor_no, person_type, territory_id, town_id, beat_id, sales_employee_id,
		 phone, secondary_phone, gst_no, gps_lat, gps_lng, outlet_type, created_by_employee_id)
		VALUES (".db_escape($debtor_no).",".db_escape($person_type ?: 'Retailer').","
		.db_escape($territory_id ?: null, true).",".db_escape($town_id ?: null, true).","
		.db_escape($beat_id ?: null, true).",".db_escape($employee_id ?: null, true).","
		.db_escape($phone ?: null, true).",".db_escape($secondary_phone ?: null, true).","
		.db_escape($gst_no ?: null, true).",".db_escape($lat !== null ? (float)$lat : null, true).","
		.db_escape($lng !== null ? (float)$lng : null, true).",".db_escape($outlet_type ?: null, true).","
		.db_escape($employee_id ?: null, true).")",
		"could not save outlet distribution details");

	commit_transaction();
	return $debtor_no;
}

function add_outlet_photo($debtor_no, $photo_type, $file_path, $employee_id)
{
	db_query("INSERT INTO ".TB_PREF."knb_outlet_photos
		(debtor_no, photo_type, file_path, uploaded_by_employee_id, uploaded_date)
		VALUES (".db_escape($debtor_no).",".db_escape($photo_type).",".db_escape($file_path).","
		.db_escape($employee_id ?: null, true).", NOW())",
		"could not save outlet photo");
	return db_insert_id();
}
