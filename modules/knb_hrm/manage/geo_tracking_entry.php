<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Geo Tracking"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/geo_tracking_db.inc");
include_once(__DIR__ . "/employee_db.inc");

if (isset($_POST['SavePing']) && $_POST['latitude'] !== '' && $_POST['longitude'] !== '')
{
	save_geo_ping($_POST['employee_id'], $_POST['latitude'], $_POST['longitude'],
		$_POST['accuracy_m'] ?: null, $_POST['remarks']);
	display_notification(_('Location captured and saved'));
}

$employees = array();
$result = get_all_employees(true);
while ($row = db_fetch($result))
	$employees[$row['id']] = trim($row['first_name'].' '.$row['last_name']).' ('.$row['emp_code'].')';

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], $employees);
text_row_ex(_("Remarks (e.g. visit/customer name)").':', 'remarks', 40);
end_table();

hidden('latitude', '');
hidden('longitude', '');
hidden('accuracy_m', '');

echo "<div style='margin:10px 0'>";
echo "<button type='button' id='capture_btn' class='inputsubmit'>"._("Capture My Location").
	"</button> <span id='geo_status'></span>";
echo "</div>";

echo "<script>
document.getElementById('capture_btn').addEventListener('click', function() {
	var status = document.getElementById('geo_status');
	if (!navigator.geolocation) {
		status.innerText = 'Geolocation not supported by this browser.';
		return;
	}
	status.innerText = 'Locating...';
	navigator.geolocation.getCurrentPosition(function(pos) {
		document.getElementsByName('latitude')[0].value = pos.coords.latitude;
		document.getElementsByName('longitude')[0].value = pos.coords.longitude;
		document.getElementsByName('accuracy_m')[0].value = Math.round(pos.coords.accuracy);
		status.innerText = 'Location captured: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5) +
			' (accuracy ' + Math.round(pos.coords.accuracy) + 'm). Click Save Location to record it.';
	}, function(err) {
		status.innerText = 'Could not get location: ' + err.message;
	}, {enableHighAccuracy: true, timeout: 10000});
});
</script>";

submit_center('SavePing', _("Save Location"));

end_form();

end_page();
