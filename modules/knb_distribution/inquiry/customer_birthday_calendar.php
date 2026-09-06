<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Customers Birthday Calendar"));

include_once($path_to_root . "/includes/ui.inc");

function get_birthdays_in_month($month, $year)
{
	$sql = "SELECT d.name AS customer_name, DAY(cd.date_of_birth) AS day_of_month
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		WHERE cd.date_of_birth IS NOT NULL AND MONTH(cd.date_of_birth) = ".db_escape($month)."
		ORDER BY DAY(cd.date_of_birth), d.name";
	$result = db_query($sql, "could not get birthdays in month");
	$by_day = array();
	while ($row = db_fetch($result))
		$by_day[$row['day_of_month']][] = $row['customer_name'];
	return $by_day;
}

if (!isset($_POST['cal_month']) || $_POST['cal_month'] === '')
	$_POST['cal_month'] = date('n');
if (!isset($_POST['cal_year']) || $_POST['cal_year'] === '')
	$_POST['cal_year'] = date('Y');

$months = array();
for ($m = 1; $m <= 12; $m++)
	$months[$m] = date('F', mktime(0, 0, 0, $m, 1));

start_form();
start_table(TABLESTYLE2);
start_row();
array_selector_row(_("Month").':', 'cal_month', @$_POST['cal_month'], $months);
end_row();
end_table();
end_form();

$month = (int)$_POST['cal_month'];
$year = (int)$_POST['cal_year'];
$days_in_month = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$first_weekday = (int)date('w', mktime(0, 0, 0, $month, 1, $year)); // 0=Sun

$by_day = get_birthdays_in_month($month, $year);

display_heading($months[$month]." ".$year);

echo "<table class='tablestyle' width='95%' cellpadding='4' cellspacing='0'>";
echo "<tr>";
foreach (array(_('Sun'), _('Mon'), _('Tue'), _('Wed'), _('Thu'), _('Fri'), _('Sat')) as $wd)
	echo "<td class='tableheader' width='14%'>$wd</td>";
echo "</tr><tr>";

for ($i = 0; $i < $first_weekday; $i++)
	echo "<td>&nbsp;</td>";

$col = $first_weekday;
for ($day = 1; $day <= $days_in_month; $day++)
{
	if ($col == 7)
	{
		echo "</tr><tr>";
		$col = 0;
	}
	echo "<td valign='top' style='height:70px;'><b>$day</b><br>";
	if (isset($by_day[$day]))
		foreach ($by_day[$day] as $name)
			echo "<span style='font-size:11px;'>".htmlspecialchars($name, ENT_QUOTES, 'UTF-8')."</span><br>";
	echo "</td>";
	$col++;
}
while ($col < 7)
{
	echo "<td>&nbsp;</td>";
	$col++;
}
echo "</tr></table>";

end_page();
