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
			$user_id = $this->db->insert_id();

			$this->db->query('INSERT INTO "groups" ("name") VALUES (?);', [$default_username]);
			$group_id = $this->db->insert_id();

			$this->db->query('INSERT INTO "users_in_groups" ("user_id", "group_id") VALUES (?, ?);', [$user_id, $group_id]);

			// check if we need to create a mountpoint in case all mountpoints also got deleted
			if ((int)($this->db->query('SELECT COUNT() AS "count" FROM "mountpoints";')->result_array()[0]['count']) === 0) {
				$this->db->query('INSERT INTO "mountpoints" ("destination_path", "driver", "driver_options") VALUES (\'/\', \'server_fs\', ?);', [
					json_encode([
						'server_fs' => [
							'path' => $this->config->item('docman_default_data_directory')
						]
					])
				]);
				$mountpoint_id = $this->db->insert_id();

				$this->db->query('INSERT INTO "files" ("mountpoint_id", "path_in_mountpoint", "display_name", "owner_user_id", "mountpoint_driver_info") VALUES (?, \'/\', NULL, ?, \'{}\')', [
					$mountpoint_id,
					$user_id,
				]);
				$file_id = $this->db->insert_id();

				$this->db->query('INSERT INTO "file_permissions" ("file_id", "group_id", "read", "write", "share", "expires") VALUES (?, ?, 1, 1, 1, NULL);', [
					$file_id,
					$group_id
				]);
			}
		}

		redirect(site_url(), 'location', 303);
	}
}
