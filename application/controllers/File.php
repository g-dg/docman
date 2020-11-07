<?php
defined('BASEPATH') or exit('No direct script access allowed');

class File extends CI_Controller
{

	public function open()
	{
		$path_array = func_get_args();
		for ($i = 0; $i < count($path_array); $i++) {
			$path_array[$i] = rawurldecode($path_array[$i]);
		}

		$path = '/' . implode('/', $path_array);

		return $this->send_file($path, false);
	}

	public function download()
	{
		$path_array = func_get_args();
		for ($i = 0; $i < count($path_array); $i++) {
			$path_array[$i] = rawurldecode($path_array[$i]);
		}

		$path = '/' . implode('/', $path_array);

		return $this->send_file($path, true);
	}

	private function send_file($path, $force_download)
	{
		db_connect();
		setup_session();

		$this->authentication->require_login();

		$this->load->library('filesystem');
	}
}
