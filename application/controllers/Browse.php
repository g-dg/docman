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
			redirect(site_url('/file' . $this->url_encode_path($path)));
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

		$this->load->library('settings');
		$hide_dot_files = $this->settings->get('browse.hide_hidden', true);

		while (($file = $this->filesystem->readdir($dh)) !== false) {
			if ($file !== '.' && $file !== '..' && (!$hide_dot_files || substr($file, 0, 1) !== '.')) {

				$filepath = rtrim($path, '/') . '/' . $file;

				$displayname = $this->filesystem->get_display_name($filepath);

				$filetype = $this->filesystem->filetype($filepath);

				switch ($filetype) {
					case 'dir':
						$displayname .= '/';
						$url = site_url('/browse' . $this->url_encode_path($filepath));
						$size = $this->filesystem->filecount($filepath);
						break;
					case 'file':
						$url = site_url('/file' . $this->url_encode_path($filepath));
						$size = $this->filesystem->filesize($filepath);
						break;
					default:
						$url = site_url('/browse' . $this->url_encode_path($path));
						$size = null;
						break;
				}

				$mtime = $this->filesystem->filemtime($filepath);

				$tags = $this->filesystem->get_tags($filepath);

				$files[] = [
					'name' => $displayname,
					'realname' => $file,
					'url' => $url,
					'type' => $filetype,
					'size' => $size,
					'mtime' => $mtime,
					'tags' => $tags,
				];
			}
		}

		$this->filesystem->closedir($dh);

		$sort_field = 'name';
		$sort_order = 'asc';
		if (isset($_SESSION['browse.sort.field'])) {
			$sort_field = $_SESSION['browse.sort.field'];
		}
		if (isset($_SESSION['browse.sort.order'])) {
			$sort_order = $_SESSION['browse.sort.order'];
		}
		if (isset($_GET['sort'])) {
			$sort_field = $_GET['sort'];
		}
		if (isset($_GET['order'])) {
			$sort_order = $_GET['order'];
		}

		$_SESSION['browse.sort.field'] = $sort_field;
		$_SESSION['browse.sort.order'] = $sort_order;

		switch ($sort_field) {
			case 'mtime':
				if ($sort_order == 'desc') {
					usort($files, function ($b, $a) { // new to old
						return $a['mtime'] - $b['mtime'];
					});
				} else {
					usort($files, function ($a, $b) { // old to new
						return $a['mtime'] - $b['mtime'];
					});
				}
				break;
			case 'size':
				if ($sort_order == 'desc') {
					usort($files, function ($b, $a) { // small to large
						return $a['size'] - $b['size'];
					});
				} else {
					usort($files, function ($a, $b) { // large to small
						return $a['size'] - $b['size'];
					});
				}
				break;
			default: // name
				if ($sort_order == 'desc') {
					usort($files, function ($b, $a) { // Z-A
						return strnatcasecmp($a['name'], $b['name']);
					});
				} else {
					usort($files, function ($a, $b) { // A-Z
						return strnatcasecmp($a['name'], $b['name']);
					});
				}
				break;
		}

		// Add previous directory link
		if (rtrim($path, '/') !== '') {
			array_unshift($files, [
				'name' => '..',
				'realname' => '..',
				'url' => site_url('/browse' . $this->url_encode_path(dirname($path))),
				'type' => 'dir',
				'size' => $this->filesystem->filecount(dirname($path)),
				'mtime' => $this->filesystem->filemtime(dirname($path))
			]);
		}

		$this->load->view('browse', [
			'current_dir_link' => $this->url_encode_path($path),
			'files' => $files,
			'sort_field' => $sort_field,
			'sort_order' => $sort_order
		]);
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
