<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Employees Asset Allocations"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/asset_allocation_db.inc");
include_once(__DIR__ . "/employee_db.inc");

simple_page_mode(true);

function employee_list($with_all=false)
{
	$items = $with_all ? array('' => _('All Employees')) : array();
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function can_process()
{
	if (empty($_POST['employee_id']))
	{
		display_error(_("Select an employee."));
		return false;
	}
	if (strlen(@$_POST['asset_name']) == 0)
	{
		display_error(_("The asset name cannot be empty."));
		set_focus('asset_name');
		return false;
	}
	if (strlen(@$_POST['allocation_date']) == 0)
	{
		display_error(_("The allocation date cannot be empty."));
		set_focus('allocation_date');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process())
{
	add_asset_allocation($_POST['employee_id'], $_POST['asset_name'], $_POST['allocation_date'], $_POST['remarks']);
	display_notification(_('New asset allocation has been added'));
	$Mode = 'RESET';
}

if ($Mode=='UPDATE_ITEM' && can_process())
{
	update_asset_allocation($selected_id, $_POST['employee_id'], $_POST['asset_name'], $_POST['allocation_date'], $_POST['remarks']);
	display_notification(_('Selected asset allocation has been updated'));
	$Mode = 'RESET';
}

if ($Mode == 'Delete')
{
	delete_asset_allocation($selected_id);
	display_notification(_('Selected asset allocation has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	$sav_filter = get_post('filter_employee_id');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
	$_POST['filter_employee_id'] = $sav_filter;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Search By Employee").':', 'filter_employee_id', @$_POST['filter_employee_id'], employee_list(true), array('select_submit' => true));
end_table();

$result = get_all_asset_allocations(check_value('show_inactive'), @$_POST['filter_employee_id'] ?: null);

start_table(TABLESTYLE, "width='60%'");

$th = array(_('Employee Name'), _('Asset Name'), _('Allocation Date'), _('Remarks'), '', '');
inactive_control_column($th);
table_header($th);
$k = 0;

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars(trim($myrow['first_name'].' '.$myrow['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell($myrow["asset_name"]);
	label_cell(sql2date($myrow["allocation_date"]));
	label_cell($myrow["remarks"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'knb_asset_allocations', 'id');
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
		$myrow = get_asset_allocation($selected_id);
		$_POST['employee_id'] = $myrow["employee_id"];
		$_POST['asset_name'] = $myrow["asset_name"];
		$_POST['allocation_date'] = sql2date($myrow["allocation_date"]);
		$_POST['remarks'] = $myrow["remarks"];
	}
	hidden('selected_id', $selected_id);
}

array_selector_row(_("Employee Name").':', 'employee_id', @$_POST['employee_id'], employee_list());
text_row_ex(_("Asset Name").':', 'asset_name', 40);
date_row(_("Allocation Date").':', 'allocation_date');
text_row_ex(_("Remarks").':', 'remarks', 40);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
