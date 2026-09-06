<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Target & Incentive Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/target_db.inc");
include_once(__DIR__ . "/../manage/incentive_tiers_db.inc");
include_once($path_to_root . "/modules/knb_hrm/manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('All Employees'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

// Net invoiced value for customers assigned to this sales employee (via
// knb_distribution's customer_distribution mapping), for invoices dated
// within the target month. ABS() sidesteps FA's debtor_trans sign
// convention for the invoice type specifically (matches the pattern core's
// own sales reports use), restricted to ST_SALESINVOICE only.
function get_actual_sales($sales_employee_id, $period_month)
{
	$period_end = date('Y-m-t', strtotime($period_month));
	$sql = "SELECT SUM(ABS(dt.ov_amount + dt.ov_gst + dt.ov_freight + dt.ov_freight_tax - dt.ov_discount)) AS total
		FROM ".TB_PREF."debtor_trans dt
		JOIN ".TB_PREF."customer_distribution cd ON cd.debtor_no = dt.debtor_no
		WHERE dt.type = ".ST_SALESINVOICE."
		AND cd.sales_employee_id = ".db_escape($sales_employee_id)."
		AND dt.tran_date >= ".db_escape($period_month)."
		AND dt.tran_date <= ".db_escape($period_end);
	$result = db_query($sql, "could not get actual sales");
	$row = db_fetch($result);
	return (float)$row['total'];
}

function get_incentive_pct($achievement_pct)
{
	$sql = "SELECT incentive_pct FROM ".TB_PREF."knb_incentive_tiers
		WHERE !inactive AND ".db_escape($achievement_pct)." >= min_achievement_pct
		AND ".db_escape($achievement_pct)." <= max_achievement_pct
		ORDER BY min_achievement_pct DESC LIMIT 1";
	$result = db_query($sql, "could not get incentive tier");
	$row = db_fetch($result);
	return $row ? (float)$row['incentive_pct'] : 0;
}

if (!isset($_POST['period_month']) || $_POST['period_month'] == '')
	$_POST['period_month'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Sales Employee").':', 'sales_employee_id', @$_POST['sales_employee_id'], employee_list());
date_row(_("Period (any date in the month)").':', 'period_month');
end_table();
end_form();

$period_month = date2sql(date('m/01/Y', strtotime(date2sql($_POST['period_month']))));
$sales_employee_id = @$_POST['sales_employee_id'];

$result = get_sales_targets($sales_employee_id ?: null, $period_month, $period_month);
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Employee'), _('Period'), _('Target'), _('Actual Sales'), _('Achievement %'), _('Incentive %'), _('Incentive Amount'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	$actual = get_actual_sales($row['sales_employee_id'], $row['period_month']);
	$achievement = $row['target_amount'] > 0 ? round(($actual / $row['target_amount']) * 100, 2) : 0;
	$incentive_pct = get_incentive_pct($achievement);
	$incentive_amount = round($actual * $incentive_pct / 100, 2);

	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['period_month']));
	amount_cell($row['target_amount']);
	amount_cell($actual);
	label_cell(number_format($achievement, 2).'%');
	label_cell(number_format($incentive_pct, 2).'%');
	amount_cell($incentive_amount);
	end_row();
}
end_table();

end_page();
