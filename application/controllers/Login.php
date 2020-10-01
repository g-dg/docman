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
			if ($this->authentication->login($_POST['username'], $_POST['password'])) {
				
			} else {
				$_SESSION['docman_login_result'] = 'Incorrect username or password.';
				$this->load->helper('url');
				redirect(html_escape(rtrim($this->config->site_url(), '/')) . '/login/', 'location', 303);
			}
		} else {
			set_status_header(400);
			echo 'There was an error with your request. Please go back and try again.';
		}
	}
}
