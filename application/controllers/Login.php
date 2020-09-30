<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	/**
	 * Main login page
	 */
	public function index()
	{
		setup_session();

		$this->load->view('login');
	}

	/**
	 * Authenticates and logs in
	 */
	public function do_login()
	{
		db_connect();
		setup_session();
	}
}
