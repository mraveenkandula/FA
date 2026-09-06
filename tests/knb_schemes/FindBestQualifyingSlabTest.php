<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/knb_schemes/manage/schemes_db.inc';

final class FindBestQualifyingSlabTest extends TestCase
{
	public function testPicksHighestQualifyingTargetRegardlessOfSlabOrder(): void
	{
		// slabs deliberately NOT in ascending target order - this is exactly
		// the bug fixed in schemes_eligibility_inquiry.php: picking the last
		// slab that matched in iteration order (rather than the highest
		// qualifying target) gave the wrong answer whenever slabs weren't
		// entered lowest-to-highest.
		$slabs = array(
			array('slab_no' => 2, 'slab_target_qty' => 100, 'scheme_text' => 'Silver'),
			array('slab_no' => 1, 'slab_target_qty' => 50, 'scheme_text' => 'Bronze'),
			array('slab_no' => 3, 'slab_target_qty' => 200, 'scheme_text' => 'Gold'),
		);

		$result = find_best_qualifying_slab($slabs, 150);

		$this->assertNotNull($result);
		$this->assertSame('Silver', $result['scheme_text']);
	}

	public function testReturnsNullWhenNoSlabQualifies(): void
	{
		$slabs = array(
			array('slab_no' => 1, 'slab_target_qty' => 50, 'scheme_text' => 'Bronze'),
		);

		$this->assertNull(find_best_qualifying_slab($slabs, 10));
	}

	public function testIgnoresSlabsWithZeroOrEmptyTarget(): void
	{
		$slabs = array(
			array('slab_no' => 1, 'slab_target_qty' => 0, 'scheme_text' => 'Empty slab'),
			array('slab_no' => 2, 'slab_target_qty' => 50, 'scheme_text' => 'Bronze'),
		);

		$result = find_best_qualifying_slab($slabs, 1000);

		$this->assertSame('Bronze', $result['scheme_text']);
	}

	public function testExactlyMeetingTheTargetQualifies(): void
	{
		$slabs = array(array('slab_no' => 1, 'slab_target_qty' => 100, 'scheme_text' => 'Bronze'));

		$result = find_best_qualifying_slab($slabs, 100);

		$this->assertSame('Bronze', $result['scheme_text']);
	}
}
