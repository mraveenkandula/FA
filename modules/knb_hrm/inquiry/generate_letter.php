<?php
$page_security = 'SA_KNB_LETTER_MANAGE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

page(_($help_context = "Generate Letter"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/../manage/letter_db.inc");
include_once(__DIR__ . "/../manage/employee_db.inc");

function employee_list()
{
	$items = array('' => _('-- select an employee --'));
	$result = get_all_employees(true);
	while ($row = db_fetch($result)) $items[$row['id']] = trim($row['first_name'].' '.$row['last_name']);
	return $items;
}

function template_list()
{
	$items = array('' => _('-- select a template --'));
	$result = get_all_letter_templates(false);
	while ($row = db_fetch($result)) $items[$row['id']] = $row['name'];
	return $items;
}

start_form();
start_table(TABLESTYLE2);
array_selector_row(_("Employee").':', 'employee_id', @$_POST['employee_id'], employee_list());
array_selector_row(_("Letter Template").':', 'template_id', @$_POST['template_id'], template_list());
end_table();
submit_center('Generate', _("Generate"), true, '', 'default');
end_form();

if (!empty($_POST['employee_id']) && !empty($_POST['template_id']))
{
	$employee = get_employee_for_letter($_POST['employee_id']);
	$template = get_letter_template($_POST['template_id']);

	if ($employee && $template)
	{
		$subject = merge_letter_text($template['subject'], $employee);
		$body = merge_letter_text($template['body'], $employee);

		echo "<div id='knb-letter-print' style='background:#fff;border:1px solid #C9BFA0;border-radius:8px;padding:24px;max-width:800px;margin:16px auto;'>";
		if ($subject !== '')
			echo "<h3>".htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')."</h3>";
		echo "<div style='white-space:pre-wrap;font-family:Georgia,serif;'>".htmlspecialchars($body, ENT_QUOTES, 'UTF-8')."</div>";
		echo "</div>";
		echo "<div style='text-align:center;'><button type='button' onclick='window.print()'>"._("Print")."</button></div>";
		echo "<style>@media print { .no-print, #controls, .navigation, .no_print { display: none !important; } }</style>";
	}
}

end_page();
