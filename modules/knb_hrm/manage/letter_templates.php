<?php
$page_security = 'SA_KNB_LETTER_MANAGE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

page(_($help_context = "Letter Templates"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/letter_db.inc");

simple_page_mode(true);

function can_process()
{
	if (strlen(trim($_POST['name'])) == 0)
	{
		display_error(_("The template name cannot be empty."));
		set_focus('name');
		return false;
	}
	if (strlen(trim($_POST['body'])) == 0)
	{
		display_error(_("The letter body cannot be empty."));
		set_focus('body');
		return false;
	}
	return true;
}

if ($Mode=='ADD_ITEM' && can_process() && check_csrf_token())
{
	add_letter_template($_POST['name'], $_POST['subject'], $_POST['body']);
	display_notification(_('New letter template has been added'));
	$Mode = 'RESET';
}
if ($Mode=='UPDATE_ITEM' && can_process() && check_csrf_token())
{
	update_letter_template($selected_id, $_POST['name'], $_POST['subject'], $_POST['body']);
	display_notification(_('Selected letter template has been updated'));
	$Mode = 'RESET';
}
if ($Mode == 'Delete' && check_csrf_token())
{
	delete_letter_template($selected_id);
	display_notification(_('Selected letter template has been deleted'));
	$Mode = 'RESET';
}
if ($Mode == 'RESET')
{
	$selected_id = -1;
	unset($_POST);
}

$result = get_all_letter_templates(true);

display_note(_("Available placeholders: {{first_name}}, {{last_name}}, {{full_name}}, {{emp_code}}, {{department}}, {{designation}}, {{hire_date}}, {{today}}, {{company_name}}"), 0, 1);

start_form();
start_table(TABLESTYLE, "width='70%'");
$th = array(_('Template Name'), _('Subject'), '', '');
table_header($th);
$k = 0;
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($myrow["name"], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($myrow["subject"], ENT_QUOTES, 'UTF-8'));
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}
end_table();

start_table(TABLESTYLE2);
if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$myrow = get_letter_template($selected_id);
		$_POST['name'] = $myrow["name"];
		$_POST['subject'] = $myrow["subject"];
		$_POST['body'] = $myrow["body"];
	}
	hidden('selected_id', $selected_id);
}
text_row_ex(_("Template Name").':', 'name', 40);
text_row_ex(_("Subject").':', 'subject', 60);
textarea_row(_("Body").':', 'body', null, 70, 12);
end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
end_form();
end_page();
