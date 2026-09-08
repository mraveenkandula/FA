<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Attendance Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/attendance_db.inc");
include_once(__DIR__ . "/../manage/department_db.inc");
include_once(__DIR__ . "/../manage/designation_db.inc");

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

$result = get_attendance_report(date2sql($_POST['date_from']), date2sql($_POST['date_to']));

/*
	Selfie thumbnails - the same punch-in/punch-out photo the employee sees
	in the mobile app's own attendance history (attendance_selfie_post.php /
	get_attendance_selfies(), see modules/knb_api/includes/
	attendance_selfie_db.inc), surfaced here so HR/admin reviewing this
	report can see it too without needing separate access to the mobile
	app or its API. $row['selfie_in_path']/['selfie_out_path'] are root-
	relative (e.g. "company/0/images/attendance_selfies/xxx.jpg", per
	api_upload.inc's doc comment on that shape) - prefixed with
	$path_to_root here since this page lives three directories down from
	the FA root (modules/knb_hrm/inquiry/).
*/
function selfie_thumb_cell($path)
{
	global $path_to_root;

	if (!$path)
	{
		label_cell('&nbsp;');
		return;
	}
	$src = htmlspecialchars($path_to_root.'/'.$path, ENT_QUOTES, 'UTF-8');
	label_cell("<a href='$src' target='_blank'><img src='$src' height='40' border='0' alt='selfie'></a>");
}

start_table(TABLESTYLE, "width='80%'");
$th = array(_('Date'), _('Code'), _('Employee'), _('Status'), _('Check In'), _('Selfie In'), _('Check Out'), _('Selfie Out'), _('Remarks'));
table_header($th);
$k = 0;

while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['att_date']));
	label_cell(htmlspecialchars($row['emp_code'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['check_in'], ENT_QUOTES, 'UTF-8'));
	selfie_thumb_cell($row['selfie_in_path']);
	label_cell(htmlspecialchars($row['check_out'], ENT_QUOTES, 'UTF-8'));
	selfie_thumb_cell($row['selfie_out_path']);
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
