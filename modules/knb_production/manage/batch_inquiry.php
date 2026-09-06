<?php
$page_security = 'SA_WORKORDERENTRY';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Production Batch Inquiry"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/batch_db.inc");

$stages = array('' => _('All Stages'), 'RM_TO_SFG' => _('Raw Material -> Semi-Finished Goods'), 'SFG_TO_FG' => _('Semi-Finished Goods -> Finished Goods'));

if (!isset($_POST['date_from']) || $_POST['date_from'] == '')
	$_POST['date_from'] = sql2date(date('Y-m-01'));
if (!isset($_POST['date_to']) || $_POST['date_to'] == '')
	$_POST['date_to'] = Today();

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Stage").':', 'stage', @$_POST['stage'], $stages);
date_row(_("From").':', 'date_from');
date_row(_("To").':', 'date_to');
end_table();
end_form();

$date_from = date2sql($_POST['date_from']);
$date_to = date2sql($_POST['date_to']);
$stage = @$_POST['stage'];

if ($stage)
{
	$avg = get_average_yield($stage, $date_from, $date_to);
	if ($avg && $avg['batch_count'] > 0)
		display_note(sprintf(_('Average yield for this period: %s%% across %d batches'),
			round($avg['avg_yield'], 2), $avg['batch_count']), 0, 1);
}

$result = get_production_batches($stage ?: null, $date_from, $date_to);
start_table(TABLESTYLE, "width='95%'");
$th = array(_('Date'), _('Stage'), _('Input'), _('Input Qty'), _('Output'), _('Output Qty'), _('Yield %'), _('Fat %'), _('Moisture %'), _('Incharge'));
table_header($th);
$k = 0;
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['batch_date']));
	label_cell(htmlspecialchars($row['stage'] == 'RM_TO_SFG' ? _('RM -> SFG') : _('SFG -> FG'), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['input_desc'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['input_qty']);
	label_cell(htmlspecialchars($row['output_desc'], ENT_QUOTES, 'UTF-8'));
	amount_cell($row['output_qty']);
	label_cell($row['yield_percent'] !== null ? number_format((float)$row['yield_percent'], 2) : '');
	label_cell($row['fat_content'] !== null ? number_format((float)$row['fat_content'], 2) : '');
	label_cell($row['moisture_percent'] !== null ? number_format((float)$row['moisture_percent'], 2) : '');
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	end_row();
}
end_table();

end_page();
