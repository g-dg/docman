<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends CI_Controller
{
	public function index()
	{
		$this->setup();
		$this->load->view('admin/index');
	}

	public function users()
	{
		$this->setup();

		$users = $this->db->query('SELECT "id", "username", "type", "full_name" FROM "users";')->result_array();

		$this->load->view('admin/users', [
			'current_user_id' => $this->authentication->get_current_user_id(),
			'users' => $users
		]);
	}

	public function groups()
	{
		$this->setup();

		$groups = $this->db->query('SELECT "id", "name" FROM "mountpoints";')->result_array();

		$this->load->view('admin/groups', [
			'groups' => $groups
		]);
	}

	public function mountpoints()
	{
		$this->setup();

		$mountpoints = $this->db->query('SELECT "id", "destination_path", "driver", "driver_options" FROM "mountpoints";')->result_array();

		$this->load->view('admin/mountpoints', [
			'mountpoints' => $mountpoints
		]);
	}

	public function action()
	{
		$this->setup();
		
		// check csrf token
		if (!isset($_POST['_csrf_token'], $_GET['action']) || !check_csrf_token($_POST['_csrf_token'])) {
			http_response_code(400);
			return;
		}

		switch ($_GET['action']) {
			case 'user_create':
				$this->db->query('INSERT INTO "users" ("username", "password", "type", "settings") VALUES (?, ?, ?, \'{}\');', [
					$_POST['username'],
					password_hash($_POST['password'], PASSWORD_DEFAULT),
					(int)$_POST['user_type']
				]);
				$user_id = $this->db->insert_id();

				$this->db->query('INSERT INTO "groups" ("name") VALUES (?);', [$_POST['username']]);
				$group_id = $this->db->insert_id();

				$this->db->query('INSERT INTO "users_in_groups" ("user_id", "group_id") VALUES (?, ?);', [$user_id, $group_id]);

				$file_id = (int)($this->db->query('SELECT "id" FROM "files" WHERE "mountpoint_driver_info" = \'{"_root":true}\';')->result_array()[0]['id']);

				$this->db->query('INSERT INTO "file_permissions" ("file_id", "group_id", "read", "write", "share", "expires") VALUES (?, ?, 1, 1, 1, NULL);', [
					$file_id,
					$group_id
				]);
				break;
			case 'user_change_type':
				$this->db->query('UPDATE "users" SET "type" = ? WHERE "id" = ?;', [$_POST['type'], $_POST['user_id']]);
				break;
			case 'user_delete':
				$username = $this->db->query('SELECT "username" FROM "users" WHERE "id" = ?;', [$_POST['user_id']])->result_array()[0]['username'];
				$this->db->query('DELETE FROM "users_in_groups" WHERE "user_id" = ?;', [$_POST['user_id']]);
				$group_id = $this->db->query('SELECT "id" FROM "groups" WHERE "name" = ?;', [$username])->result_array()[0]['id'];
				$this->db->query('DELETE FROM "file_permissions" WHERE "group_id" = ?;', [$group_id]);
				$this->db->query('DELETE FROM "groups" WHERE "id" = ?;', [$group_id]);
				$this->db->query('DELETE FROM "users" WHERE "id" = ?;', [$_POST['user_id']]);
				break;
		}

		redirect(site_url('/admin/users'));

	}

	/**
	 * Loads and sets up common libraries and makes sure the user is an admin
	 */
	private function setup()
	{
		db_connect();
		setup_session();
		$this->authentication->require_login();
		if ($this->authentication->get_current_user_type() !== 'admin') {
			http_response_code(403);
			return;
		}
	}
}
