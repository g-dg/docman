<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends CI_Controller
{
	public function index()
	{
		db_connect();
		setup_session();

		$path_array = func_get_args();
		for ($i = 0; $i < count($path_array); $i++) {
			$path_array[$i] = rawurldecode($path_array[$i]);
		}

		$this->authentication->require_login();

		$path = '/' . implode('/', $path_array);

		$this->load->library('filesystem');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['_csrf_token']) && check_csrf_token($_POST['_csrf_token'])) {
				switch ($_GET['action']) {
					case 'change_password':
						if (!isset($_POST['old_password'], $_POST['new_password1'], $_POST['new_password2'])) {
							set_status_header(400);
							return;
						}

						// check if guest
						if ($this->authentication->get_current_user_type() == 'guest') {
							set_status_header(403);
							return;
						}

						// get current hash
						$user_id = $this->authentication->get_current_user_id();
						$result = $this->db->query('SELECT "password" FROM "users" WHERE "id" = ?;', [$user_id])->result_array();
						if (!isset($result[0], $result[0]['password'])) {
							set_status_header(400);
							return;
						}

						if (!password_verify($_POST['old_password'], $result[0]['password'])) {
							echo 'Old password is incorrect.';
							return;
						} else {
							$this->db->query('UPDATE "users" SET "password" = ? WHERE "id" = ?;', [password_hash($_POST['new_password1'], PASSWORD_DEFAULT), $user_id]);
							redirect($this->config->site_url('/settings'));
						}

						break;
				}
			} else {
				set_status_header(403);
				return;
			}
		} else {

			$this->load->view('settings', [
				'username' => $this->authentication->get_current_username(),
				'user_id' => $this->authentication->get_current_user_id(),
				'user_type' => $this->authentication->get_current_user_type(),
				'user_groups' => $this->authentication->get_current_user_groups(),
				'allow_password_change' => ($this->authentication->get_current_user_type() != 'guest')
			]);

		}
	}

	private function url_encode_path($path)
	{
		$path_array = explode('/', trim($path, '/'));
		$new_path_array = [];
		foreach ($path_array as $path_part) {
			$new_path_array[] = rawurlencode($path_part);
		}
		return '/' . implode('/', $new_path_array);
	}
}
