<?php
/*
	POST /modules/knb_api/endpoints/outlet_search_duplicates.php
	Authorization: Bearer <token>
	{ "name": "...", "phone": "...", "lat": 17.12345, "lng": 78.12345 }
	-> { "candidates": [ {debtor_no, name, phone, secondary_phone, gps_lat, gps_lng, distance_km}, ... ] }
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
api_require_auth();
require_once(__DIR__ . '/../includes/outlet_db.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
	api_error('POST required', 405);

$input = api_input();
$name = trim(@$input['name']);
$phone = trim(@$input['phone']);
$lat = isset($input['lat']) && $input['lat'] !== '' ? (float)$input['lat'] : null;
$lng = isset($input['lng']) && $input['lng'] !== '' ? (float)$input['lng'] : null;

if ($name === '' && $phone === '' && $lat === null)
	api_error('Provide at least a name, phone, or GPS location to check');

$candidates = search_duplicate_outlets($name, $phone, $lat, $lng);
api_json(array('candidates' => $candidates));
