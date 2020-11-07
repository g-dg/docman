<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Browse extends CI_Controller
{

	/**
	 * Main browse page
	 * Each part of the requested path is specified in each parameter
	 */
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

		// check if it's a file
		if ($this->filesystem->filetype($path) != 'dir') {
			redirect(site_url('/file/open' . $this->url_encode_path($path)));
			return;
		}

		// get file information
		$files = [];
		$dh = $this->filesystem->opendir($path);
		if (!$dh) {
			show_404();
			return;
		}

		$files = [];
		if (rtrim($path, '/') !== '') {
			$files[] = [
				'name' => '..',
				'realname' => '..',
				'url' => site_url('/browse' . dirname($path)),
				'type' => 'dir',
				'size' => $this->filesystem->filecount(dirname($path)),
				'mtime' => $this->filesystem->filemtime(dirname($path))
			];
		}

		while (($file = $this->filesystem->readdir($dh)) !== false) {
			if ($file !== '.' && $file !== '..') {

				$filepath = rtrim($path, '/') . '/' . $file;

				$displayname = $this->filesystem->get_display_name($filepath);

				$filetype = $this->filesystem->filetype($filepath);

				switch ($filetype) {
					case 'dir':
						$displayname .= '/';
						$url = site_url('/browse' . $filepath);
						$size = $this->filesystem->filecount($filepath);
						break;
					case 'file':
						$url = site_url('/file' . $filepath);
						$size = $this->filesystem->filesize($filepath);
						break;
					default:
						$url = site_url('/browse' . $path);
						$size = null;
						break;
				}

				$mtime = $this->filesystem->filemtime($filepath);

				$files[] = [
					'name' => $displayname,
					'realname' => $file,
					'url' => $url,
					'type' => $filetype,
					'size' => $size,
					'mtime' => $mtime
				];
			}
		}

		$this->filesystem->closedir($dh);

		usort($files, function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

		$this->load->view('browse', ['files' => $files]);
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
