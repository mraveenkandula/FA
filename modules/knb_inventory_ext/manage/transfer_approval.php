<?php
$page_security = 'SA_LOCATIONTRANSFER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Stock Location Approved"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/transfer_db.inc");

$approve_id = find_submit('Approve_');
if ($approve_id != -1 && check_csrf_token())
{
	set_transfer_status($approve_id, 'Approved', date2sql(Today()));
	display_notification(_('Transfer request approved. Execute it via Stock Location Transfers.'));
}
$reject_id = find_submit('Reject_');
if ($reject_id != -1 && check_csrf_token())
{
	set_transfer_status($reject_id, 'Rejected', date2sql(Today()));
	display_notification(_('Transfer request rejected.'));
}

start_form();

display_heading(_("Pending Requests"));
$result = get_transfer_requests('Pending');
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('From'), _('To'), _('Item'), _('Qty'), _('Requested By'), _('Remarks'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(sql2date($row['requested_date']));
	label_cell(htmlspecialchars($row['from_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['to_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['item_desc'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['qty']);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	label_cell("<input type='submit' name='Approve_$id' value='"._('Approve')."' class='inputsubmit'>"
		." <input type='submit' name='Reject_$id' value='"._('Reject')."' class='inputsubmit'>");
	end_row();
}
end_table();

display_heading(_("Approved (execute via Stock Location Transfers)"));
$result = get_transfer_requests('Approved');
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('From'), _('To'), _('Item'), _('Qty'), _('Requested By'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['requested_date']));
	label_cell(htmlspecialchars($row['from_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['to_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['item_desc'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['qty']);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_form();
end_page();
