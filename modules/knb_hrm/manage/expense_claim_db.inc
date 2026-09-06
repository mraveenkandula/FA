<?php

function add_expense_claim($employee_id, $claim_date, $category, $amount, $description)
{
	db_query("INSERT INTO ".TB_PREF."hr_expense_claims
		(employee_id, claim_date, category, amount, description, status)
		VALUES (".db_escape($employee_id).",".db_escape($claim_date).",".db_escape($category ?: null, true).","
		.db_escape($amount).",".db_escape($description ?: null, true).",'Pending')",
		"could not add expense claim");
}

function get_expense_claims($status=null)
{
	$sql = "SELECT c.*, e.first_name, e.last_name
		FROM ".TB_PREF."hr_expense_claims c
		JOIN ".TB_PREF."hr_employees e ON e.id = c.employee_id";
	if ($status)
		$sql .= " WHERE c.status=".db_escape($status);
	$sql .= " ORDER BY c.claim_date DESC, c.id DESC";
	return db_query($sql, "could not get expense claims");
}

function set_expense_claim_status($id, $status, $approved_by, $approved_date)
{
	db_query("UPDATE ".TB_PREF."hr_expense_claims SET status=".db_escape($status)
		.", approved_by=".db_escape($approved_by ?: null, true).", approved_date=".db_escape($approved_date ?: null, true)
		." WHERE id=".db_escape($id), "could not update expense claim status");
}
