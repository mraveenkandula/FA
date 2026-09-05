<?php
$page_security = 'SA_OPEN';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Import Bank Statement"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_bank_accounts.inc");
include_once(__DIR__ . "/statement_db.inc");

/*
	Expected CSV format (matches what most Indian bank net-banking exports
	produce): a header row containing Date / Description / Reference /
	Debit / Credit / Balance (case-insensitive, any order); one transaction
	per row after that. Debit and Credit can be two separate columns, or a
	single signed Amount column - both are supported.
*/
function parse_statement_csv($filepath)
{
	$rows = array();
	if (($fh = fopen($filepath, 'r')) === false)
		return array(null, _("Could not open the uploaded file."));

	$header = fgetcsv($fh);
	if ($header === false)
	{
		fclose($fh);
		return array(null, _("The file appears to be empty."));
	}
	$col = array();
	foreach ($header as $i => $name)
		$col[strtolower(trim($name))] = $i;

	$date_col = null;
	foreach (array('date', 'txn date', 'transaction date', 'value date') as $k)
		if (isset($col[$k])) { $date_col = $col[$k]; break; }
	$desc_col = null;
	foreach (array('description', 'narration', 'particulars') as $k)
		if (isset($col[$k])) { $desc_col = $col[$k]; break; }
	$ref_col = null;
	foreach (array('reference', 'ref', 'cheque no', 'chq no', 'utr') as $k)
		if (isset($col[$k])) { $ref_col = $col[$k]; break; }
	$debit_col = isset($col['debit']) ? $col['debit'] : (isset($col['withdrawal']) ? $col['withdrawal'] : null);
	$credit_col = isset($col['credit']) ? $col['credit'] : (isset($col['deposit']) ? $col['deposit'] : null);
	$amount_col = isset($col['amount']) ? $col['amount'] : null;
	$balance_col = isset($col['balance']) ? $col['balance'] : null;

	if ($date_col === null || ($debit_col === null && $credit_col === null && $amount_col === null))
	{
		fclose($fh);
		return array(null, _("Could not find recognizable Date and Debit/Credit (or Amount) columns in the file header."));
	}

	while (($line = fgetcsv($fh)) !== false)
	{
		if (count($line) < 2 || $line[$date_col] === '')
			continue;

		$amount = null;
		if ($amount_col !== null)
		{
			$amount = (float)str_replace(array(',', ' '), '', $line[$amount_col]);
		}
		else
		{
			$debit = $debit_col !== null && isset($line[$debit_col]) ? (float)str_replace(array(',', ' '), '', $line[$debit_col]) : 0;
			$credit = $credit_col !== null && isset($line[$credit_col]) ? (float)str_replace(array(',', ' '), '', $line[$credit_col]) : 0;
			$amount = $credit - $debit;
		}

		$rows[] = array(
			'date' => trim($line[$date_col]),
			'description' => $desc_col !== null && isset($line[$desc_col]) ? trim($line[$desc_col]) : '',
			'reference' => $ref_col !== null && isset($line[$ref_col]) ? trim($line[$ref_col]) : '',
			'amount' => $amount,
			'balance' => $balance_col !== null && isset($line[$balance_col]) && $line[$balance_col] !== ''
				? (float)str_replace(array(',', ' '), '', $line[$balance_col]) : null,
		);
	}
	fclose($fh);
	return array($rows, null);
}

function parse_statement_date($raw)
{
	// Accept DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD
	$raw = trim($raw);
	if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m))
		return "$m[1]-$m[2]-$m[3]";
	if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $raw, $m))
		return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
	return null;
}

if (isset($_POST['DoImport']) && isset($_FILES['statement_file']) && $_FILES['statement_file']['error'] == UPLOAD_ERR_OK)
{
	list($rows, $err) = parse_statement_csv($_FILES['statement_file']['tmp_name']);
	if ($err)
	{
		display_error($err);
	}
	else
	{
		$account = get_bank_account($_POST['bank_account']);
		$batch = date('YmdHis');
		$matched = 0; $unmatched = 0; $skipped = 0;

		foreach ($rows as $row)
		{
			$sql_date = parse_statement_date($row['date']);
			if (!$sql_date || $row['amount'] === null)
			{
				$skipped++;
				continue;
			}
			$result = import_statement_line(
				$_POST['bank_account'], $account['account_code'], $sql_date, $row['amount'],
				$row['description'], $row['reference'], $row['balance'], $batch
			);
			if ($result == 'matched') $matched++; else $unmatched++;
		}

		display_notification(sprintf(
			_('Imported %d transactions: %d auto-matched, %d need review, %d skipped (unrecognized date/amount).'),
			$matched + $unmatched, $matched, $unmatched, $skipped
		));
	}
}

start_form(true);

start_table(TABLESTYLE2);
bank_accounts_list_row(_("Bank Account").':', 'bank_account', null);
file_row(_("Statement File (CSV)").':', 'statement_file');
end_table();

display_note(_("CSV header row should contain Date and either Debit/Credit columns or a single signed Amount column. Description/Reference/Balance columns are optional but improve matching and audit trail."), 0, 1);

submit_center('DoImport', _("Import Statement"));

end_form();

end_page();
