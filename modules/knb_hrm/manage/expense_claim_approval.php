<?php
$page_security = 'SA_KNB_EXPENSE_APPROVE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

page(_($help_context = "Expense Claim Approval"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/expense_claim_db.inc");

$approve_id = find_submit('Approve_');
if ($approve_id != -1 && check_csrf_token())
{
	set_expense_claim_status($approve_id, 'Approved', null, date2sql(Today()));
	display_notification(_('Expense claim approved.'));
}
$reject_id = find_submit('Reject_');
if ($reject_id != -1 && check_csrf_token())
{
	set_expense_claim_status($reject_id, 'Rejected', null, date2sql(Today()));
	display_notification(_('Expense claim rejected.'));
}

start_form();

display_heading(_("Pending Claims"));
$result = get_expense_claims('Pending');
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Date'), _('Employee'), _('Category'), _('Amount'), _('Description'), '');
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$id = $row['id'];
	alt_table_row_color($k);
	label_cell(sql2date($row['claim_date']));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['amount']);
	label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
	label_cell("<input type='submit' name='Approve_$id' value='"._('Approve')."' class='inputsubmit'>"
		." <input type='submit' name='Reject_$id' value='"._('Reject')."' class='inputsubmit'>");
	end_row();
}
end_table();

end_form();
end_page();
