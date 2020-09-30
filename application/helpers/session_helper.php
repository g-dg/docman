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

	if (!isset($_SESSION['docman_csrf_token'])) {
		$_SESSION['docman_csrf_token'] = hash('sha512', $CI->security->get_random_bytes(256));
	}
}
