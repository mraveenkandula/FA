<?php

/*
	Pure logic, no DB - kept separate from schemes_eligibility_inquiry.php so
	it can be unit tested without a database. Picks the highest slab target
	a customer's total_qty qualifies for; deliberately does not assume slabs
	are passed in ascending target order (they're user-entered in slab_no
	order, which was the actual bug this was extracted to fix - fixed once
	already for the inline version, keeping it here as a named, testable unit
	stops it regressing silently).
*/
function find_best_qualifying_slab($slabs, $total_qty)
{
	$best_target = -1;
	$best_slab = null;
	foreach ($slabs as $slab)
	{
		if ($slab['slab_target_qty'] > 0 && $total_qty >= $slab['slab_target_qty']
			&& $slab['slab_target_qty'] > $best_target)
		{
			$best_target = $slab['slab_target_qty'];
			$best_slab = $slab;
		}
	}
	return $best_slab;
}

function add_scheme($brand_id, $person_type, $state, $territory_id, $start_date, $end_date, $slabs)
{
	begin_transaction();
	db_query("INSERT INTO ".TB_PREF."knb_schemes
		(brand_id, person_type, state, territory_id, start_date, end_date)
		VALUES (".db_escape($brand_id ?: null, true).",".db_escape($person_type ?: null, true).","
		.db_escape($state ?: null, true).",".db_escape($territory_id ?: null, true).","
		.db_escape(date2sql($start_date)).",".db_escape(date2sql($end_date)).")",
		"could not add scheme");
	$scheme_id = db_insert_id();
	save_scheme_slabs($scheme_id, $slabs);
	commit_transaction();
	return $scheme_id;
}

function update_scheme($id, $brand_id, $person_type, $state, $territory_id, $start_date, $end_date, $slabs)
{
	begin_transaction();
	db_query("UPDATE ".TB_PREF."knb_schemes SET
		brand_id=".db_escape($brand_id ?: null, true).", person_type=".db_escape($person_type ?: null, true).","
		." state=".db_escape($state ?: null, true).", territory_id=".db_escape($territory_id ?: null, true).","
		." start_date=".db_escape(date2sql($start_date)).", end_date=".db_escape(date2sql($end_date))."
		WHERE id=".db_escape($id),
		"could not update scheme");
	db_query("DELETE FROM ".TB_PREF."knb_scheme_slabs WHERE scheme_id=".db_escape($id), "could not clear scheme slabs");
	save_scheme_slabs($id, $slabs);
	commit_transaction();
}

function save_scheme_slabs($scheme_id, $slabs)
{
	foreach ($slabs as $i => $slab)
	{
		if ($slab['slab_target_qty'] === '' && $slab['scheme_text'] === '' && $slab['slab_label'] === '')
			continue;
		db_query("INSERT INTO ".TB_PREF."knb_scheme_slabs (scheme_id, slab_no, slab_label, slab_target_qty, scheme_text)
			VALUES (".db_escape($scheme_id).",".db_escape($i + 1).",".db_escape($slab['slab_label'] ?: null, true).","
			.db_escape($slab['slab_target_qty'] !== '' ? $slab['slab_target_qty'] : 0).","
			.db_escape($slab['scheme_text'] ?: null, true).")",
			"could not add scheme slab");
	}
}

function delete_scheme($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_scheme_slabs WHERE scheme_id=".db_escape($id), "could not delete scheme slabs");
	db_query("DELETE FROM ".TB_PREF."knb_schemes WHERE id=".db_escape($id), "could not delete scheme");
}

function get_scheme($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_schemes WHERE id=".db_escape($id), "could not get scheme");
	return db_fetch($result);
}

function get_scheme_slabs($scheme_id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_scheme_slabs WHERE scheme_id=".db_escape($scheme_id)." ORDER BY slab_no",
		"could not get scheme slabs");
	$slabs = array();
	while ($row = db_fetch($result)) $slabs[] = $row;
	return $slabs;
}

function get_all_schemes($show_inactive=false)
{
	$sql = "SELECT s.*, b.name AS brand_name, t.name AS territory_name
		FROM ".TB_PREF."knb_schemes s
		LEFT JOIN ".TB_PREF."item_brands b ON b.id = s.brand_id
		LEFT JOIN ".TB_PREF."sales_territories t ON t.id = s.territory_id
		WHERE 1=1";
	if (!$show_inactive)
		$sql .= " AND !s.inactive";
	$sql .= " ORDER BY s.start_date DESC";
	return db_query($sql, "could not get schemes");
}

function get_distinct_states()
{
	return db_query("SELECT DISTINCT state FROM ".TB_PREF."sales_towns WHERE state IS NOT NULL AND state <> '' ORDER BY state",
		"could not get distinct states");
}

/*
	Total quantity of the scheme's brand invoiced to each customer of the
	scheme's person type (and matching state/territory, if set) within the
	scheme's active date range - real ST_SALESINVOICE lines only, so a
	scheme's slabs can be checked against what customers actually bought.
*/
function get_scheme_achievement($scheme)
{
	$sql = "SELECT cd.debtor_no, d.name AS customer_name, SUM(dtd.quantity) AS total_qty
		FROM ".TB_PREF."debtor_trans_details dtd
		JOIN ".TB_PREF."debtor_trans dt ON dt.trans_no = dtd.debtor_trans_no AND dt.type = dtd.debtor_trans_type
		JOIN ".TB_PREF."customer_distribution cd ON cd.debtor_no = dt.debtor_no
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = dt.debtor_no
		LEFT JOIN ".TB_PREF."sales_towns tn ON tn.id = cd.town_id
		WHERE dt.type = ".ST_SALESINVOICE."
		AND dt.tran_date >= ".db_escape($scheme['start_date'])."
		AND dt.tran_date <= ".db_escape($scheme['end_date']);

	if ($scheme['brand_id'])
		$sql .= " AND dtd.stock_id IN (SELECT stock_id FROM ".TB_PREF."item_classification WHERE brand_id=".db_escape($scheme['brand_id']).")";
	if ($scheme['person_type'])
		$sql .= " AND cd.person_type=".db_escape($scheme['person_type']);
	if ($scheme['territory_id'])
		$sql .= " AND cd.territory_id=".db_escape($scheme['territory_id']);
	if ($scheme['state'])
		$sql .= " AND tn.state=".db_escape($scheme['state']);

	$sql .= " GROUP BY cd.debtor_no, d.name ORDER BY total_qty DESC";
	return db_query($sql, "could not get scheme achievement");
}
