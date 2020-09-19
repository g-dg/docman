<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Browse extends CI_Controller {

	/**
	 * Main browse page
	 * Each part of the requested path is specified in each parameter
	 */
	public function index()
	{
		$this->load->library('session');

		$path_array = func_get_args();
		for ($i = 0; $i < count($path_array); $i++) {
			$path_array[$i] = rawurldecode($path_array[$i]);
		}


	}
}
