<?php
/*
	Lead follow-up pipeline for the mobile app's "My Leads" screen.

	outlet_create.php (outlet_db.inc's create_outlet()) already turns a
	captured lead straight into a full working customer (debtors_master +
	branch + 0_customer_distribution row) - there was no lifecycle beyond
	that one-shot creation: no status, no follow-up log. This adds exactly
	that on top, without touching create_outlet() itself.

	Two tables, on purpose kept separate (same distinction the task asked
	for): 0_knb_lead_followups is an append-only history of every touch a
	rep logs against a lead (one row per follow-up call/visit); 0_knb_lead_status
	is the lead's current state - a single row per debtor_no, upserted every
	time a new follow-up is logged, so "what's this lead's status right now"
	is a cheap indexed lookup rather than "find the latest row in the log".

	"My leads" = customers whose 0_customer_distribution.created_by_employee_id
	matches the authenticated employee (that column already exists - added by
	modules/knb_api/sql/knb_api.sql, populated by outlet_create.php's
	create_outlet() - see outlet_db.inc). Not every customer in the system,
	just the ones this rep personally captured as a lead.

	Self-healing CREATE TABLE IF NOT EXISTS, called defensively from every
	function that touches these tables - same pattern as
	modules/knb_inventory_ext/manage/price_import_db.inc's
	apply_price_import(), for the same reason: FA's package installer never
	runs for hand-placed modules like this one, so there is no other place
	these tables would ever get created on a given install.
*/

function knb_lead_valid_statuses()
{
	return array('New', 'Contacted', 'Converted', 'Lost');
}

function ensure_lead_tables()
{
	db_query("CREATE TABLE IF NOT EXISTS ".TB_PREF."knb_lead_followups (
		id int(11) NOT NULL AUTO_INCREMENT,
		debtor_no int(11) NOT NULL,
		employee_id int(11) DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT 'New',
		follow_up_date date DEFAULT NULL,
		notes varchar(500) DEFAULT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY debtor_no (debtor_no),
		KEY employee_id (employee_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci",
		"could not create knb_lead_followups table");

	db_query("CREATE TABLE IF NOT EXISTS ".TB_PREF."knb_lead_status (
		debtor_no int(11) NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'New',
		last_follow_up_date date DEFAULT NULL,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (debtor_no)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci",
		"could not create knb_lead_status table");
}

/*
	Logs one follow-up touch and moves the lead's current status forward in
	the same transaction. $follow_up_date is a plain ISO YYYY-MM-DD string
	(or null) written straight into a native DATE column - no date2sql()/
	sql2date() round-trip, same rule as every other native DATE column in
	this API (see stock_verification_get.php's doc comment).
*/
function add_lead_followup($debtor_no, $employee_id, $status, $follow_up_date, $notes)
{
	ensure_lead_tables();

	begin_transaction();

	db_query("INSERT INTO ".TB_PREF."knb_lead_followups
		(debtor_no, employee_id, status, follow_up_date, notes, created_at)
		VALUES (".db_escape($debtor_no).",".db_escape($employee_id ?: null, true).","
		.db_escape($status).",".db_escape($follow_up_date ?: null, true).","
		.db_escape($notes ?: null, true).", NOW())",
		"could not add lead follow-up");
	$id = db_insert_id();

	db_query("INSERT INTO ".TB_PREF."knb_lead_status (debtor_no, status, last_follow_up_date, updated_at)
		VALUES (".db_escape($debtor_no).",".db_escape($status).",".db_escape($follow_up_date ?: null, true).", NOW())
		ON DUPLICATE KEY UPDATE status=".db_escape($status).",
			last_follow_up_date=".db_escape($follow_up_date ?: null, true).", updated_at=NOW()",
		"could not update lead status");

	commit_transaction();
	return $id;
}

/*
	One row per customer this employee created as a lead (outlet_create.php),
	with its current status (defaults to 'New' when no follow-up has been
	logged yet - there's no 0_knb_lead_status row until add_lead_followup()
	is called for the first time).
*/
function get_leads_for_employee($employee_id)
{
	ensure_lead_tables();

	$sql = "SELECT d.debtor_no, d.name, cd.phone, cd.secondary_phone, cd.person_type,
			cd.territory_id, cd.town_id, cd.beat_id, cd.gps_lat, cd.gps_lng,
			COALESCE(ls.status, 'New') AS status, ls.last_follow_up_date, ls.updated_at
		FROM ".TB_PREF."customer_distribution cd
		JOIN ".TB_PREF."debtors_master d ON d.debtor_no = cd.debtor_no
		LEFT JOIN ".TB_PREF."knb_lead_status ls ON ls.debtor_no = cd.debtor_no
		WHERE cd.created_by_employee_id=".db_escape($employee_id)."
		ORDER BY d.debtor_no DESC";
	return db_query($sql, "could not get leads");
}

/*
	Full follow-up history for one lead, newest first - the "history of
	touches" behind that lead's current status.
*/
function get_lead_followups($debtor_no)
{
	ensure_lead_tables();

	$sql = "SELECT lf.id, lf.debtor_no, lf.employee_id, lf.status, lf.follow_up_date,
			lf.notes, lf.created_at, e.first_name, e.last_name
		FROM ".TB_PREF."knb_lead_followups lf
		LEFT JOIN ".TB_PREF."hr_employees e ON e.id = lf.employee_id
		WHERE lf.debtor_no=".db_escape($debtor_no)."
		ORDER BY lf.created_at DESC, lf.id DESC";
	return db_query($sql, "could not get lead follow-ups");
}
