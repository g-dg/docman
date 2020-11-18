<?php
defined('BASEPATH') or exit('No direct script access allowed');

$GLOBALS['mimetypes'] = [];

/**
 * Updates mimetypes property with types in ./resources/mime.types
 */
function get_mimetype_for_extension($test_extension)
{
	global $mimetypes;

	if (count($mimetypes) == 0) {

		$types_file = @file('./resources/mime.types');
		if ($types_file === false) {
			log_message('error', 'File "resources/mime.types" not present.');
			return false;
		}

		foreach ($types_file as $line) {
			$line = trim($line);
			if (strlen($line) > 0 && substr($line, 0, 1) !== '#') {
				$extensions = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
				if (count($extensions) > 0) {
					$type = array_shift($extensions);
					foreach ($extensions as $extension) {
						$mimetypes[$extension] = $type;
					}
				}
			}
		}
	}

	if (isset($mimetypes[$test_extension])) {
		return $mimetypes[$test_extension];
	} else {
		return null;
	}
}
