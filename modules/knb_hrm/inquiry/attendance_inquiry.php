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

start_table(TABLESTYLE, "width='70%'");
$th = array(_('Date'), _('Code'), _('Employee'), _('Status'), _('Check In'), _('Check Out'), _('Remarks'));
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
	label_cell(htmlspecialchars($row['check_out'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
