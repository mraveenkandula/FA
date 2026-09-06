<?php
require_once(__DIR__ . '/includes/portal_bootstrap.inc');
require_once(__DIR__ . '/includes/stock_check_db.inc');

$customer = portal_require_login();
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_demand']))
{
	if (portal_csrf_check() && isset($_POST['demand']) && is_array($_POST['demand']))
	{
		foreach ($_POST['demand'] as $stock_id => $qty)
		{
			$qty = is_numeric($qty) ? (float)$qty : 0;
			save_demand($customer['debtor_no'], $stock_id, $qty);
		}
		$saved = true;
	}
}

$qoh = get_qoh_map($customer['loc_code']);
$ordered = get_ordered_map($customer['debtor_no']);
$demand = get_demand_map($customer['debtor_no']);

portal_page_start(_('Stock Check'));
?>
<div class="knb-header">
	<a class="knb-logout" href="logout.php"><?php echo _('Logout'); ?></a>
	<h1><?php echo htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
	<div class="sub"><?php echo _('Stock Check - QOH / Demand / Ordered'); ?></div>
</div>
<div class="knb-body">
<?php if (!$customer['loc_code']): ?>
	<div class="knb-error"><?php echo _('No stock location is linked to your account yet - QOH cannot be shown until one is set up. Contact KNB Group to have this configured.'); ?></div>
<?php endif; ?>
<?php if ($saved): ?>
	<div class="knb-card" style="color:#2e6e2e;"><?php echo _('Your demand has been saved.'); ?></div>
<?php endif; ?>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo portal_csrf_token(); ?>">
		<table>
			<tr><th><?php echo _('Item'); ?></th><th><?php echo _('Units'); ?></th>
				<th><?php echo _('QOH'); ?></th><th><?php echo _('Ordered'); ?></th><th><?php echo _('Demand'); ?></th></tr>
<?php
$result = get_stock_check_items();
while ($row = db_fetch($result))
{
	$stock_id = $row['stock_id'];
	echo '<tr>';
	echo '<td>'.htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8').'</td>';
	echo '<td>'.htmlspecialchars($row['units'], ENT_QUOTES, 'UTF-8').'</td>';
	echo '<td>'.number_format(isset($qoh[$stock_id]) ? $qoh[$stock_id] : 0, 2).'</td>';
	echo '<td>'.number_format(isset($ordered[$stock_id]) ? $ordered[$stock_id] : 0, 2).'</td>';
	echo '<td><input type="number" step="0.01" min="0" name="demand['.htmlspecialchars($stock_id, ENT_QUOTES, 'UTF-8').']" value="'.(isset($demand[$stock_id]) ? htmlspecialchars($demand[$stock_id], ENT_QUOTES, 'UTF-8') : '0').'"></td>';
	echo '</tr>';
}
?>
		</table>
		<p><button type="submit" name="save_demand" value="1"><?php echo _('Save Demand'); ?></button></p>
	</form>
</div>
<?php
portal_page_end();
