<?php
/*
	PHPUnit has no built-in "fail if below X%" flag the way Jest's
	coverageThreshold does - this parses the Clover XML report it produces
	and enforces the gate ourselves. Scoped to this fork's own knb_* code
	only (see phpunit.xml's <source><include>) - not FrontAccounting's
	~50k-line upstream core, which isn't ours to test.
*/

$threshold = 80.0;
$cloverFile = $argv[1] ?? 'coverage.xml';

if (!file_exists($cloverFile))
{
	fwrite(STDERR, "Coverage file not found: $cloverFile\n");
	exit(1);
}

$xml = simplexml_load_file($cloverFile);
if ($xml === false)
{
	fwrite(STDERR, "Could not parse coverage file: $cloverFile\n");
	exit(1);
}

$metrics = $xml->project->metrics ?? null;
if (!$metrics)
{
	fwrite(STDERR, "No <metrics> found in coverage file - did PHPUnit run any tests?\n");
	exit(1);
}

$statements = (int)$metrics['statements'];
$coveredStatements = (int)$metrics['coveredstatements'];

if ($statements === 0)
{
	fwrite(STDERR, "No statements measured - coverage source scope in phpunit.xml may be empty.\n");
	exit(1);
}

$pct = round(($coveredStatements / $statements) * 100, 2);

echo "Statement coverage: $pct% ($coveredStatements / $statements)\n";

if ($pct < $threshold)
{
	fwrite(STDERR, "Coverage $pct% is below the required $threshold% threshold.\n");
	exit(1);
}

echo "Coverage threshold met.\n";
exit(0);
