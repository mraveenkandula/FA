<?php

/*
	Insert one parsed statement line and immediately try to auto-match it.
	Returns 'matched' or 'unmatched'.
*/
function import_statement_line($bank_account_id, $bank_act_code, $txn_date, $amount, $description, $reference, $balance_after, $batch)
{
	$sql = "INSERT INTO ".TB_PREF."bank_statement_lines
		(bank_account_id, txn_date, amount, description, reference, balance_after, import_batch, status)
		VALUES (".db_escape($bank_account_id).",".db_escape($txn_date).",".db_escape($amount).","
		.db_escape($description ?: null, true).",".db_escape($reference ?: null, true).","
		.db_escape($balance_after !== null ? $balance_after : null, true).","
		.db_escape($batch).",'unmatched')";
	db_query($sql, "could not insert bank statement line");
	$line_id = db_insert_id();

	$candidates = find_match_candidates($bank_act_code, $txn_date, $amount);
	if (count($candidates) == 1)
	{
		auto_match_line($line_id, $candidates[0]['id'], $txn_date);
		return 'matched';
	}
	return 'unmatched';
}

/*
	Find existing (not yet reconciled) bank_trans rows on the same account
	with the same amount, within a few days of the statement line date.
	Kept deliberately conservative: only an UNAMBIGUOUS single match auto-links.
*/
function find_match_candidates($bank_act_code, $txn_date, $amount, $day_tolerance=5)
{
	$sql = "SELECT id, type, trans_no, ref, trans_date, amount FROM ".TB_PREF."bank_trans
		WHERE bank_act = ".db_escape($bank_act_code)."
		AND reconciled IS NULL
		AND amount = ".db_escape($amount)."
		AND ABS(DATEDIFF(trans_date, ".db_escape($txn_date).")) <= ".(int)$day_tolerance."
		ORDER BY ABS(DATEDIFF(trans_date, ".db_escape($txn_date).")) ASC";
	$result = db_query($sql, "could not search bank transactions");
	$rows = array();
	while ($row = db_fetch($result))
		$rows[] = $row;
	return $rows;
}

function auto_match_line($line_id, $bank_trans_id, $statement_date)
{
	db_query("UPDATE ".TB_PREF."bank_trans SET reconciled=".db_escape($statement_date)."
		WHERE id=".db_escape($bank_trans_id), "could not mark transaction reconciled");
	db_query("UPDATE ".TB_PREF."bank_statement_lines SET status='matched', matched_bank_trans_id=".db_escape($bank_trans_id)."
		WHERE id=".db_escape($line_id), "could not update statement line");
}

function manual_match_line($line_id, $bank_trans_id, $statement_date)
{
	auto_match_line($line_id, $bank_trans_id, $statement_date);
}

function ignore_line($line_id)
{
	db_query("UPDATE ".TB_PREF."bank_statement_lines SET status='ignored' WHERE id=".db_escape($line_id),
		"could not update statement line");
}

function unmatch_line($line_id)
{
	$line = get_statement_line($line_id);
	if ($line['matched_bank_trans_id'])
		db_query("UPDATE ".TB_PREF."bank_trans SET reconciled=NULL WHERE id=".db_escape($line['matched_bank_trans_id']),
			"could not clear reconciled flag");
	db_query("UPDATE ".TB_PREF."bank_statement_lines SET status='unmatched', matched_bank_trans_id=NULL WHERE id=".db_escape($line_id),
		"could not update statement line");
}

function get_statement_line($id)
{
	$sql = "SELECT * FROM ".TB_PREF."bank_statement_lines WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get statement line");
	return db_fetch($result);
}

function get_statement_lines($bank_account_id, $status=null)
{
	$sql = "SELECT sl.*, ba.bank_account_name
		FROM ".TB_PREF."bank_statement_lines sl
		JOIN ".TB_PREF."bank_accounts ba ON ba.id = sl.bank_account_id
		WHERE sl.bank_account_id=".db_escape($bank_account_id);
	if ($status)
		$sql .= " AND sl.status=".db_escape($status);
	$sql .= " ORDER BY sl.txn_date DESC, sl.id DESC";
	return db_query($sql, "could not get statement lines");
}

function get_match_candidates_for_line($line_id)
{
	$line = get_statement_line($line_id);
	$account = get_bank_account($line['bank_account_id']);
	return find_match_candidates($account['account_code'], $line['txn_date'], $line['amount'], 30);
}
