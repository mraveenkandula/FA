<?php

function add_employee_loan($employee_id, $loan_amount, $loan_type, $payment_type, $installment_amount,
	$installment_month, $loan_start_date, $loan_end_date, $status, $paid_amount, $pending_amount, $remarks)
{
	db_query("INSERT INTO ".TB_PREF."knb_employee_loans
		(employee_id, loan_amount, loan_type, payment_type, installment_amount, installment_month,
		 loan_start_date, loan_end_date, status, paid_amount, pending_amount, remarks)
		VALUES (".db_escape($employee_id).",".db_escape($loan_amount).",".db_escape($loan_type).","
		.db_escape($payment_type).",".db_escape($installment_amount).",".db_escape($installment_month).","
		.db_escape($loan_start_date ? date2sql($loan_start_date) : null, true).","
		.db_escape($loan_end_date ? date2sql($loan_end_date) : null, true).","
		.db_escape($status).",".db_escape($paid_amount).",".db_escape($pending_amount).","
		.db_escape($remarks).")",
		"could not add employee loan");
}

function update_employee_loan($id, $employee_id, $loan_amount, $loan_type, $payment_type, $installment_amount,
	$installment_month, $loan_start_date, $loan_end_date, $status, $paid_amount, $pending_amount, $remarks)
{
	db_query("UPDATE ".TB_PREF."knb_employee_loans SET
		employee_id=".db_escape($employee_id).",
		loan_amount=".db_escape($loan_amount).",
		loan_type=".db_escape($loan_type).",
		payment_type=".db_escape($payment_type).",
		installment_amount=".db_escape($installment_amount).",
		installment_month=".db_escape($installment_month).",
		loan_start_date=".db_escape($loan_start_date ? date2sql($loan_start_date) : null, true).",
		loan_end_date=".db_escape($loan_end_date ? date2sql($loan_end_date) : null, true).",
		status=".db_escape($status).",
		paid_amount=".db_escape($paid_amount).",
		pending_amount=".db_escape($pending_amount).",
		remarks=".db_escape($remarks)."
		WHERE id=".db_escape($id),
		"could not update employee loan");
}

function delete_employee_loan($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_employee_loans WHERE id=".db_escape($id),
		"could not delete employee loan");
}

function get_employee_loan($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_employee_loans WHERE id=".db_escape($id));
	return db_fetch($result);
}
