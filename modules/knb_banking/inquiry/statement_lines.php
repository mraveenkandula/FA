<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Bank Statement Matching"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_bank_accounts.inc");
include_once(__DIR__ . "/../manage/statement_db.inc");

if (!isset($_POST['bank_account']) || $_POST['bank_account'] === '')
	$_POST['bank_account'] = 1;

if (isset($_POST['Ignore']) && isset($_POST['line_id']))
{
	ignore_line($_POST['line_id']);
	display_notification(_('Statement line marked as ignored.'));
}
if (isset($_POST['Unmatch']) && isset($_POST['line_id']))
{
	unmatch_line($_POST['line_id']);
	display_notification(_('Match removed - transaction is unreconciled again.'));
}
if (isset($_POST['DoMatch']) && isset($_POST['line_id']) && !empty($_POST['match_trans_id']))
{
	$line = get_statement_line($_POST['line_id']);
	manual_match_line($_POST['line_id'], $_POST['match_trans_id'], sql2date($line['txn_date']));
	display_notification(_('Statement line matched.'));
}

start_form();
start_table(TABLESTYLE2);
bank_accounts_list_row(_("Bank Account").':', 'bank_account', null, true);
end_table();
end_form();

$statuses = array('unmatched' => _('Needs review'), 'matched' => _('Matched'), 'ignored' => _('Ignored'));

foreach ($statuses as $status => $status_label)
{
	$result = get_statement_lines($_POST['bank_account'], $status);
	$rows = array();
	while ($row = db_fetch($result))
		$rows[] = $row;

	display_heading($status_label . ' (' . count($rows) . ')');

	if (count($rows) == 0)
	{
		display_note(_('None.'), 0, 1);
		continue;
	}

	start_table(TABLESTYLE, "width='95%'");
	$th = array(_('Date'), _('Description'), _('Reference'), _('Amount'), _('Balance'), '');
	table_header($th);
	$k = 0;

	foreach ($rows as $row)
	{
		alt_table_row_color($k);
		label_cell(sql2date($row['txn_date']));
		label_cell(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'));
		amount_cell($row['amount']);
		label_cell($row['balance_after'] !== null ? number_format((float)$row['balance_after'], 2) : '');

		if ($status == 'unmatched')
		{
			$candidates = find_match_candidates(
				get_bank_account($row['bank_account_id'])['account_code'], $row['txn_date'], $row['amount'], 30
			);
			$opts = "<select name='match_trans_id'><option value=''>"._('-- select transaction --')."</option>";
			foreach ($candidates as $c)
				$opts .= "<option value='".$c['id']."'>".sql2date($c['trans_date'])." | ".htmlspecialchars($c['ref'], ENT_QUOTES, 'UTF-8')." | ".number_format($c['amount'], 2)."</option>";
			$opts .= "</select>";
			label_cell("<form method='post' style='margin:0'>"
				."<input type='hidden' name='line_id' value='".$row['id']."'>"
				.$opts
				." <input type='submit' name='DoMatch' value='"._('Match')."' class='inputsubmit'>"
				." <input type='submit' name='Ignore' value='"._('Ignore')."' class='inputsubmit'>"
				."</form>");
		}
		else if ($status == 'matched')
		{
			label_cell("<form method='post' style='margin:0'>"
				."<input type='hidden' name='line_id' value='".$row['id']."'>"
				."<input type='submit' name='Unmatch' value='"._('Undo match')."' class='inputsubmit'>"
				."</form>");
		}
		else
		{
			label_cell('');
		}
		end_row();
	}
	end_table();
}

end_page();
