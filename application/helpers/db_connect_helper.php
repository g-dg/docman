<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Connects to the database and runs some setup SQL statements
 * @param this_obj The CodeIgniter Object
 */
function db_connect($this_obj)
{
	$this_obj->load->database();

	$this_obj->db->simple_query('PRAGMA journal_mode=WAL;');
	$this_obj->db->simple_query('PRAGMA synchronous=NORMAL;');

	//$this_obj->db->simple_query('PRAGMA temp_store=MEMORY;');
	//$this_obj->db->simple_query('PRAGMA cache_size=-16384');

	$this_obj->db->simple_query('PRAGMA foreign_keys = ON;');
}
