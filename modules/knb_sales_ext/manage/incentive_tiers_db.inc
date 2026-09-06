<?php

function add_incentive_tier($min_pct, $max_pct, $incentive_pct)
{
	db_query("INSERT INTO ".TB_PREF."knb_incentive_tiers (min_achievement_pct, max_achievement_pct, incentive_pct)
		VALUES (".db_escape($min_pct).",".db_escape($max_pct).",".db_escape($incentive_pct).")",
		"could not add incentive tier");
}

function update_incentive_tier($id, $min_pct, $max_pct, $incentive_pct)
{
	db_query("UPDATE ".TB_PREF."knb_incentive_tiers SET min_achievement_pct=".db_escape($min_pct).",
		max_achievement_pct=".db_escape($max_pct).", incentive_pct=".db_escape($incentive_pct)."
		WHERE id=".db_escape($id),
		"could not update incentive tier");
}

function delete_incentive_tier($id)
{
	db_query("DELETE FROM ".TB_PREF."knb_incentive_tiers WHERE id=".db_escape($id),
		"could not delete incentive tier");
}

function get_incentive_tier($id)
{
	$result = db_query("SELECT * FROM ".TB_PREF."knb_incentive_tiers WHERE id=".db_escape($id),
		"could not get incentive tier");
	return db_fetch($result);
}

function get_all_incentive_tiers($show_inactive=false)
{
	$sql = "SELECT * FROM ".TB_PREF."knb_incentive_tiers";
	if (!$show_inactive)
		$sql .= " WHERE !inactive";
	$sql .= " ORDER BY min_achievement_pct";
	return db_query($sql, "could not get incentive tiers");
}
