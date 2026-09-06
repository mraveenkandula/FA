<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Schemes Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/schemes_db.inc");

$result = get_all_schemes(true);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Brand'), _('Start Date'), _('End Date'), _('Slab'), _('Slab Target (cases)'), _('Scheme'), _('Person Type'), _('State'), _('Territory'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	$slabs = get_scheme_slabs($row['id']);
	if (count($slabs) == 0)
		$slabs = array(array('slab_no' => '', 'slab_target_qty' => '', 'scheme_text' => ''));
	foreach ($slabs as $slab)
	{
		alt_table_row_color($k);
		label_cell(htmlspecialchars($row['brand_name'], ENT_QUOTES, 'UTF-8'));
		label_cell(sql2date($row['start_date']));
		label_cell(sql2date($row['end_date']));
		label_cell($slab['slab_no']);
		amount_cell($slab['slab_target_qty']);
		label_cell(htmlspecialchars($slab['scheme_text'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['person_type'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['state'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['territory_name'], ENT_QUOTES, 'UTF-8'));
		end_row();
	}
}
end_table();

end_page();
