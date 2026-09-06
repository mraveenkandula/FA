<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Journey Plan Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/journey_plan_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('All Employees'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

if (!isset($_POST['date_from']) || $_POST['date_from'] == '')
	$_POST['date_from'] = Today();
if (!isset($_POST['date_to']) || $_POST['date_to'] == '')
	$_POST['date_to'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
date_row(_("From").':', 'date_from');
date_row(_("To").':', 'date_to');
end_table();
end_form();

$result = get_journey_plans(@$_POST['sales_employee_id'] ?: null, date2sql($_POST['date_from']), date2sql($_POST['date_to']));
start_table(TABLESTYLE, "width='80%'");
$th = array(_('Date'), _('Sales Employee'), _('Beat'), _('Remarks'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['plan_date']));
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['beat_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['remarks'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
