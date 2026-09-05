<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Daily Attendance Entry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/attendance_db.inc");

$statuses = array('Present', 'Absent', 'Half Day', 'On Leave');

if (isset($_POST['att_date_pick']))
	$_POST['att_date'] = $_POST['att_date_pick'];
if (!isset($_POST['att_date']) || $_POST['att_date'] == '')
	$_POST['att_date'] = Today();

if (isset($_POST['SaveAttendance']))
{
	$sql_date = date2sql($_POST['att_date']);
	foreach ($_POST['status'] as $employee_id => $status)
	{
		save_attendance(
			$sql_date,
			$employee_id,
			$status,
			@$_POST['check_in'][$employee_id],
			@$_POST['check_out'][$employee_id],
			@$_POST['remarks'][$employee_id]
		);
	}
	display_notification(_('Attendance saved for').' '.$_POST['att_date']);
}

start_form();

start_table(TABLESTYLE2, "width='30%'");
date_row(_("Attendance Date").':', 'att_date_pick', null, null, 0, 0, 0, null, false);
end_table();

$sql_date = date2sql($_POST['att_date']);
$existing = get_attendance_for_date($sql_date);
$result = get_active_employees();

start_table(TABLESTYLE, "width='90%'");
$th = array(_('Code'), _('Employee'), _('Status'), _('Check In'), _('Check Out'), _('Remarks'));
table_header($th);
$k = 0;

while ($row = db_fetch($result))
{
	$id = $row['id'];
	$cur = @$existing[$id];
	alt_table_row_color($k);
	label_cell($row['emp_code']);
	label_cell(trim($row['first_name'].' '.$row['last_name']));

	$sel = "<select name='status[$id]'>";
	foreach ($statuses as $s)
	{
		$selected = (isset($cur['status']) ? $cur['status'] : 'Present') == $s ? " selected" : "";
		$sel .= "<option value='".htmlspecialchars($s)."'$selected>".htmlspecialchars($s)."</option>";
	}
	$sel .= "</select>";
	label_cell($sel);

	label_cell("<input type='text' size=8 name='check_in[$id]' value='".htmlspecialchars(@$cur['check_in'])."' placeholder='HH:MM'>");
	label_cell("<input type='text' size=8 name='check_out[$id]' value='".htmlspecialchars(@$cur['check_out'])."' placeholder='HH:MM'>");
	label_cell("<input type='text' size=15 name='remarks[$id]' value='".htmlspecialchars(@$cur['remarks'])."'>");
	end_row();
}
end_table();

submit_center('SaveAttendance', _("Save Attendance"));

end_form();

end_page();
