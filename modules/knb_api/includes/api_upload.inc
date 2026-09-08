<?php
/*
	Shared photo-upload handling for the mobile API. Three endpoints
	(attendance_selfie_post.php, outlet_photo_post.php, competitor_note_post.php)
	all need "take/pick one photo, validate it, land it on disk somewhere the
	web server can serve it back from, hand back a path to store in a DB
	row" - this is that logic in one place instead of copy-pasted three
	times, following the same validate-then-move_uploaded_file() shape as
	core FA's own inventory/manage/items.php (see its del_image()/
	show_image() and the $_FILES['pic'] handling around line 117-172),
	just wrapped as a function returning a value instead of a page emitting
	display_error()/display_warning() calls.

	Storage location: company_path().'/images/<subdir>/' - i.e. INSIDE the
	existing company/<n>/images directory tree, not a new top-level
	directory. That matters operationally: infrastructure/terraform/deploy.tf
	already does a recursive chmod 0777 on every company install's images
	dir (among a fixed list of writable dirs) on every deploy - see that
	file's comment block starting
	"FA writes to company/<n>/{images,pdf_files,backup,js_cache}". Landing
	these new subdirectories under images/ means that existing chmod already
	covers them; no deploy.tf change is needed. A subdirectory itself is
	created here on first use (mkdir 0777) if missing - since images/ is
	already 0777 from the deploy step, the web server user can always do
	that.
*/

/*
	Validates and moves an uploaded photo from $_FILES[$field_name] into
	company_path()."/images/$subdir/", using the same checks core FA's own
	items.php upload path applies (real image per getimagesize(), one of
	jpg/png/gif, under $SysPrefs->max_image_size KB).

	Returns the path to store in a DB column: relative to $path_to_root
	(e.g. "company/0/images/outlet_photos/xxx.jpg") so it's usable directly
	as a URL path off this same install's origin without leaking an
	absolute filesystem path - same relative-to-root shape company_path()
	itself resolves from.

	On any validation failure, calls api_error() (sends the JSON error
	response and exits) rather than returning - callers don't need their
	own error handling for the upload step itself.
*/
function api_handle_photo_upload($field_name, $subdir, $filename_prefix)
{
	global $path_to_root, $SysPrefs;

	if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE)
		api_error("No photo uploaded (expected multipart field '$field_name')");

	$file = $_FILES[$field_name];

	if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
		api_error('The photo is too large.');
	elseif ($file['error'] !== UPLOAD_ERR_OK)
		api_error('Error uploading photo.');

	$max_bytes = (isset($SysPrefs->max_image_size) ? (int)$SysPrefs->max_image_size : 1024) * 1024;
	if ($file['size'] <= 0)
		api_error('The uploaded photo is empty.');
	if ($file['size'] > $max_bytes)
		api_error('The photo exceeds the maximum allowed size of '.round($max_bytes / 1024).' KB.');

	$imageinfo = @getimagesize($file['tmp_name']);
	if ($imageinfo === false)
		api_error('Only image files can be uploaded.');
	$imagetype = $imageinfo[2];
	if (!in_array($imagetype, array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF), true))
		api_error('Only JPEG, PNG or GIF photos are supported.');

	$ext_map = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
	$ext = $ext_map[$imagetype];

	$dir = company_path().'/images/'.$subdir;
	if (!file_exists($dir))
		mkdir($dir, 0777, true);

	$basename = preg_replace('/[^A-Za-z0-9_-]/', '', $filename_prefix);
	$unique = $basename.'_'.date('YmdHis').'_'.substr(bin2hex(random_bytes(4)), 0, 8).'.'.$ext;
	$full_path = $dir.'/'.$unique;

	if (!move_uploaded_file($file['tmp_name'], $full_path))
		api_error('Could not save the uploaded photo.');

	// Relative-to-root path for storage/serving, mirroring how
	// company_path() itself is built ($path_to_root.'/company/<n>').
	$root = rtrim($path_to_root, '/');
	if (strpos($full_path, $root.'/') === 0)
		return substr($full_path, strlen($root) + 1);
	return $full_path;
}
