<?php
require_once(__DIR__ . '/includes/portal_bootstrap.inc');

$error = null;

if (!empty($_SESSION['portal_debtor_no']))
{
	header('Location: stock_check.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	if (!portal_csrf_check())
	{
		$error = _('Your session expired, please try again.');
	}
	else
	{
		$username = trim(@$_POST['username']);
		$password = @$_POST['password'];

		$result = db_query("SELECT debtor_no, name, portal_password_hash FROM ".TB_PREF."debtors_master
			WHERE portal_username=".db_escape($username)." AND portal_active=1",
			"could not look up distributor");
		$customer = db_fetch($result);

		if (!$customer || !$customer['portal_password_hash'] || !password_verify($password, $customer['portal_password_hash']))
			$error = _('Invalid username or password.');
		else
		{
			session_regenerate_id(true);
			$_SESSION['portal_debtor_no'] = $customer['debtor_no'];
			header('Location: stock_check.php');
			exit;
		}
	}
}

portal_page_start(_('Distributor Login'));
?>
<div class="knb-header"><h1>KNB Group - Godavari Ghee</h1><div class="sub"><?php echo _('Distributor Portal'); ?></div></div>
<div class="knb-body">
	<div class="knb-card" style="max-width:360px;margin:40px auto;">
		<h2 style="margin-top:0;"><?php echo _('Login'); ?></h2>
		<?php if ($error): ?>
			<div class="knb-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
		<?php endif; ?>
		<form method="post">
			<input type="hidden" name="csrf_token" value="<?php echo portal_csrf_token(); ?>">
			<p><label><?php echo _('Username'); ?></label><br>
				<input type="text" name="username" required autofocus></p>
			<p><label><?php echo _('Password'); ?></label><br>
				<input type="password" name="password" required></p>
			<button type="submit"><?php echo _('Login'); ?></button>
		</form>
	</div>
</div>
<?php
portal_page_end();
