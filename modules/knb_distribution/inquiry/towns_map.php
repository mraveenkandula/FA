<?php
/*
	Towns Coverage Map - a real, live map (Leaflet.js + OpenStreetMap tiles,
	no API key needed) showing every real town with a usable GPS point from
	the TechCloud data import. This is genuinely mappable data (3,594 of
	3,601 real towns have lat/lng) - unlike employee Geo Tracking, which
	has none yet (the imported attendance data is location-code check-ins,
	not GPS points, and hr_geo_tracking has no history to show until reps
	start using that feature going forward).
*/
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Towns Coverage Map"));

include_once($path_to_root . "/includes/ui.inc");

/*
	The imported lat/lng strings are genuinely messy free text from the
	source system - plain decimals, decimals with a stray leading "0 "
	token, degree-minute-second notation ("14° 16' 0.12\" N"), and some
	that are truncated/swapped and not recoverable at all. Rather than
	guess at ambiguous cases, this parses only the formats that have an
	unambiguous mathematical reading (plain decimal, or real DMS via
	degrees + minutes/60 + seconds/3600) and skips - counted, not
	silently dropped - anything else, plus anything that parses but
	lands outside India's lat/lng range (a real anomaly in the source
	data, not a parsing bug).
*/
function parse_coordinate($raw, $min, $max)
{
	$raw = trim($raw);

	// Degrees/minutes/seconds: 14° 16' 0.12" or 20°19'30.2"
	if (preg_match('/([0-9]{1,3})\s*°\s*([0-9]{1,2})\s*[\'\x{2019}]\s*([0-9]{1,2}(?:\.[0-9]+)?)?/u', $raw, $m))
	{
		$deg = (float)$m[1];
		$min_part = (float)$m[2];
		$sec_part = isset($m[3]) ? (float)$m[3] : 0;
		$val = $deg + ($min_part / 60) + ($sec_part / 3600);
	}
	// Plain decimal, optionally with a stray leading "0 " token or a
	// trailing degree sign (e.g. "0 77.60", "014.68° ", "020.2986")
	elseif (preg_match('/([0-9]{1,3}\.[0-9]+)/', $raw, $m))
	{
		$val = (float)$m[1];
	}
	else
	{
		return null;
	}

	if ($val < $min || $val > $max)
		return null;

	return $val;
}

$customer_counts = array();
$result = db_query("SELECT town_code, COUNT(*) AS cnt FROM ".TB_PREF."debtors_master GROUP BY town_code");
while ($row = db_fetch($result))
	$customer_counts[$row['town_code']] = (int)$row['cnt'];

$result = db_query(
	"SELECT id, name, district, lat, lng FROM ".TB_PREF."sales_towns
	 WHERE lat IS NOT NULL AND lat != '' AND lat != '0'
	 AND lng IS NOT NULL AND lng != '' AND lng != '0'");

$points = array();
$skipped = 0;
while ($row = db_fetch($result))
{
	$lat = parse_coordinate($row['lat'], 6, 38);
	$lng = parse_coordinate($row['lng'], 68, 98);

	if ($lat === null || $lng === null)
	{
		$skipped++;
		continue;
	}

	$points[] = array(
		'name' => $row['name'],
		'district' => $row['district'],
		'lat' => $lat,
		'lng' => $lng,
		'customers' => isset($customer_counts[$row['id']]) ? $customer_counts[$row['id']] : 0,
	);
}

display_note(sprintf(_('%d towns plotted (real GPS data from the TechCloud import).'), count($points)), 0, 1);
if ($skipped > 0)
	display_note(sprintf(_('%d towns skipped - their recorded coordinates are unparseable or fall outside India (a source data quality issue, not a display bug).'), $skipped), 0, 1);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css"
	integrity="sha512-Zcn6bjR/8RZbLEpLIeOwNtzREBAJnUKESxces60Mpoj+2okopSAcSUIUOseddDm0cxnGQzxIR7vJgsLZbdLE3w=="
	crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"
	integrity="sha512-BwHfrr4c9kmRkLw6iXFdzcdWV/PGkVgiIyIWLLlTSXzWQzxuSg4DiQUCpauz/EWjgk5TYQqX/kvn9pG1NpYfqg=="
	crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<div id="knb-towns-map" style="height:600px;width:100%;border:1px solid #C9BFA0;border-radius:8px;"></div>
<script>
(function() {
	var points = <?php echo json_encode($points); ?>;
	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function(c) {
			return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
		});
	}

	var map = L.map('knb-towns-map');
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; OpenStreetMap contributors',
		maxZoom: 18
	}).addTo(map);

	var bounds = [];
	points.forEach(function(p) {
		var radius = 4 + Math.min(Math.sqrt(p.customers), 20);
		var marker = L.circleMarker([p.lat, p.lng], {
			radius: radius,
			color: '#6E0000',
			fillColor: '#C8102E',
			fillOpacity: 0.7,
			weight: 1
		}).addTo(map);
		marker.bindPopup(
			'<b>' + escapeHtml(p.name) + '</b><br>' +
			(p.district ? escapeHtml(p.district) + '<br>' : '') +
			escapeHtml(p.customers) + ' customer(s)'
		);
		bounds.push([p.lat, p.lng]);
	});

	if (bounds.length > 0)
		map.fitBounds(bounds, {padding: [20, 20]});
	else
		map.setView([17.0, 81.8], 6);
})();
</script>
<?php
end_page();
