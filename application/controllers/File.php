<?php
defined('BASEPATH') or exit('No direct script access allowed');

class File extends CI_Controller
{
	private $mimetypes = [];

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

	/**
	 * Updates mimetypes property with types in ./resources/mime.types
	 */
	private function update_mimetypes()
	{
		$types_file = @file('./resources/mime.types');
		if ($types_file === false) {
			log_message('error', 'File "resources/mime.types" not present.');
			return false;
		}

		foreach ($types_file as $line) {
			$line = trim($line);
			if (strlen($line) > 0 && substr($line, 0, 1) !== '#') {
				$extensions = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
				if (count($extensions) > 0) {
					$type = array_shift($extensions);
					foreach ($extensions as $extension) {
						$this->mimetypes[$extension] = $type;
					}
				}
			}
		}
	}

	/**
	 * Sends a file to the client, supporting HTTP range requests
	 * Does not currently work properly with files > 2GB on 32-bit systems
	 * //TODO: Ensure full compliance (currently only partially compliant)
	 * //TODO: Port to 32-bit
	 */
	private function send_file($path, $force_download)
	{
		db_connect();
		setup_session();

		$this->authentication->require_login();

		$this->load->library('filesystem');

		$fh = $this->filesystem->fopen($path, 'r');
		if (!$fh) {
			http_response_code(404);
			return;
		}

		$this->update_mimetypes();

		ob_end_clean();

		header('Accept-Ranges: bytes');

		$pathinfo = pathinfo($path);
		if (isset($pathinfo['extension']) && isset($this->mimetypes[$pathinfo['extension']])) {
			header('Content-Type: ' . $this->mimetypes[$pathinfo['extension']]);
		} else {
			header('Content-Type: application/octet-stream');
		}

		if ($force_download) {
			header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9-]/', '_', basename($path)) . '"');
		}

		$content_length = $this->filesystem->filesize($path);

		if (isset($_SERVER['HTTP_RANGE'])) {
			list($start, $end) = explode("-", explode(",", explode("=", $_SERVER['HTTP_RANGE'], 2)[1], 2)[0]);

			// Check if we need to send the whole file
			if ($end = '') {
				$end = $content_length - 1;
			} else {
				$end = (int)$end;
			}
			$send_length = $end - (int)$start + 1;

			// Check if range is valid
			if ($start > $content_length || $end > $content_length) {
				http_response_code(416);
				header('Content-Length: ' . $content_length);
				return;
			}

			http_response_code(206); // Partial length
			header('Content-Length: ' . $send_length);
			header('Content-Range: bytes ' . $start . '-' . $end . '/' . $content_length);
		} else {
			$send_length = $content_length;
			header('Content-Length: ' . $content_length);
		}

		// seek to beginning if needed
		if (isset($start)) {
			$this->filesystem->fseek($fh, (int)$start);
		}

		set_time_limit(0);

		$sent = 0;
		while (!$this->filesystem->feof($fh) && !connection_aborted() && $sent < $send_length) {
			$buffer = $this->filesystem->fread($fh, 4096);
			echo $buffer;
			$sent += strlen($buffer);
		}

		$this->filesystem->fclose($fh);
	}
}
