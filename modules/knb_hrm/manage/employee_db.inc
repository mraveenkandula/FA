<?php

function add_employee($emp_code, $first_name, $last_name, $gender, $department_id,
	$designation_id, $mobile, $email, $dob, $hire_date)
{
	$sql = "INSERT INTO ".TB_PREF."hr_employees
		(emp_code, first_name, last_name, gender, department_id, designation_id, mobile, email, dob, hire_date)
		VALUES (".db_escape($emp_code).",".db_escape($first_name).",".db_escape($last_name).","
		.db_escape($gender ?: null, true).",".db_escape($department_id ?: null, true).","
		.db_escape($designation_id ?: null, true).",".db_escape($mobile).",".db_escape($email).","
		.db_escape($dob ?: null, true).",".db_escape($hire_date ?: null, true).")";
	db_query($sql, "could not add employee");
}

function update_employee($id, $emp_code, $first_name, $last_name, $gender, $department_id,
	$designation_id, $mobile, $email, $dob, $hire_date)
{
	$sql = "UPDATE ".TB_PREF."hr_employees SET
		emp_code=".db_escape($emp_code).", first_name=".db_escape($first_name).",
		last_name=".db_escape($last_name).", gender=".db_escape($gender ?: null, true).",
		department_id=".db_escape($department_id ?: null, true).", designation_id=".db_escape($designation_id ?: null, true).",
		mobile=".db_escape($mobile).", email=".db_escape($email).",
		dob=".db_escape($dob ?: null, true).", hire_date=".db_escape($hire_date ?: null, true)."
		WHERE id=".db_escape($id);
	db_query($sql, "could not update employee");
}

function get_all_employees($all=false)
{
	$sql = "SELECT e.*, d.name AS department_name, p.name AS designation_name
		FROM ".TB_PREF."hr_employees e
		LEFT JOIN ".TB_PREF."hr_departments d ON d.id = e.department_id
		LEFT JOIN ".TB_PREF."hr_designations p ON p.id = e.designation_id";
	if (!$all)
		$sql .= " WHERE !e.inactive";
	$sql .= " ORDER BY e.first_name";
	return db_query($sql, "could not get employees");
}

function get_employee($id)
{
	$sql = "SELECT * FROM ".TB_PREF."hr_employees WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get employee");
	return db_fetch($result);
}

function delete_employee($id)
{
	$sql = "DELETE FROM ".TB_PREF."hr_employees WHERE id=".db_escape($id);
	db_query($sql, "could not delete employee");
}
