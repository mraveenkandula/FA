<?php
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Incentive Tiers"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/incentive_tiers_db.inc");

simple_page_mode(true);

function can_process()
{
	if (!check_num('min_achievement_pct', 0) || !check_num('max_achievement_pct', 0))
	{
		display_error(_("Achievement % range must be non-negative numbers."));
		return false;
	}
	if (input_num('max_achievement_pct') < input_num('min_achievement_pct'))
	{
		display_error(_("Max achievement % must not be less than min achievement %."));
		return false;
	}
	if (!check_num('incentive_pct', 0))
	{
		display_error(_("Incentive % must be a non-negative number."));
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_incentive_tier(input_num('min_achievement_pct'), input_num('max_achievement_pct'), input_num('incentive_pct'));
	display_notification(_('New incentive tier has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_incentive_tier($selected_id, input_num('min_achievement_pct'), input_num('max_achievement_pct'), input_num('incentive_pct'));
	display_notification(_('Selected incentive tier has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete')
{
	delete_incentive_tier($selected_id);
	display_notification(_('Selected incentive tier has been deleted'));
	$Mode = 'RESET';
}
if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_incentive_tiers(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='60%'");
$th = array(_('Min Achievement %'), _('Max Achievement %'), _('Incentive %'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(number_format($myrow["min_achievement_pct"], 2));
	label_cell(number_format($myrow["max_achievement_pct"], 2));
	label_cell(number_format($myrow["incentive_pct"], 2));
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_incentive_tiers', 'id');
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table();

start_table(TABLESTYLE2);
if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$myrow = get_incentive_tier($selected_id);
		$_POST['min_achievement_pct'] = $myrow["min_achievement_pct"];
		$_POST['max_achievement_pct'] = $myrow["max_achievement_pct"];
		$_POST['incentive_pct'] = $myrow["incentive_pct"];
	}
	hidden('selected_id', $selected_id);
}
amount_row(_("Min Achievement %").':', 'min_achievement_pct');
amount_row(_("Max Achievement %").':', 'max_achievement_pct');
amount_row(_("Incentive %").':', 'incentive_pct');
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
