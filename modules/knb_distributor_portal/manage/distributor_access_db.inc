<?php
/*
	Portal access is scoped to person_type 1 (Super Stockist) and 2
	(Distributor) - the two tiers that actually hold stock at their own
	location (0_locations.debtor_no) - not person_type 3 (Retailer), which
	matches the contract's "Distributor Login" wording rather than a
	general customer login.
*/
function get_portal_eligible_customers($search = '')
{
	$sql = "SELECT debtor_no, name, debtor_ref, person_type, portal_username, portal_active
		FROM ".TB_PREF."debtors_master
		WHERE person_type IN (1, 2) AND !inactive";
	if ($search !== '')
		$sql .= " AND (name LIKE ".db_escape('%'.$search.'%')." OR debtor_ref LIKE ".db_escape('%'.$search.'%').")";
	$sql .= " ORDER BY name";
	return db_query($sql, "could not retrieve distributors");
}

function get_customer_for_portal($debtor_no)
{
	$result = db_query("SELECT debtor_no, name, debtor_ref, portal_username, portal_active
		FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($debtor_no),
		"could not retrieve customer");
	return db_fetch($result);
}

function set_portal_credentials($debtor_no, $username, $password)
{
	$sql = "UPDATE ".TB_PREF."debtors_master SET portal_username=".db_escape($username).",
		portal_password_hash=".db_escape(password_hash($password, PASSWORD_DEFAULT)).",
		portal_active=1 WHERE debtor_no=".db_escape($debtor_no);
	db_query($sql, "could not set portal credentials");
}

function set_portal_active($debtor_no, $active)
{
	db_query("UPDATE ".TB_PREF."debtors_master SET portal_active=".db_escape((int)$active)."
		WHERE debtor_no=".db_escape($debtor_no), "could not update portal access");
}
