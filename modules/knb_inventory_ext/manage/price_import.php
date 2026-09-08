<?php
/*
	Bulk Item Price Import.

	Fixes the class of problem behind the TechCloud wholesale data import
	gap (most items ended up with zero rows in 0_prices for most sales
	types/price lists) by letting an admin upload a CSV of correct prices
	instead of re-entering them one by one via inventory/prices.php.

	Expected CSV format: a header row, then one row per price.
	  item_code,sales_type_id,price
	or
	  item_code,sales_type,price
	- item_code must match an existing 0_stock_master.stock_id exactly.
	- sales_type_id is the numeric 0_sales_types.id; sales_type is the
	  sales type's name (e.g. "Retail"), matched case-insensitively. Only
	  one of the two columns should be present - whichever the header row
	  names is what gets used.
	- price must be a positive number (plain decimal, e.g. 199.50).
	There is no currency column in the CSV (avoiding a 4th column - a price
	list mixing currencies still has to be entered by hand via
	inventory/prices.php); instead the page offers a currency dropdown,
	defaulting to whichever currency the existing 0_prices rows are
	actually in rather than the company's configured base currency - see
	knb_price_import_default_currency() in price_import_db.inc for why
	those two can disagree on this install.

	Nothing is written to 0_prices until the row-by-row preview below has
	been shown and the user explicitly clicks "Confirm Import" - selecting
	a file only parses and validates it. The parsed, validated rows are
	held in $_SESSION between the preview and confirm requests (not on
	disk) so the confirm step doesn't require re-uploading the file.
*/
$page_security = 'SA_KNB_PRICE_IMPORT';
$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Bulk Price Import"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/inventory/includes/db/items_prices_db.inc");
include_once(__DIR__ . "/price_import_db.inc");

define('KNB_PRICE_IMPORT_MAX_BYTES', 2 * 1024 * 1024); // 2MB - plenty for a price list CSV
define('KNB_PRICE_IMPORT_SESSION_KEY', 'knb_price_import_pending');

// parse_price_import_csv() and validate_price_import_rows() - the actual CSV
// parsing (fgetcsv() only, no eval()/include on uploaded content) and
// row-by-row validation against 0_stock_master/0_sales_types - live in
// price_import_db.inc as plain functions with no page/session dependency,
// so they can be exercised directly (incl. from a CLI test harness) without
// a browser session. See that file for both.

function price_import_status_label($row)
{
	switch ($row['status'])
	{
		case 'reject': return "<span style='color:#b30000;font-weight:bold'>"._("REJECTED").":</span> ".htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8');
		case 'superseded': return "<span style='color:#8a6d00'>"._("SKIPPED (superseded)").":</span> ".htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8');
		default:
			$label = $row['action'] == 'insert'
				? "<span style='color:#0a7a00;font-weight:bold'>"._("INSERT")."</span>"
				: "<span style='color:#0a5ea8;font-weight:bold'>"._("UPDATE")."</span>";
			if ($row['reason'] !== '')
				$label .= " - ".htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8');
			return $label;
	}
}

//---------------------------------------------------------------------------------------------------

$default_curr_abrev = knb_price_import_default_currency();
$input_error = 0;

// Step 1: file uploaded -> validate + parse -> stash in session -> show preview.
if (isset($_POST['DoPreview']) && check_csrf_token())
{
	$curr_abrev = trim(@$_POST['curr_abrev']);
	if ($curr_abrev === '' || !knb_price_import_currency_exists($curr_abrev))
	{
		display_error(_("Please choose a valid currency for the imported prices."));
		$input_error = 1;
	}
	elseif (!isset($_FILES['price_file']) || $_FILES['price_file']['error'] == UPLOAD_ERR_NO_FILE)
	{
		display_error(_("Please choose a CSV file to upload."));
		$input_error = 1;
	}
	elseif ($_FILES['price_file']['error'] != UPLOAD_ERR_OK)
	{
		display_error(_("The file upload failed (it may be larger than the server allows)."));
		$input_error = 1;
	}
	else
	{
		$orig_name = basename($_FILES['price_file']['name']);
		$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
		if ($ext != 'csv')
		{
			display_error(_("Only .csv files are accepted."));
			$input_error = 1;
		}
		elseif ($_FILES['price_file']['size'] > KNB_PRICE_IMPORT_MAX_BYTES)
		{
			display_error(sprintf(_("File is too large - maximum size is %dMB."), KNB_PRICE_IMPORT_MAX_BYTES / (1024 * 1024)));
			$input_error = 1;
		}
		elseif ($_FILES['price_file']['size'] == 0)
		{
			display_error(_("The uploaded file is empty."));
			$input_error = 1;
		}
		else
		{
			list($raw_rows, $type_mode, $err) = parse_price_import_csv($_FILES['price_file']['tmp_name']);
			if ($err)
			{
				display_error($err);
				$input_error = 1;
			}
			elseif (count($raw_rows) == 0)
			{
				display_error(_("No data rows found after the header."));
				$input_error = 1;
			}
			else
			{
				$validated = validate_price_import_rows($raw_rows, $curr_abrev);
				$_SESSION[KNB_PRICE_IMPORT_SESSION_KEY] = array(
					'rows' => $validated['rows'],
					'summary' => $validated['summary'],
					'curr_abrev' => $curr_abrev,
					'source_file' => $orig_name,
				);
			}
		}
	}
}

// Step 2: explicit confirm -> apply as one transaction.
if (isset($_POST['DoImport']) && check_csrf_token())
{
	$pending = @$_SESSION[KNB_PRICE_IMPORT_SESSION_KEY];
	if (!$pending)
	{
		display_error(_("Nothing to import - your preview may have expired. Please upload the file again."));
	}
	else
	{
		$to_apply = array();
		foreach ($pending['rows'] as $row)
			if ($row['status'] == 'ok')
				$to_apply[] = $row;

		if (count($to_apply) == 0)
		{
			display_error(_("There were no valid rows to import."));
		}
		else
		{
			$batch = apply_price_import($to_apply, $pending['curr_abrev'], $pending['source_file']);
			display_notification(sprintf(
				_('Price import complete (batch %s): %d row(s) applied (%d inserted, %d updated). %d row(s) were rejected and not applied.'),
				$batch, count($to_apply), $pending['summary']['insert'], $pending['summary']['update'], $pending['summary']['reject']
			));
		}
		unset($_SESSION[KNB_PRICE_IMPORT_SESSION_KEY]);
	}
}

// Cancel: drop the pending preview without applying anything.
if (isset($_POST['DoCancel']))
{
	unset($_SESSION[KNB_PRICE_IMPORT_SESSION_KEY]);
}

//---------------------------------------------------------------------------------------------------

$pending = @$_SESSION[KNB_PRICE_IMPORT_SESSION_KEY];

if ($pending)
{
	// Preview / confirm screen - no upload control here; the parsed rows
	// already live in $_SESSION from the preview step above.
	display_note(sprintf(_("Preview of '%s' (all prices in %s). Nothing has been written to the database yet."),
		htmlspecialchars($pending['source_file'], ENT_QUOTES, 'UTF-8'), $pending['curr_abrev']), 0, 1);

	$s = $pending['summary'];
	start_table(TABLESTYLE2, "width='60%'");
	label_row(_("Rows in file").':', $s['total']);
	label_row(_("Will be inserted (new price)").':', $s['insert']);
	label_row(_("Will be updated (existing price)").':', $s['update']);
	label_row(_("Rejected (will NOT be applied)").':', $s['reject']);
	if ($s['superseded'])
		label_row(_("Superseded by a later row for the same item (will NOT be applied)").':', $s['superseded']);
	end_table(1);

	start_table(TABLESTYLE);
	$th = array(_("Line"), _("Item Code"), _("Description"), _("Sales Type"), _("Price"), _("Result"));
	table_header($th);
	$k = 0;
	foreach ($pending['rows'] as $row)
	{
		alt_table_row_color($k);
		label_cell($row['line']);
		label_cell(htmlspecialchars($row['item_code'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars(@$row['description'], ENT_QUOTES, 'UTF-8'));
		label_cell(htmlspecialchars(@$row['sales_type_name'] !== null ? $row['sales_type_name'] : $row['sales_type_raw'], ENT_QUOTES, 'UTF-8'));
		label_cell(isset($row['price']) ? price_format($row['price']) : htmlspecialchars($row['price_raw'], ENT_QUOTES, 'UTF-8'));
		label_cell(price_import_status_label($row));
		end_row();
	}
	end_table(1);

	start_form();
	echo "<center>";
	if ($s['insert'] + $s['update'] > 0)
	{
		submit('DoImport', _("Confirm Import"), true, false, false, ICON_OK);
		echo "&nbsp;";
	}
	submit('DoCancel', _("Cancel"), true, false, false, ICON_CANCEL);
	echo "</center>";
	end_form();
}
else
{
	// Upload screen.
	display_note(_("Upload a CSV to add or update item sales prices in bulk. Nothing is written to the database until you review the preview and click Confirm Import."), 0, 1);
	display_note(_("Expected columns (header row required): item_code, price, and either sales_type_id (numeric) or sales_type (name, e.g. \"Retail\")."), 0, 1);
	if ($default_curr_abrev != get_company_currency())
		display_warning(sprintf(
			_("Note: existing item prices in this system are mostly in %s, but the company's configured base currency is %s - the currency below defaults to %s (matching existing prices) rather than the company default, so this import updates those rows instead of creating duplicates in a different currency. Check with whoever manages company settings if this looks wrong."),
			$default_curr_abrev, get_company_currency(), $default_curr_abrev
		));

	start_form(true);
	start_table(TABLESTYLE2);
	currencies_list_row(_("Currency for imported prices").':', 'curr_abrev', $default_curr_abrev);
	file_row(_("Price CSV File").':', 'price_file');
	end_table();
	submit_center('DoPreview', _("Preview Import"));
	end_form();

	$hist = get_recent_price_import_batches();
	if (db_num_rows($hist))
	{
		br();
		display_note(_("Recent import batches (audit trail):"), 0, 1);
		start_table(TABLESTYLE);
		$th = array(_("Batch"), _("File"), _("By"), _("When"), _("Rows"), _("Inserted"), _("Updated"));
		table_header($th);
		$k = 0;
		while ($row = db_fetch($hist))
		{
			alt_table_row_color($k);
			label_cell($row['import_batch']);
			label_cell(htmlspecialchars($row['source_file'], ENT_QUOTES, 'UTF-8'));
			label_cell(htmlspecialchars($row['imported_by_name'], ENT_QUOTES, 'UTF-8'));
			label_cell($row['imported_at']);
			label_cell($row['row_count']);
			label_cell($row['inserted']);
			label_cell($row['updated']);
			end_row();
		}
		end_table(1);
	}
}

end_page();
