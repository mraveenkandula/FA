<?php

function add_expense_claim($sales_employee_id, $expense_date, $category, $amount, $description)
{
	db_query("INSERT INTO ".TB_PREF."knb_expense_claims
		(sales_employee_id, expense_date, category, amount, description, status, created_date)
		VALUES (".db_escape($sales_employee_id).",".db_escape($expense_date).","
		.db_escape($category).",".db_escape($amount).",".db_escape($description ?: null, true).", 0, NOW())",
		"could not add expense claim");
}

function get_expense_claims($sales_employee_id=null, $status=null, $date_from=null, $date_to=null)
{
	$sql = "SELECT ec.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_expense_claims ec
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = ec.sales_employee_id
		WHERE 1=1";
	if ($sales_employee_id)
		$sql .= " AND ec.sales_employee_id=".db_escape($sales_employee_id);
	if ($status !== null && $status !== '')
		$sql .= " AND ec.status=".db_escape($status);
	if ($date_from)
		$sql .= " AND ec.expense_date >= ".db_escape($date_from);
	if ($date_to)
		$sql .= " AND ec.expense_date <= ".db_escape($date_to);
	$sql .= " ORDER BY ec.expense_date DESC, ec.id DESC";
	return db_query($sql, "could not get expense claims");
}

function set_expense_claim_status($id, $status, $approved_by)
{
	db_query("UPDATE ".TB_PREF."knb_expense_claims
		SET status=".db_escape($status).", approved_by=".db_escape($approved_by).", approved_date=NOW()
		WHERE id=".db_escape($id), "could not update expense claim status");
}
