<?php
/*
	Admin-facing provisioning screen for the Distributor Portal. A
	distributor is a customer (debtors_master), not an FA user, so there
	is no self-registration - staff set a portal username/password here,
	same as any other account-provisioning workflow, and hand the
	credentials to the distributor directly.
*/
$page_security = 'SA_CUSTOMER';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Distributor Portal Access"));

include_once($path_to_root . "/includes/ui.inc");
include_once(__DIR__ . "/distributor_access_db.inc");

$selected_id = null;
$message = null;

if (isset($_POST['SetCredentials']) && !empty($_POST['debtor_no']) && check_csrf_token())
{
	$username = trim($_POST['portal_username']);
	$password = $_POST['portal_password'];

	if ($username === '' || $password === '')
	{
		$message = _("Username and password are both required.");
	}
	elseif (strlen($password) < 6)
	{
		$message = _("Password must be at least 6 characters.");
	}
	else
	{
		$result = db_query("SELECT debtor_no FROM ".TB_PREF."debtors_master
			WHERE portal_username=".db_escape($username)." AND debtor_no != ".db_escape($_POST['debtor_no']));
		if (db_fetch($result))
		{
			$message = _("That username is already in use by another distributor.");
		}
		else
		{
			set_portal_credentials($_POST['debtor_no'], $username, $password);
			$message = sprintf(_("Portal access enabled for %s. Share the username and password with them directly."), $username);
		}
	}
	$selected_id = $_POST['debtor_no'];
}

$toggle_on_id = find_submit('ToggleOn_');
$toggle_off_id = find_submit('ToggleOff_');
if (($toggle_on_id != -1 || $toggle_off_id != -1) && check_csrf_token())
{
	if ($toggle_on_id != -1)
	{
		set_portal_active($toggle_on_id, true);
		$selected_id = $toggle_on_id;
	}
	if ($toggle_off_id != -1)
	{
		set_portal_active($toggle_off_id, false);
		$selected_id = $toggle_off_id;
	}
}

if ($message)
	display_notification($message);

start_form();
start_table(TABLESTYLE, "width='90%'");
$th = array(_('Distributor'), _('Ref'), _('Type'), _('Portal Username'), _('Portal Access'), '');
table_header($th);
$k = 0;
$result = get_portal_eligible_customers();
while ($row = db_fetch($result))
{
	alt_table_row_color($k);
	label_cell(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
	label_cell(htmlspecialchars($row['debtor_ref'], ENT_QUOTES, 'UTF-8'));
	label_cell($row['person_type'] == 1 ? _('Super Stockist') : _('Distributor'));
	label_cell($row['portal_username'] ? htmlspecialchars($row['portal_username'], ENT_QUOTES, 'UTF-8') : _('Not set'));
	label_cell($row['portal_active'] ? _('Active') : ($row['portal_username'] ? _('Disabled') : '-'));
	echo "<td>";
	echo "<a href='distributor_access.php?edit=".$row['debtor_no']."'>"._("Set Password")."</a>";
	if ($row['portal_username'])
	{
		$toggle_name = ($row['portal_active'] ? 'ToggleOff_' : 'ToggleOn_').$row['debtor_no'];
		echo " <button type='submit' name='".$toggle_name."' value='1'>";
		echo ($row['portal_active'] ? _("Disable") : _("Enable"));
		echo "</button>";
	}
	echo "</td>";
	end_row();
}
end_table(1);

if (isset($_GET['edit']))
{
	$customer = get_customer_for_portal($_GET['edit']);
	if ($customer)
	{
		display_note(sprintf(_("Set portal credentials for %s"), $customer['name']), 1, 1);
		hidden('debtor_no', $customer['debtor_no']);
		start_table(TABLESTYLE2);
		text_row(_("Username").':', 'portal_username', $customer['portal_username'] ?: $customer['debtor_ref'], 30, 60);
		text_row(_("New Password").':', 'portal_password', '', 30, 60);
		end_table();
		submit_center('SetCredentials', _("Save"), true, '', 'default');
	}
}

end_form();
end_page();
