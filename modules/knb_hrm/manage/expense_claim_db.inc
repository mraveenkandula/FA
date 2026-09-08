<?php

/*
	TA/DA (Travel Allowance / Dearness Allowance) manual claim entry reuses this
	same table via a `claim_type` column ('Expense' | 'TADA') rather than a new
	table - the shape is identical (employee submits a dated claim with an
	amount and a reason/description, awaits approval via the same
	expense_claim_approval.php office screen). See modules/knb_api/endpoints/
	tada_claim_get.php / tada_claim_post.php for the TA/DA-specific endpoints;
	expense_claim_get.php/post.php keep hardcoding claim_type='Expense' so
	existing behaviour (and the existing mobile Expense Claims screen) is
	unchanged - TA/DA claims never show up there, and vice versa.

	`claim_type` is added defensively at runtime, same self-healing pattern as
	modules/knb_inventory_ext/manage/price_import_db.inc's apply_price_import()
	- this install never runs knb_hrm_expense_claims.sql's own ALTER line via
	FA's package installer for hand-placed modules, so the actual mechanism is
	this runtime ALTER, not the .sql file (kept in sync there for documentation
	only). ADD COLUMN IF NOT EXISTS is supported on this install's MariaDB
	(12.3.3 - confirmed already relied on by modules/knb_api/sql/knb_api.sql).
*/
function ensure_expense_claim_schema()
{
	db_query("ALTER TABLE ".TB_PREF."hr_expense_claims
		ADD COLUMN IF NOT EXISTS claim_type varchar(20) NOT NULL DEFAULT 'Expense'",
		"could not update expense claims schema");
}

function add_expense_claim($employee_id, $claim_date, $category, $amount, $description, $claim_type='Expense')
{
	ensure_expense_claim_schema();
	db_query("INSERT INTO ".TB_PREF."hr_expense_claims
		(employee_id, claim_date, category, amount, description, status, claim_type)
		VALUES (".db_escape($employee_id).",".db_escape($claim_date).",".db_escape($category ?: null, true).","
		.db_escape($amount).",".db_escape($description ?: null, true).",'Pending',"
		.db_escape($claim_type ?: 'Expense').")",
		"could not add expense claim");
}

function get_expense_claims($status=null, $claim_type=null)
{
	ensure_expense_claim_schema();
	$sql = "SELECT c.*, e.first_name, e.last_name
		FROM ".TB_PREF."hr_expense_claims c
		JOIN ".TB_PREF."hr_employees e ON e.id = c.employee_id";
	$where = array();
	if ($status)
		$where[] = "c.status=".db_escape($status);
	if ($claim_type)
		$where[] = "c.claim_type=".db_escape($claim_type);
	if ($where)
		$sql .= " WHERE ".implode(' AND ', $where);
	$sql .= " ORDER BY c.claim_date DESC, c.id DESC";
	return db_query($sql, "could not get expense claims");
}

function set_expense_claim_status($id, $status, $approved_by, $approved_date)
{
	db_query("UPDATE ".TB_PREF."hr_expense_claims SET status=".db_escape($status)
		.", approved_by=".db_escape($approved_by ?: null, true).", approved_date=".db_escape($approved_date ?: null, true)
		." WHERE id=".db_escape($id), "could not update expense claim status");
}
