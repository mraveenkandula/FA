<?php

function get_customer_distribution($debtor_no)
{
	$result = db_query("SELECT * FROM ".TB_PREF."customer_distribution WHERE debtor_no=".db_escape($debtor_no),
		"could not get customer distribution mapping");
	return db_fetch($result);
}

function save_customer_distribution($debtor_no, $person_type, $territory_id, $town_id, $beat_id, $sales_employee_id)
{
	$sql = "REPLACE INTO ".TB_PREF."customer_distribution
		(debtor_no, person_type, territory_id, town_id, beat_id, sales_employee_id)
		VALUES (".db_escape($debtor_no).",".db_escape($person_type).","
		.db_escape($territory_id ?: null, true).",".db_escape($town_id ?: null, true).","
		.db_escape($beat_id ?: null, true).",".db_escape($sales_employee_id ?: null, true).")";
	db_query($sql, "could not save customer distribution mapping");
}

function get_customers_by_area($territory_id=null, $beat_id=null)
{
	$sql = "SELECT d.name, cd.person_type, z.name AS territory_name, t.name AS town_name, b.name AS beat_name
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		LEFT JOIN ".TB_PREF."sales_territories z ON z.id = cd.territory_id
		LEFT JOIN ".TB_PREF."sales_towns t ON t.id = cd.town_id
		LEFT JOIN ".TB_PREF."sales_beats b ON b.id = cd.beat_id
		WHERE 1=1";
	if ($territory_id)
		$sql .= " AND cd.territory_id=".db_escape($territory_id);
	if ($beat_id)
		$sql .= " AND cd.beat_id=".db_escape($beat_id);
	$sql .= " ORDER BY z.name, t.name, d.name";
	return db_query($sql, "could not get customers by area");
}
