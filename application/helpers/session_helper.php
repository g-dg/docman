<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Connects to the database and runs some setup SQL statements
 * @param this_obj The CodeIgniter Object
 */
function setup_session()
{
	$CI =& get_instance();
	$CI->load->library('session');

	if (!isset($_SESSION['_csrf_token'])) {
		$_SESSION['_csrf_token'] = hash('sha512', $CI->security->get_random_bytes(256));
	}
}
