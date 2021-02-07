<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Properties extends CI_Controller
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

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			if (isset($_POST['_csrf_token'], $_GET['action']) && check_csrf_token($_POST['_csrf_token'])) {

				switch ($_GET['action']) {
					case 'set_display_name':
						if (isset($_POST['new_display_name'])) {
							$this->filesystem->set_display_name($path, $_POST['new_display_name']);
						} else {
							set_status_header(400);
							return;
						}
						redirect($this->config->site_url('/properties' . $path));
						break;

					case 'cut':
						$_SESSION['clipboard_file'] = $path;
						$_SESSION['clipboard_mode'] = 'move';
						redirect($this->config->site_url('/properties' . $path));
						break;
					case 'copy':
						$_SESSION['clipboard_file'] = $path;
						$_SESSION['clipboard_mode'] = 'copy';
						redirect($this->config->site_url('/properties' . $path));
						break;
					case 'paste':
						if (isset($_SESSION['clipboard_file'], $_SESSION['clipboard_mode'])) {
							if ($this->filesystem->filetype($path) != 'dir') {
								// only paste into a directory
								redirect($this->config->site_url('/properties' . $path));
								return;
							}

							if ($_SESSION['clipboard_mode'] == 'move') {
								if ($this->filesystem->move($_SESSION['clipboard_file'], rtrim($path, '/') . '/' . basename($_SESSION['clipboard_file']))) {
									$_SESSION['clipboard_file'] = rtrim($path, '/') . '/' . basename($_SESSION['clipboard_file']);
									$_SESSION['clipboard_mode'] = 'copy';
									redirect($this->config->site_url('/properties' . $path));
								} else {
									log_message('error', 'Error moving file "' . $_SESSION['clipboard_file'] . '" to "' . rtrim($path, '/') . '/' . basename($_SESSION['clipboard_file']) . '"');
									echo 'An error occurred moving the file.';
								}
							} else {
								if ($this->filesystem->copy($_SESSION['clipboard_file'], rtrim($path, '/') . '/' . basename($_SESSION['clipboard_file']))) {
									redirect($this->config->site_url('/properties' . $path));
								} else {
									log_message('error', 'Error copy file "' . $_SESSION['clipboard_file'] . '" to "' . rtrim($path, '/') . '/' . basename($_SESSION['clipboard_file']) . '"');
									echo 'An error occurred copy the file.';
								}
							}
						}
						break;

					case 'delete':
						$this->filesystem->unlink($path);
						redirect($this->config->site_url('/browse' . dirname($path)));
						break;

				}

			} else {
				set_status_header(403);
				return;
			}

		} else {
			// display view

			$friendly_name = $this->filesystem->get_display_name($path);

			$file_type = $this->filesystem->filetype($path);
			$writable = $this->filesystem->is_writable($path);

			$this->load->view('properties', [
				'title' => $friendly_name,
				'file_path' => $path,
				'current_dir_link' => dirname($path),
				'friendly_name' => $friendly_name,
				'allow_cut' => $writable,
				'allow_copy' => true,
				'allow_paste' => isset($_SESSION['clipboard_file']) && $writable && $file_type == 'dir',
				'allow_rename' => $writable,
				'allow_delete' => $writable
			]);
		}
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
