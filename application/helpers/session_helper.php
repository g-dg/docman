<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Connects to the database and runs some setup SQL statements
 * @param this_obj The CodeIgniter Object
 */
function setup_session()
{
	$CI = &get_instance();
	$CI->load->library('session');

	/*if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}*/

	if (!isset($_SESSION['docman_csrf_token'])) {
		$_SESSION['docman_csrf_token'] = hash('sha512', $CI->security->get_random_bytes(4096));
	}
}

/**
 * Checks the CSRF token
 * Note: this does not yet prevent timing attacks.
 * @param csrf_token The token to check against
 * @return bool Whether the check passed
 */
function check_csrf_token($csrf_token)
{
	setup_session();
	return ($csrf_token === $_SESSION['docman_csrf_token']);
}

/**
 * Gets the CSRF token
 * @return string the CSRF token
 */
function get_csrf_token()
{
	setup_session();
	return $_SESSION['docman_csrf_token'];
}
