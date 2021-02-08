<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Upload extends CI_Controller
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

		// process upload

		if (!isset($_POST['_csrf_token']) || !check_csrf_token($_POST['_csrf_token'])) {
			set_status_header(403);
			return;
		}

		if (!isset($_FILES['file'])) {
			set_status_header(400);
			return;
		}

		$filename = ltrim(preg_replace('/[^A-Za-z0-9_\\-.]/', '_', $_FILES['file']['name']), '.');

		$pathinfo = pathinfo($filename);
		$extension = isset($pathinfo['extension']) ? ('.' . $pathinfo['extension']) : '';

		$file_path = rtrim($path, '/') . '/' . substr(hash('sha512', $this->security->get_random_bytes(4096)), 0, 32) . $extension;

		// safety check
		if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
			exit('error: uploaded file path assertion failed');
		}

		$out = $this->filesystem->fopen($file_path, 'w');
		if (!$out) {
			log_message('error', 'Could not write uploaded file to "' . $file_path . '"');
			redirect(site_url('/browse' . $this->url_encode_path($path)));
			return;
		}
		$in = fopen($_FILES['file']['tmp_name'], 'rb');
		if (!$in) {
			log_message('error', 'Could not open uploaded file for "' . $file_path . '"');
			return;
		}

		while (!feof($in)) {
			$out->fwrite(fread($in, 4096));
		}
		fclose($in);
		$out->fclose();

		$this->filesystem->set_display_name($file_path, $filename);

		redirect(site_url('/browse' . $this->url_encode_path($path)));
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
