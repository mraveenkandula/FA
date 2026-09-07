<?php
$page_security = 'SA_KNB_PAYROLL_VIEW';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Professional Tax Slabs"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/professional_tax_db.inc");

simple_page_mode(true);

function can_process()
{
	if (!check_num('slab_type', 0))
	{
		display_error(_("The slab type must be a non-negative number."));
		set_focus('slab_type');
		return false;
	}
	if (!check_num('basic_amount', 0))
	{
		display_error(_("The basic amount must be a non-negative number."));
		set_focus('basic_amount');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_professional_tax_slab(input_num('slab_type'), input_num('basic_amount'));
	display_notification(_('New professional tax slab has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_professional_tax_slab($selected_id, input_num('slab_type'), input_num('basic_amount'));
	display_notification(_('Selected professional tax slab has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_professional_tax_slab($selected_id);
	display_notification(_('Selected professional tax slab has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

$result = get_all_professional_tax_slabs(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='40%'");

$th = array(_('Slab Type'), _('Basic Amount'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($myrow["slab_type"]);
	amount_cell($myrow["basic_amount"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_professional_tax_slabs', 'id');
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
		$myrow = get_professional_tax_slab($selected_id);
		$_POST['slab_type'] = $myrow["slab_type"];
		$_POST['basic_amount'] = $myrow["basic_amount"];
	}
	hidden('selected_id', $selected_id);
}

text_row_ex(_("Slab Type").':', 'slab_type', 10);
amount_row(_("Basic Amount").':', 'basic_amount');

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
