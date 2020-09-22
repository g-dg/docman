<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends CI_Controller {

	/**
	 * Sets up the system according to the configuration
	 */
	public function index()
	{
		db_connect($this);
		$this->config->load('setup');

		$default_username = $this->config->item('docman_default_username');
		$default_password = $this->config->item('docman_default_password');

		// we can attempt to recreate the database, even if it's already set up. This can act as a sort of recovery function.
		$create_db_sql = file('./resources/database_schema.min.sql');
		foreach ($create_db_sql as $sql) {
			if (trim($sql) !== '') {
				$this->db->simple_query($sql);
			}
		}

		// only create the default admin user if there is no other admin user (in case all the admin accounts somehow get deleted)
		$query = $this->db->query('SELECT COUNT() AS "count" FROM "users" WHERE "type" = 0;');
		$row = $query->row_array();
		if ($row['count'] == 0) {
			$this->db->query('INSERT INTO "users" ("username", "password", "type", "settings") VALUES (?, ?, 0, \'{}\');', [$default_username, password_hash($default_password, PASSWORD_DEFAULT)]);
		}

	}
}
