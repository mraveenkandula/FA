<?php

function add_professional_tax_slab($slab_type, $basic_amount)
{
	db_query("INSERT INTO ".TB_PREF."knb_professional_tax_slabs (slab_type, basic_amount)
		VALUES (".db_escape($slab_type).",".db_escape($basic_amount).")",
		"could not add professional tax slab");
}

function update_professional_tax_slab($id, $slab_type, $basic_amount)
{
	db_query("UPDATE ".TB_PREF."knb_professional_tax_slabs SET
		slab_type=".db_escape($slab_type).",
		basic_amount=".db_escape($basic_amount)."
		WHERE id=".db_escape($id),
		"could not update professional tax slab");
}

function delete_professional_tax_slab($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_professional_tax_slabs WHERE id=".db_escape($id),
		"could not delete professional tax slab");
}

function get_professional_tax_slab($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_professional_tax_slabs WHERE id=".db_escape($id));
	return db_fetch($result);
}

function get_all_professional_tax_slabs($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_professional_tax_slabs";
	if (!$all)
		$sql .= " WHERE inactive=0";
	$sql .= " ORDER BY basic_amount";
	return db_query($sql);
}
