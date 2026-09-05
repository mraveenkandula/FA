<?php
$page_security = 'SA_RECONCILE';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Bank Statement Matching"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_bank_accounts.inc");
include_once(__DIR__ . "/../manage/statement_db.inc");

if (!isset($_POST['bank_account']) || $_POST['bank_account'] === '')
	$_POST['bank_account'] = 1;

$ignore_id = find_submit('Ignore_');
if ($ignore_id != -1 && check_csrf_token())
{
	ignore_line($ignore_id);
	display_notification(_('Statement line marked as ignored.'));
}

$unmatch_id = find_submit('Unmatch_');
if ($unmatch_id != -1 && check_csrf_token())
{
	unmatch_line($unmatch_id);
	display_notification(_('Match removed - transaction is unreconciled again.'));
}

$match_id = find_submit('DoMatch_');
if ($match_id != -1 && check_csrf_token() && !empty($_POST['match_trans_'.$match_id]))
{
	$line = get_statement_line($match_id);
	manual_match_line($match_id, $_POST['match_trans_'.$match_id], sql2date($line['txn_date']));
	display_notification(_('Statement line matched.'));
}

start_form();

start_table(TABLESTYLE2);
bank_accounts_list_row(_("Bank Account").':', 'bank_account', null, true);
end_table();

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
		$id = $row['id'];
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
			$opts = "<select name='match_trans_$id'><option value=''>"._('-- select transaction --')."</option>";
			foreach ($candidates as $c)
				$opts .= "<option value='".(int)$c['id']."'>".htmlspecialchars(sql2date($c['trans_date'])." | ".$c['ref']." | ".number_format($c['amount'], 2), ENT_QUOTES, 'UTF-8')."</option>";
			$opts .= "</select>";
			label_cell($opts
				." <input type='submit' name='DoMatch_$id' value='"._('Match')."' class='inputsubmit'>"
				." <input type='submit' name='Ignore_$id' value='"._('Ignore')."' class='inputsubmit'>");
		}
		else if ($status == 'matched')
		{
			label_cell("<input type='submit' name='Unmatch_$id' value='"._('Undo match')."' class='inputsubmit'>");
		}
		else
		{
			label_cell('');
		}
		end_row();
	}
	end_table();
}

end_form();

end_page();
