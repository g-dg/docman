<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends CI_Controller {

	/**
	 * Sets up the system according to the configuration
	 */
	public function index()
	{
		$this->config->load('setup');

		$default_username = $this->config->item('docman_default_username');
		$default_password = $this->config->item('docman_default_password');

		

	}
}
