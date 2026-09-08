<?php
/*
	Admin-facing provisioning screen for the Godavari Ghee ERP mobile app
	login (modules/knb_api/endpoints/login.php). An employee's mobile
	credential is not something they self-register - like the Distributor
	Portal's access page (modules/knb_distributor_portal/manage/
	distributor_access.php), office staff generate it here and hand it to
	the employee out of band (in person / over a call), the same way an
	API key is normally shown once at creation time and never again.

	Unlike the distributor portal page, the password itself is never
	admin-typed here - it's always freshly generated server-side and
	shown exactly once in the response to the save action that created
	it. There is deliberately no way to view a previously-set password;
	re-running "Generate Credentials" always overwrites with a new one.
*/
$page_security = 'SA_KNB_MOBILE_CREDENTIALS';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

page(_($help_context = "Mobile App Credentials"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/mobile_credentials_db.inc");

$generated_username = null;
$generated_password = null;
$message = null;

if (isset($_POST['GenerateCredentials']) && !empty($_POST['employee_id']) && check_csrf_token())
{
	$employee_id = $_POST['employee_id'];
	$username = trim($_POST['mobile_username']);

	if ($username === '')
	{
		$message = _("Username cannot be empty.");
	}
	elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username))
	{
		$message = _("Username must be 3-30 characters: letters, numbers, underscore or period only.");
	}
	elseif (mobile_username_exists($username, $employee_id))
	{
		$message = _("That username is already in use by another employee.");
	}
	else
	{
		$generated_password = set_mobile_credentials($employee_id, $username);
		$generated_username = $username;
	}
	$_GET['edit'] = $employee_id;
}

if ($message)
	display_error($message);

if ($generated_password !== null)
{
	$employee = get_employee_for_mobile_credentials($_GET['edit']);
	display_note(sprintf(_("Mobile app credentials set for %s. Share these with them directly - the password will not be shown again; generating new credentials replaces it."),
		trim($employee['first_name'].' '.$employee['last_name'])), 1, 1);

	start_table(TABLESTYLE2);
	echo "<tr><td class='label'>"._("Username").":</td><td>";
	echo "<input type='text' readonly value='".htmlspecialchars($generated_username, ENT_QUOTES, 'UTF-8')."'
		onclick='this.select();' style='font-family:monospace' size='30'>";
	echo "</td></tr>\n";
	echo "<tr><td class='label'>"._("Password").":</td><td>";
	echo "<input type='text' readonly value='".htmlspecialchars($generated_password, ENT_QUOTES, 'UTF-8')."'
		onclick='this.select();' style='font-family:monospace' size='30'>";
	echo " <span style='color:#900'>"._("(shown once - copy it now)")."</span>";
	echo "</td></tr>\n";
	end_table(1);
}

start_form();
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Code'), _('Name'), _('Mobile'), _('Mobile Username'), '');
table_header($th);
$k = 0;
$result = get_mobile_credential_employees();
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell($row['emp_code']);
	label_cell(htmlspecialchars(trim($row['first_name'].' '.$row['last_name']), ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['mobile'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['mobile_username'] ? htmlspecialchars($row['mobile_username'], ENT_QUOTES, 'UTF-8') : _('Not set'));
	echo "<td><a href='mobile_credentials.php?edit=".$row['id']."'>".
		($row['mobile_username'] ? _("Reset Credentials") : _("Set Up Credentials"))."</a></td>";
	end_row();
}
end_table(1);

if (isset($_GET['edit']))
{
	$employee = get_employee_for_mobile_credentials($_GET['edit']);
	if ($employee)
	{
		display_note(sprintf(_("Generate mobile app credentials for %s"),
			trim($employee['first_name'].' '.$employee['last_name'])), 1, 1);
		hidden('employee_id', $employee['id']);
		start_table(TABLESTYLE2);
		text_row(_("Username").':',
			'mobile_username',
			$employee['mobile_username'] ?: suggest_mobile_username($employee),
			30, 30);
		end_table();
		display_note(_("A new password is generated automatically when you save - there is no field for it."), 0, 1);
		submit_center('GenerateCredentials', _("Generate Credentials"), true, '', 'default');
	}
}

end_form();
end_page();
