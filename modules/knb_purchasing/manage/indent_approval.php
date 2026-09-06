<?php
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Purchase Indent Approval"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/indent_db.inc");

$approve_id = find_submit('Approve_');
if ($approve_id != -1 && check_csrf_token())
{
	set_indent_status($approve_id, 'Approved', null, date2sql(Today()));
	display_notification(_('Indent approved.'));
}
$reject_id = find_submit('Reject_');
if ($reject_id != -1 && check_csrf_token())
{
	set_indent_status($reject_id, 'Rejected', null, date2sql(Today()));
	display_notification(_('Indent rejected.'));
}

start_form();

$result = get_indents('Pending');
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('Item'), _('Qty'), _('UOM'), _('Requested By'), _('Required By'), _('Remarks'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(sql2date($row['requested_date']));
	label_cell(htmlspecialchars($row['item_description'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['qty']);
	label_cell(htmlspecialchars($row['uom'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell($row['required_by_date'] ? sql2date($row['required_by_date']) : '');
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	label_cell("<input type='submit' name='Approve_$id' value='"._('Approve')."' class='inputsubmit'>"
		." <input type='submit' name='Reject_$id' value='"._('Reject')."' class='inputsubmit'>");
	end_row();
}
end_table();

end_form();
end_page();
