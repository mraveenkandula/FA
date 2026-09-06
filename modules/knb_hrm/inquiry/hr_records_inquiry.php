<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "HR Records Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/hr_records_db.inc");

display_note(_("Holidays"), 0, 1);
$result = get_holidays();
start_table(TABLESTYLE, "width='50%'");
table_header(array(_('Name'), _('Date')));
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['holiday_date']));
	end_row();
}
end_table(1);

display_note(_("Employee Grievances"), 1, 1);
$result = get_grievances();
start_table(TABLESTYLE, "width='90%'");
table_header(array(_('Name'), _('Email'), _('Subject'), _('Message'), _('Submitted')));
$k = 0;
$any = false;
while ($row = db_fetch($result))
{
	$any = true;
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['submitted_at']);
	end_row();
}
end_table(1);
if (!$any)
	display_note(_("No grievances on record."), 0, 1);

display_note(_("Local Conveyance"), 1, 1);
$result = get_local_conveyance();
start_table(TABLESTYLE, "width='95%'");
table_header(array(_('Employee'), _('Date'), _('From'), _('To'), _('Mode'), _('KM'), _('Parking'), _('Total')));
$k = 0;
$any = false;
while ($row = db_fetch($result))
{
	$any = true;
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(sql2date($row['travel_date']));
	label_cell(htmlspecialchars($row['from_place'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['to_place'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['mode'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['km']);
	amount_cell($row['parking']);
	amount_cell($row['total']);
	end_row();
}
end_table(1);
if (!$any)
	display_note(_("No local conveyance claims on record."), 0, 1);

display_note(_("Notice Periods"), 1, 1);
$result = get_notice_periods();
start_table(TABLESTYLE, "width='60%'");
table_header(array(_('Employee'), _('Notice Days'), _('Start Date'), _('End Date')));
$k = 0;
$any = false;
while ($row = db_fetch($result))
{
	$any = true;
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell($row['notice_days']);
	label_cell(sql2date($row['start_date']));
	label_cell(sql2date($row['end_date']));
	end_row();
}
end_table(1);
if (!$any)
	display_note(_("No notice periods on record."), 0, 1);

end_page();
