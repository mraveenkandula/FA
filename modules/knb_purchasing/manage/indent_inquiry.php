<?php
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Purchase Indent Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/indent_db.inc");

$statuses = array('' => _('All'), 'Pending' => _('Pending'), 'Approved' => _('Approved'), 'Rejected' => _('Rejected'));

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Status").':', 'status_filter', @$_POST['status_filter'], $statuses);
end_table();
end_form();

$result = get_indents(@$_POST['status_filter'] ?: null);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('Item'), _('Qty'), _('UOM'), _('Requested By'), _('Status'), _('Approved Date'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['requested_date']));
	label_cell(htmlspecialchars($row['item_description'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['qty']);
	label_cell(htmlspecialchars($row['uom'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['approved_date'] ? sql2date($row['approved_date']) : '');
	end_row();
}
end_table();
end_page();
