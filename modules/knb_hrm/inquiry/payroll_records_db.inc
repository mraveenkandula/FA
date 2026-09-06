<?php
/*
	Read-only queries over the real payroll/PF/ESI/PT/loans data merged in
	from the TechCloud export. Payroll *processing* (running a payroll,
	approving it, computing PF/ESI/PT deductions) is out of scope until the
	accountant signs off on the compliance rules - this is history, not a
	workflow.
*/

function get_payroll_runs($employee_id = null)
{
	$sql = "SELECT p.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_payroll p
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = p.employee_id";
	if ($employee_id)
		$sql .= " WHERE p.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY p.period_from DESC";
	return db_query($sql);
}

function get_salary_structures()
{
	return db_query(
		"SELECT s.*, d.name AS designation_name
		 FROM ".TB_PREF."knb_salary_structure s
		 LEFT JOIN ".TB_PREF."hr_designations d ON d.id = s.designation_id
		 ORDER BY d.name, s.effective_date DESC");
}

function get_professional_tax_slabs()
{
	return db_query("SELECT * FROM ".TB_PREF."knb_professional_tax_slabs ORDER BY basic_amount");
}

function get_employee_incentives($employee_id = null)
{
	$sql = "SELECT i.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_employee_incentives i
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = i.employee_id";
	if ($employee_id)
		$sql .= " WHERE i.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY i.incentive_amount DESC";
	return db_query($sql);
}

function get_employee_loans($employee_id = null)
{
	$sql = "SELECT l.*, e.first_name, e.last_name
		FROM ".TB_PREF."knb_employee_loans l
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = l.employee_id";
	if ($employee_id)
		$sql .= " WHERE l.employee_id = ".db_escape($employee_id);
	$sql .= " ORDER BY l.loan_start_date DESC";
	return db_query($sql);
}
