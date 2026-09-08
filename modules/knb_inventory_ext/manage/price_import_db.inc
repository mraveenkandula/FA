<?php
/*
	DB helpers for the Bulk Price Import tool. Reuses core FA's own
	inventory/includes/db/items_prices_db.inc functions (add_item_price(),
	update_item_price()) for the actual 0_prices writes rather than
	reinventing them - this file only adds the lookups needed to validate a
	CSV row and the audit-log writer.
*/

//---------------------------------------------------------------------------------------------------
// CSV parsing - no eval()/include on the uploaded content, just fgetcsv().
//---------------------------------------------------------------------------------------------------
function parse_price_import_csv($filepath)
{
	if (($fh = fopen($filepath, 'r')) === false)
		return array(null, null, _("Could not open the uploaded file."));

	$header = fgetcsv($fh);
	if ($header === false || count($header) < 1)
	{
		fclose($fh);
		return array(null, null, _("The file appears to be empty."));
	}
	$col = array();
	foreach ($header as $i => $name)
		$col[strtolower(trim($name))] = $i;

	$code_col = isset($col['item_code']) ? $col['item_code'] : (isset($col['stock_id']) ? $col['stock_id'] : null);
	$type_id_col = isset($col['sales_type_id']) ? $col['sales_type_id'] : null;
	$type_name_col = isset($col['sales_type']) ? $col['sales_type'] : null;
	$price_col = isset($col['price']) ? $col['price'] : null;

	if ($code_col === null || $price_col === null || ($type_id_col === null && $type_name_col === null))
	{
		fclose($fh);
		return array(null, null, _("The header row must contain item_code, price, and either sales_type_id or sales_type columns."));
	}
	// If both are present, prefer the numeric id (less ambiguous).
	$type_mode = $type_id_col !== null ? 'id' : 'name';

	$rows = array();
	$line_no = 1; // header was line 1
	while (($line = fgetcsv($fh)) !== false)
	{
		$line_no++;
		// skip fully blank lines
		if (count($line) == 1 && trim($line[0]) === '')
			continue;

		$rows[] = array(
			'line' => $line_no,
			'item_code' => isset($line[$code_col]) ? trim($line[$code_col]) : '',
			'sales_type_raw' => $type_mode == 'id'
				? (isset($line[$type_id_col]) ? trim($line[$type_id_col]) : '')
				: (isset($line[$type_name_col]) ? trim($line[$type_name_col]) : ''),
			'type_mode' => $type_mode,
			'price_raw' => isset($line[$price_col]) ? trim($line[$price_col]) : '',
		);
	}
	fclose($fh);
	return array($rows, $type_mode, null);
}

//---------------------------------------------------------------------------------------------------
// Validate every row against 0_stock_master / 0_sales_types / basic number
// rules before anything is written. Rejected rows are reported, never
// silently dropped. Returns array('rows'=>..., 'summary'=>...).
//---------------------------------------------------------------------------------------------------
function validate_price_import_rows($raw_rows, $curr_abrev)
{
	$results = array();
	$seen = array(); // "$stock_id|$sales_type_id" => index into $results of the row that will actually be applied

	foreach ($raw_rows as $raw)
	{
		$out = array(
			'line' => $raw['line'],
			'item_code' => $raw['item_code'],
			'sales_type_raw' => $raw['sales_type_raw'],
			'price_raw' => $raw['price_raw'],
			'status' => 'ok',
			'reason' => '',
		);

		if ($raw['item_code'] === '')
		{
			$out['status'] = 'reject';
			$out['reason'] = _("item_code is blank.");
			$results[] = $out;
			continue;
		}

		$item = knb_price_import_get_stock_item($raw['item_code']);
		if (!$item)
		{
			$out['status'] = 'reject';
			$out['reason'] = sprintf(_("Unknown item code '%s' - no such stock item."), $raw['item_code']);
			$results[] = $out;
			continue;
		}
		$out['description'] = $item['description'];

		$sales_type = null;
		if ($raw['type_mode'] == 'id')
		{
			if ($raw['sales_type_raw'] === '' || !ctype_digit($raw['sales_type_raw']))
			{
				$out['status'] = 'reject';
				$out['reason'] = sprintf(_("Invalid sales_type_id '%s' - must be a whole number."), $raw['sales_type_raw']);
				$results[] = $out;
				continue;
			}
			$sales_type = knb_price_import_get_sales_type_by_id((int)$raw['sales_type_raw']);
		}
		else
		{
			if ($raw['sales_type_raw'] === '')
			{
				$out['status'] = 'reject';
				$out['reason'] = _("sales_type is blank.");
				$results[] = $out;
				continue;
			}
			$sales_type = knb_price_import_get_sales_type_by_name($raw['sales_type_raw']);
		}
		if (!$sales_type)
		{
			$out['status'] = 'reject';
			$out['reason'] = sprintf(_("Unknown sales type '%s' - no matching row in Sales Types."), $raw['sales_type_raw']);
			$results[] = $out;
			continue;
		}
		$out['sales_type_id'] = $sales_type['id'];
		$out['sales_type_name'] = $sales_type['sales_type'];
		if ($sales_type['inactive'])
			$out['reason'] = sprintf(_("Note: sales type '%s' is currently marked inactive."), $sales_type['sales_type']);

		// Accept plain decimals; reject anything non-numeric (incl. blank),
		// negative, or zero (zero-pricing an item is very unlikely to be
		// intentional in a bulk import and is far more likely a data error).
		$price_str = str_replace(array(',', ' '), '', $raw['price_raw']);
		if ($price_str === '' || !is_numeric($price_str) || (float)$price_str <= 0)
		{
			$out['status'] = 'reject';
			$out['reason'] = sprintf(_("Invalid price '%s' - must be a positive number."), $raw['price_raw']);
			$results[] = $out;
			continue;
		}
		$out['price'] = (float)$price_str;

		$existing = knb_price_import_existing_price($item['stock_id'], $sales_type['id'], $curr_abrev);
		if ($existing)
		{
			$out['action'] = 'update';
			$out['existing_price_id'] = $existing['id'];
			$out['old_price'] = (float)$existing['price'];
		}
		else
		{
			$out['action'] = 'insert';
			$out['existing_price_id'] = null;
			$out['old_price'] = null;
		}
		$out['stock_id'] = $item['stock_id'];

		// De-duplicate within the file itself: if the same stock_id+sales_type
		// appears twice, the later row wins (matches an upsert reading of
		// the file) and the earlier one is superseded rather than being
		// applied and then immediately overwritten - applying both as
		// separate inserts would also violate 0_prices' unique key.
		$key = $item['stock_id'].'|'.$sales_type['id'];
		if (isset($seen[$key]))
		{
			$prev = $seen[$key];
			$results[$prev]['status'] = 'superseded';
			$results[$prev]['reason'] = sprintf(_("Superseded by row on line %d for the same item/sales type."), $raw['line']);
		}
		$seen[$key] = count($results);

		$results[] = $out;
	}

	$summary = array('insert' => 0, 'update' => 0, 'reject' => 0, 'superseded' => 0, 'total' => count($results));
	foreach ($results as $r)
	{
		if ($r['status'] == 'ok')
			$summary[$r['action']]++;
		else
			$summary[$r['status']]++;
	}

	return array('rows' => $results, 'summary' => $summary);
}

// Lightweight existence/description check against 0_stock_master. Deliberately
// not core's get_item() - that joins item_tax_types, which we don't need here
// and which would make a missing/misconfigured tax type mask a bad item_code.
function knb_price_import_get_stock_item($stock_id)
{
	$sql = "SELECT stock_id, description FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);
	$result = db_query($sql, "could not check item code");
	return db_fetch($result);
}

// Resolve a sales type by numeric id.
function knb_price_import_get_sales_type_by_id($id)
{
	$sql = "SELECT id, sales_type, inactive FROM ".TB_PREF."sales_types WHERE id=".db_escape($id);
	$result = db_query($sql, "could not check sales type");
	return db_fetch($result);
}

// Resolve a sales type by name, case-insensitively (for the item_code,
// sales_type,price CSV variant).
function knb_price_import_get_sales_type_by_name($name)
{
	$sql = "SELECT id, sales_type, inactive FROM ".TB_PREF."sales_types WHERE LOWER(sales_type)=LOWER(".db_escape($name).")";
	$result = db_query($sql, "could not check sales type");
	return db_fetch($result);
}

// Which currency should the import default to? Deliberately NOT just
// get_company_currency(): on at least this install, the company's
// curr_default (base currency) preference doesn't match the currency
// actually used by existing 0_prices rows (found while testing this tool -
// see docs/techcloud-parity-gap-analysis.md). Defaulting to whatever
// currency the existing price rows are actually in avoids silently creating
// a second, currency-mismatched price row for every item that already has
// one (which get_company_currency() would do here, since 0_prices' unique
// key includes curr_abrev).
function knb_price_import_default_currency()
{
	$sql = "SELECT curr_abrev, COUNT(*) as c FROM ".TB_PREF."prices GROUP BY curr_abrev ORDER BY c DESC LIMIT 1";
	$result = db_query($sql, "could not check existing price currencies");
	$row = db_fetch($result);
	return $row ? $row['curr_abrev'] : get_company_currency();
}

function knb_price_import_currency_exists($curr_abrev)
{
	$sql = "SELECT curr_abrev FROM ".TB_PREF."currencies WHERE curr_abrev=".db_escape($curr_abrev);
	$result = db_query($sql, "could not check currency");
	return db_fetch($result) ? true : false;
}

// Does a 0_prices row already exist for this stock/sales-type/currency?
// (thin wrapper so callers here don't need to know it lives in
// items_prices_db.inc - that file is included by the calling page).
function knb_price_import_existing_price($stock_id, $sales_type_id, $curr_abrev)
{
	return get_stock_price_type_currency($stock_id, $sales_type_id, $curr_abrev);
}

/*
	Apply a validated batch of rows to 0_prices as a single transaction, and
	write one 0_knb_price_import_log row per price actually written.

	$rows is an array of arrays, each with keys:
		stock_id, sales_type_id, price, action ('insert'|'update'),
		existing_price_id (for 'update'), old_price (for 'update', else null)

	Returns the batch id used in the log.
*/
function apply_price_import($rows, $curr_abrev, $source_file)
{
	$batch = date('YmdHis');
	$user_id = @$_SESSION['wa_current_user']->user;
	$user_name = @$_SESSION['wa_current_user']->username;

	begin_transaction();

	foreach ($rows as $row)
	{
		if ($row['action'] == 'update')
		{
			update_item_price($row['existing_price_id'], $row['sales_type_id'], $curr_abrev, $row['price']);
		}
		else
		{
			add_item_price($row['stock_id'], $row['sales_type_id'], $curr_abrev, $row['price']);
		}

		$sql = "INSERT INTO ".TB_PREF."knb_price_import_log
			(import_batch, source_file, stock_id, sales_type_id, curr_abrev, old_price, new_price, action, imported_by, imported_by_name)
			VALUES (".db_escape($batch).",".db_escape($source_file).",".db_escape($row['stock_id']).","
			.db_escape($row['sales_type_id']).",".db_escape($curr_abrev).","
			.db_escape($row['old_price'], true).",".db_escape($row['price']).","
			.db_escape($row['action']).",".db_escape($user_id, true).",".db_escape($user_name, true).")";
		db_query($sql, "could not write price import audit log entry");
	}

	commit_transaction();

	return $batch;
}

// Most recent batches, for the on-screen audit trail summary at the bottom
// of the import page.
function get_recent_price_import_batches($limit=10)
{
	$sql = "SELECT import_batch, source_file, imported_by_name, imported_at,
			COUNT(*) as row_count,
			SUM(action='insert') as inserted,
			SUM(action='update') as updated
		FROM ".TB_PREF."knb_price_import_log
		GROUP BY import_batch, source_file, imported_by_name, imported_at
		ORDER BY imported_at DESC
		LIMIT ".(int)$limit;
	return db_query($sql, "could not get price import history");
}
