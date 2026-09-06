<?php
$page_security = 'SA_INVENTORYADJUSTMENT';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Stock Verification Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/stock_verification_db.inc");

$result = get_all_verifications();
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Date'), _('Location'), _('Counted By'), _('Remarks'), _('Adjustment #'), _('Status'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['verify_date']));
	label_cell(htmlspecialchars($row['loc_code'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['adjustment_trans_no'] ? '#'.$row['adjustment_trans_no'] : '-');
	label_cell($row['finalized'] ? _('Finalized') : _('In Progress'));
	end_row();
}
end_table();

end_page();
