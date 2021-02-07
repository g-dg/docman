<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Filter extends CI_Controller
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

		if (isset($_POST['_csrf_token']) && check_csrf_token($_POST['_csrf_token'])) {
			if (isset($_POST['filter'])) {
				if (trim($_POST['filter']) !== '') {
					$_SESSION['filter'] = $_POST['filter'];
				} else {
					unset($_SESSION['filter']);
				}

				redirect(site_url('/browse' . $this->url_encode_path($path)));
			} else {
				set_status_header(400);
			}
		} else {
			set_status_header(403);
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
