<?php

function get_all_letter_templates($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_letter_templates";
	if (!$all)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not get letter templates");
}

function get_letter_template($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_letter_templates WHERE id=".db_escape($id),
		"could not get letter template");
	return db_fetch($result);
}

function add_letter_template($name, $subject, $body)
{
	db_query("INSERT INTO ".TB_PREF."knb_letter_templates (name, subject, body, inactive)
		VALUES (".db_escape($name).",".db_escape($subject ?: null, true).",".db_escape($body).", 0)",
		"could not add letter template");
}

function update_letter_template($id, $name, $subject, $body)
{
	db_query("UPDATE ".TB_PREF."knb_letter_templates
		SET name=".db_escape($name).", subject=".db_escape($subject ?: null, true).", body=".db_escape($body)."
		WHERE id=".db_escape($id), "could not update letter template");
}

function delete_letter_template($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_letter_templates WHERE id=".db_escape($id),
		"could not delete letter template");
}

/*
	Joined separately from get_employee() (employee_db.inc) rather than
	changing that function's return shape for every other caller - this
	is the one place that needs department/designation names alongside
	the employee row.
*/
function get_employee_for_letter($id)
{
	$sql = "SELECT e.*, d.name AS department_name, p.name AS designation_name
		FROM ".TB_PREF."hr_employees e
		LEFT JOIN ".TB_PREF."hr_departments d ON d.id = e.department_id
		LEFT JOIN ".TB_PREF."hr_designations p ON p.id = e.designation_id
		WHERE e.id=".db_escape($id);
	$result = db_query($sql, "could not get employee");
	return db_fetch($result);
}

/*
	Fixed, documented placeholder set (shown on the template editor) -
	plain string substitution, no template engine dependency needed for
	a handful of merge fields.
*/
function letter_placeholder_map($employee)
{
	return array(
		'{{first_name}}' => $employee['first_name'],
		'{{last_name}}' => $employee['last_name'],
		'{{full_name}}' => trim($employee['first_name'].' '.$employee['last_name']),
		'{{emp_code}}' => $employee['emp_code'],
		'{{department}}' => $employee['department_name'],
		'{{designation}}' => $employee['designation_name'],
		'{{hire_date}}' => $employee['hire_date'] ? sql2date($employee['hire_date']) : '',
		'{{today}}' => sql2date(date2sql(Today())),
		'{{company_name}}' => 'KNB Group - Godavari Ghee',
	);
}

function merge_letter_text($text, $employee)
{
	return strtr($text, letter_placeholder_map($employee));
}
