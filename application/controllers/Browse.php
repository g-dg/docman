<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Browse extends CI_Controller {

	/**
	 * Main browse page
	 * Parameters are each part of the path
	 */
	public function index()
	{
		$path_array = func_get_args();
		for ($i = 0; $i < count($path_array); $i++) {
			$path_array[$i] = rawurldecode($path_array[$i]);
		}
	}
}
