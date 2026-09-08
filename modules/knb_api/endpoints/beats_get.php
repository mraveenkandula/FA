<?php
/*
	GET /modules/knb_api/endpoints/beats_get.php?search=text
	Authorization: Bearer <token>
	-> { "beats": [ {id, name, town_id}, ... ] }

	Backs the beat-name autocomplete used by JourneyPlanScreen's "Add
	journey plan" dialog (journey_plan_screen.dart) and AddOutletScreen's
	"Advanced (territory / town / beat)" section (add_outlet_screen.dart) -
	both previously made the field user type a raw 0_sales_beats.id by
	hand, with no way to look one up. search (optional) does a
	case-insensitive substring match against name, same LIKE-based filter
	shape as stock_items_get.php's own search param; empty/omitted search
	returns the first $limit active beats by name rather than erroring,
	matching that endpoint's precedent of treating an absent search as "no
	filter" rather than a required parameter.

	Results are capped at $limit (20) - same rationale as
	stock_items_get.php: a fast typist against a fast local API shouldn't
	pull a large table over a mobile connection on every keystroke, and a
	type-ahead only ever needs enough options to be useful, not the whole
	table. Ordered by name so the capped set is at least alphabetically
	predictable across calls.

	inactive=1 beats are excluded - 0_sales_beats.inactive marks a retired
	beat the same way stock_master.inactive marks a retired item, and
	neither JourneyPlanScreen nor AddOutletScreen should ever offer one for
	a *new* assignment.

	modules/knb_distribution/manage/beats_db.inc's own get_all_beats()
	already exists but has no search or limit parameter (it backs the web
	admin "manage beats" dropdown, which shows the whole active table) -
	reusing it here would mean pulling every active beat over the wire on
	every keystroke just to filter/cap it in PHP, defeating the point of a
	capped, server-side-filtered autocomplete. Same "no existing function
	matches this exact shape" situation stock_items_get.php's own doc
	comment describes for its base item list - so this queries
	0_sales_beats directly, same as that endpoint does for stock_master.
*/
require_once(__DIR__ . '/../includes/api_bootstrap.inc');
api_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
	api_error('GET required', 405);

$search = trim((string)@$_GET['search']);
$limit = 20;

$sql = "SELECT id, name, town_id
	FROM ".TB_PREF."sales_beats
	WHERE inactive = 0";
if ($search !== '')
	$sql .= " AND name LIKE ".db_escape('%'.$search.'%');
$sql .= " ORDER BY name
	LIMIT ".(int)$limit;

$result = db_query($sql, "could not retrieve beats");

$beats = array();
while ($row = db_fetch_assoc($result))
{
	$beats[] = array(
		'id' => (int)$row['id'],
		'name' => $row['name'],
		'town_id' => $row['town_id'] !== null ? (int)$row['town_id'] : null,
	);
}

api_json(array('beats' => $beats));
