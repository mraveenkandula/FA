<?php
/*
	POST /modules/knb_api/endpoints/outlet_create.php
	Authorization: Bearer <token>
	{ "name": "...", "phone": "...", "secondary_phone": "...", "gst_no": "...",
	  "lat": 17.12345, "lng": 78.12345, "person_type": "Retailer",
	  "outlet_type": "Retail", "territory_id": N, "town_id": N, "beat_id": N }
	-> { "debtor_no": N }

	Same duplicate check as outlet_search_duplicates.php is run again here
	server-side before creating anything - the app's own pre-check is a UX
	nicety, not something this endpoint should trust blindly.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
$employee = api_require_auth();
require_once(__DIR__ . '/../includes/outlet_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$name = trim(@$input['name']);
$phone = trim(@$input['phone']);
$lat = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null;
$lng = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null;

if ($name === '')
	api_error('Outlet name is required');
if (empty($input['force']))
{
	$candidates = search_duplicate_outlets($name, $phone, $lat, $lng);
	if (count($candidates) > 0)
		api_json(array('duplicate_candidates' => $candidates,
			'message' => 'Possible duplicate outlets found. Resubmit with "force": true to create anyway.'), 409);
}

$debtor_no = create_outlet($name, $phone, @$input['secondary_phone'], @$input['gst_no'], $lat, $lng,
	@$input['person_type'], @$input['outlet_type'], @$input['territory_id'], @$input['town_id'],
	@$input['beat_id'], $employee['id']);

api_json(array('debtor_no' => $debtor_no), 201);
