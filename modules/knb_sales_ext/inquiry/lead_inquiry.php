<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Lead Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/lead_db.inc");

$statuses = array('' => _('All'), 'New' => _('New'), 'Contacted' => _('Contacted'), 'Converted' => _('Converted'), 'Lost' => _('Lost'));

$mark_contacted = find_submit('Contacted_');
if ($mark_contacted != -1 && check_csrf_token())
{
	set_lead_status($mark_contacted, 'Contacted');
	display_notification(_('Lead marked as Contacted.'));
}
$mark_converted = find_submit('Converted_');
if ($mark_converted != -1 && check_csrf_token())
{
	set_lead_status($mark_converted, 'Converted');
	display_notification(_('Lead marked as Converted.'));
}
$mark_lost = find_submit('Lost_');
if ($mark_lost != -1 && check_csrf_token())
{
	set_lead_status($mark_lost, 'Lost');
	display_notification(_('Lead marked as Lost.'));
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Status").':', 'status', @$_POST['status'], $statuses);
end_table();

$result = get_sales_leads(@$_POST['status'] ?: null);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('Lead Name'), _('Phone'), _('Territory'), _('Town'), _('Beat'), _('Assigned To'), _('Status'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(sql2date($row['created_date']));
	label_cell(htmlspecialchars($row['lead_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['territory_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['town_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['beat_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	if ($row['status'] == 'New' || $row['status'] == 'Contacted')
	{
		label_cell("<input type='submit' name='Contacted_$id' value='"._('Contacted')."' class='inputsubmit'>"
			." <input type='submit' name='Converted_$id' value='"._('Converted')."' class='inputsubmit'>"
			." <input type='submit' name='Lost_$id' value='"._('Lost')."' class='inputsubmit'>");
	}
	else
		label_cell('');
	end_row();
}
end_table();
end_form();

end_page();
