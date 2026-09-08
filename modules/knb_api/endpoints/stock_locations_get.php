<?php
/*
	GET /modules/knb_api/endpoints/stock_locations_get.php?search=text
	Authorization: Bearer <token>
	-> { "locations": [ {loc_code, location_name}, ... ] }

	Backs the location-name autocomplete used by StockVerificationScreen's
	"Start stock verification" dialog (stock_verification_screen.dart) -
	previously made the field user type a raw core-FA 0_locations.loc_code
	(e.g. "LC001") by hand, with no way to look one up by the name they'd
	actually recognise. search (optional) filters case-insensitively
	against location_name - the whole point of this endpoint is searching
	by *name* while still handing back the loc_code the app actually needs
	to submit (see StockVerificationRepository.startVerification()'s
	locCode param / stock_verification_post.php's "start" action). Same
	cap/ordering/inactive-exclusion rationale as beats_get.php: capped at
	$limit (20), ordered by location_name, inactive=1 locations excluded
	since a retired stock location shouldn't be offered for a *new* count.

	0_locations is a standard core-FA table (see sql/en_US-new.sql for its
	canonical shape) with no existing search-by-name-with-limit function to
	reuse (the closest, get_all_locations() in
	inventory/includes/db/items_locations_db.inc [if present] / admin's own
	location-maintenance list, returns the whole table for a small on-page
	dropdown, not a capped/filtered API result) - so, same as
	beats_get.php, this queries 0_locations directly rather than pulling a
	full list over the wire just to filter it in PHP.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$search = trim((string)@$_GET['search']);
$limit = 20;

$sql = "SELECT loc_code, location_name
	FROM ".TB_PREF."locations
	WHERE inactive = 0";
if ($search !== '')
	$sql .= " AND location_name LIKE ".db_escape('%'.$search.'%');
$sql .= " ORDER BY location_name
	LIMIT ".(int)$limit;

$result = db_query($sql, "could not retrieve stock locations");

$locations = array();
while ($row = db_fetch_assoc($result))
{
	$locations[] = array(
		'loc_code' => $row['loc_code'],
		'location_name' => $row['location_name'],
	);
}

api_json(array('locations' => $locations));
