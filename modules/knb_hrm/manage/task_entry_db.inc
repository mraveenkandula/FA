<?php

function add_task($assigned_to, $assigned_by, $title, $description, $due_date, $priority)
{
	db_query("INSERT INTO ".TB_PREF."knb_employee_tasks
		(assigned_to, assigned_by, title, description, due_date, priority, status, created_date)
		VALUES (".db_escape($assigned_to).",".db_escape($assigned_by ?: null, true).","
		.db_escape($title).",".db_escape($description ?: null, true).","
		.db_escape($due_date ? date2sql($due_date) : null, true).",".db_escape($priority).",'Pending',"
		.db_escape(date2sql(Today())).")",
		"could not add task");
}

function set_task_status($id, $status, $remarks=null)
{
	$completed_date = ($status == 'Completed') ? db_escape(date2sql(Today())) : 'NULL';
	db_query("UPDATE ".TB_PREF."knb_employee_tasks SET status=".db_escape($status).","
		." remarks=".db_escape($remarks ?: null, true).", completed_date=$completed_date
		WHERE id=".db_escape($id),
		"could not update task status");
}

function get_task($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_employee_tasks WHERE id=".db_escape($id),
		"could not get task");
	return db_fetch($result);
}

function get_tasks($assigned_to=null, $status=null)
{
	$sql = "SELECT t.*, e.first_name, e.last_name, a.first_name AS assigned_by_first_name, a.last_name AS assigned_by_last_name
		FROM ".TB_PREF."knb_employee_tasks t
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = t.assigned_to
		LEFT JOIN ".TB_PREF."hr_employees a ON a.id = t.assigned_by
		WHERE 1=1";
	if ($assigned_to)
		$sql .= " AND t.assigned_to=".db_escape($assigned_to);
	if ($status)
		$sql .= " AND t.status=".db_escape($status);
	$sql .= " ORDER BY t.due_date IS NULL, t.due_date ASC, t.id DESC";
	return db_query($sql, "could not get tasks");
}
