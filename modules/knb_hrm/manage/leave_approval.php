<?php
$page_security = 'SA_KNB_LEAVE_APPROVE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

page(_($help_context = "Leave Approval"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/leave_entry_db.inc");

$approve_id = find_submit('Approve_');
if ($approve_id != -1 && check_csrf_token())
{
	set_leave_status($approve_id, 'Approved');
	display_notification(_('Leave approved.'));
}
$reject_id = find_submit('Reject_');
if ($reject_id != -1 && check_csrf_token())
{
	set_leave_status($reject_id, 'Rejected');
	display_notification(_('Leave rejected.'));
}

start_form();

display_heading(_("Pending Leave Requests"));
$result = get_leave_entries('Pending');
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Employee'), _('Leave Type'), _('From'), _('To'), _('Days'), _('Session'), _('Reason'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['leave_type_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['leave_from']));
	label_cell(sql2date($row['leave_to']));
	amount_cell($row['leave_days']);
	label_cell(htmlspecialchars($row['session_type'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8'));
	label_cell("<input type='submit' name='Approve_$id' value='"._('Approve')."' class='inputsubmit'>"
		." <input type='submit' name='Reject_$id' value='"._('Reject')."' class='inputsubmit'>");
	end_row();
}
end_table();

end_form();
end_page();
