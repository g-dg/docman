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
		if (!isset($_POST['_csrf_token']) || !check_csrf_token($_POST['_csrf_token'])) {
			http_response_code(400);
			return;
		}


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
