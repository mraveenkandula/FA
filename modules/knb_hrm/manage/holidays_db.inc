<?php

function add_holiday($name, $holiday_date)
{
	$sql = "INSERT INTO ".TB_PREF."knb_holidays (name, holiday_date) VALUES ("
		.db_escape($name).",".db_escape(date2sql($holiday_date)).")";
	db_query($sql, "could not add holiday");
}

function update_holiday($id, $name, $holiday_date)
{
	$sql = "UPDATE ".TB_PREF."knb_holidays SET name=".db_escape($name).", holiday_date=".db_escape(date2sql($holiday_date))."
		WHERE id=".db_escape($id);
	db_query($sql, "could not update holiday");
}

function get_all_holidays($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_holidays";
	if (!$all)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY holiday_date";
	return db_query($sql, "could not get holidays");
}

function get_holiday($id)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_holidays WHERE id=".db_escape($id);
	$result = db_query($sql, "could not get holiday");
	return db_fetch($result);
}

function delete_holiday($id)
{
	$sql = "DELETE FROM ".TB_PREF."knb_holidays WHERE id=".db_escape($id);
	db_query($sql, "could not delete holiday");
}
