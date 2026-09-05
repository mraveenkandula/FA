<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Geo Tracking Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/geo_tracking_db.inc");

if (!isset($_POST['date_from']) || $_POST['date_from'] == '')
	$_POST['date_from'] = sql2date(date('Y-m-01'));
if (!isset($_POST['date_to']) || $_POST['date_to'] == '')
	$_POST['date_to'] = Today();

start_form();
start_table(TABLESTYLE2);
date_row(_("From").':', 'date_from', null, null, 0, 0, 0, null, false);
date_row(_("To").':', 'date_to', null, null, 0, 0, 0, null, false);
end_table();
end_form();

$result = get_geo_report(date2sql($_POST['date_from']), date2sql($_POST['date_to']));

start_table(TABLESTYLE, "width='80%'");
$th = array(_('Date/Time'), _('Code'), _('Employee'), _('Location'), _('Accuracy'), _('Remarks'), '');
table_header($th);
$k = 0;

while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($row['recorded_at']);
	label_cell($row['emp_code']);
	label_cell(trim($row['first_name'].' '.$row['last_name']));
	label_cell($row['latitude'].', '.$row['longitude']);
	label_cell($row['accuracy_m'] ? $row['accuracy_m'].'m' : '');
	label_cell($row['remarks']);
	$map_url = "https://www.google.com/maps?q=".$row['latitude'].",".$row['longitude'];
	label_cell("<a href='".htmlspecialchars($map_url)."' target='_blank'>"._("View on map")."</a>");
	end_row();
}
end_table();

end_page();
