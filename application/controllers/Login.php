<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{

	/**
	 * Main login page
	 */
	public function index()
	{
		setup_session();

		$this->load->view('login');
		unset($_SESSION['docman_login_result']);
	}

	/**
	 * Authenticates and logs in
	 */
	public function do_login()
	{
		db_connect();
		setup_session();

		if (isset($_POST['_csrf_token'], $_POST['username'], $_POST['password']) && check_csrf_token($_POST['_csrf_token'])) {
			$this->load->helper('url');
			if ($this->authentication->login($_POST['username'], $_POST['password'])) {
				session_write_close();
				redirect($this->config->site_url(), 'location', 303);
			} else {
				$_SESSION['docman_login_result'] = 'Incorrect username or password.';
				session_write_close();
				redirect(rtrim($this->config->site_url(), '/') . '/login/', 'location', 303);
			}
		} else {
			set_status_header(400);
			echo 'There was an error with your request. Please go back and try again.';
		}
	}

	/**
	 * Logs the user out
	 */
	public function logout()
	{
		setup_session();

		$this->authentication->logout();
	}
}
