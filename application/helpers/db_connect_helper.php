<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Connects to the database and runs some setup SQL statements
 * @param this_obj The CodeIgniter Object
 */
function db_connect()
{
	$CI =& get_instance();
	static $connected = false;

	if (!$connected) {
		$CI->load->database();

		$CI->db->simple_query('PRAGMA journal_mode=WAL;');
		$CI->db->simple_query('PRAGMA synchronous=NORMAL;');

		//$CI->db->simple_query('PRAGMA temp_store=MEMORY;');
		//$CI->db->simple_query('PRAGMA cache_size=-16384');

		$CI->db->simple_query('PRAGMA foreign_keys = ON;');

		$connected = true;
	}
}
