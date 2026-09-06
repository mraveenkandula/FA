<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Customer Birthday List"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/towns_db.inc");

function town_list()
{
	$items = array('' => _('-- all towns --'));
	$result = get_all_towns(true);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

/*
	Birthdays falling within the next N days, matched on month/day only
	(MySQL DAYOFYEAR handles this without caring what year the DOB was
	recorded in) - same "upcoming birthdays" semantics as TechCloud's own
	screen, not a fixed calendar-year date range.
*/
function get_upcoming_birthdays($town_id, $days_ahead)
{
	// this year's birthday date, or next year's if this year's has already
	// passed - gives a clean non-negative day count to filter/sort by,
	// regardless of what year the DOB itself was recorded in.
	$next_occurrence = "IF(
			DATE_ADD(cd.date_of_birth, INTERVAL (YEAR(CURDATE()) - YEAR(cd.date_of_birth)) YEAR) >= CURDATE(),
			DATE_ADD(cd.date_of_birth, INTERVAL (YEAR(CURDATE()) - YEAR(cd.date_of_birth)) YEAR),
			DATE_ADD(cd.date_of_birth, INTERVAL (YEAR(CURDATE()) - YEAR(cd.date_of_birth) + 1) YEAR)
		)";
	$sql = "SELECT d.name AS customer_name, d.address, cd.phone, t.name AS town_name, cd.date_of_birth, cd.person_type,
			DATEDIFF($next_occurrence, CURDATE()) AS days_until
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = cd.town_id
		WHERE cd.date_of_birth IS NOT NULL";
	if ($town_id)
		$sql .= " AND cd.town_id=".db_escape($town_id);
	$sql .= " HAVING days_until <= ".db_escape((int)$days_ahead);
	$sql .= " ORDER BY days_until";
	return db_query($sql, "could not get upcoming birthdays");
}

if (!isset($_POST['days_ahead']) || $_POST['days_ahead'] === '')
	$_POST['days_ahead'] = 30;

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Town").':', 'town_id', @$_POST['town_id'], town_list());
amount_row(_("Show birthdays in the next N days").':', 'days_ahead');
end_table();
end_form();

$result = get_upcoming_birthdays(@$_POST['town_id'], input_num('days_ahead'));
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Customer Name'), _('Address'), _('Phone No.'), _('Town'), _('Date Of Birth'), _('Person Type'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['town_name'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['date_of_birth']));
	label_cell(htmlspecialchars($row['person_type'], ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
