<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Search extends CI_Controller
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

		if (isset($_POST['_csrf_token']) && check_csrf_token($_POST['_csrf_token'])) {
			if (isset($_POST['q'])) {
				// replace everything that's not alpha-numeric with a space and split on spaces
				$queries = explode(' ', preg_replace('/[^A-Za-z0-9]/', ' ', strtoupper($_POST['q'])));
				// filter out any blank entries
				array_filter($queries, function($a){return $a !== '';});

				$result_paths = $this->doSearch($path, $queries);
				natcasesort($result_paths);

				$results = [];
				for ($i = 0; $i < count($result_paths); $i++) {
					$results[] = [
						'path' => $result_paths[$i],
						'display_name' => $this->filesystem->get_display_name($result_paths[$i])
					];
				}

				$this->load->view('search', [
					'title' => $this->filesystem->get_display_name($path),
					'current_dir_link' => $path,
					'results' => $results
				]);

			} else {
				set_status_header(400);
				return;
			}
		} else {
			set_status_header(403);
			return;
		}

	}

	// Recursive search function
	private function doSearch($path, $query_array, $levels_remaining = 5) {
		$found_files = [];
		$dh = $this->filesystem->opendir($path);
		if (!$dh)
			return [];
		// loop through files
		while (($file = $dh->readdir()) !== false) {
			if ($file === '.' || $file === '..')
				continue;

			$file_path = rtrim($path, '/') . '/' . $file;

			// get display name
			$display_name = $this->filesystem->get_display_name($file_path);

			// check for match
			$display_name_parts = explode(' ', preg_replace('/[^A-Za-z0-9]/', ' ', strtoupper($display_name)));
			if (count(array_intersect($query_array, $display_name_parts)) > 0) {
				$found_files[] = $file_path;
			}

			// recurse into directories
			if ($this->filesystem->filetype($file_path) === 'dir' && $levels_remaining > 0) {
				$found_files = array_merge($found_files, $this->doSearch($file_path, $query_array, $levels_remaining - 1));
			}
		}
		return $found_files;
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
