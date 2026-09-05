<?php

/* List of installed additional extensions. If extensions are added to the list manually
	make sure they have unique and so far never used extension_ids as a keys,
	and $next_extension_id is also updated. More about format of this file yo will find in 
	FA extension system documentation.
*/

$next_extension_id = 3; // unique id for next installed extension

$installed_extensions = array (
	1 => array(
		'name' => 'KNB Group HRM',
		'package' => 'knb_hrm',
		'version' => '1.0',
		'path' => 'modules/knb_hrm',
		'active' => true,
		'urank' => 1,
	),
	2 => array(
		'name' => 'KNB Group Banking',
		'package' => 'knb_banking',
		'version' => '1.0',
		'path' => 'modules/knb_banking',
		'active' => true,
		'urank' => 2,
	),
);
