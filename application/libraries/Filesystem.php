<?php
defined('BASEPATH') or exit('No direct script access allowed');

// paths will be sent to the drivers with a leading slash but no trailing slash.

class Filesystem
{
	private $CI;

	private $mountpoints = [];

	public function __construct()
	{
		$this->CI = &get_instance();

		$this->CI->load->library('authentication');
	}

	public function __destruct()
	{
		foreach ($this->mounts as $mount) {
			$mount->unmount();
		}
	}

	/**
	 * Sets up and returns required mountpoint
	 * @return array ['mount' => <mounted and set up mountpoint>, 'internal_path' => <path inside mountpoint>]
	 */
	protected function get_mountpoint($full_path)
	{
		$mountpoint_info = $this->get_mountpoint_info($full_path);

		if (!isset($this->mountpoints[(int)$mountpoint_info['id']])) {

			$driver = $mountpoint_info['driver'];
			$driver_options = json_decode($mountpoint_info['driver_options'], true);
			if (json_last_error() != JSON_ERROR_NONE) {
				log_message('error', 'Malformed driver option JSON data for mountpoint id# ' . $mountpoint_info['id'] . '.');
				return false;
			}

			$new_mountpoint = null;
			switch ($driver) {
				case 'server_fs':
					$this->CI->load->helper('FilesystemServerFSDriver');
					$new_mountpoint = new FilesystemServerFSDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;

				case 'database':
					$this->CI->load->helper('FilesystemDatabaseDriver');
					$new_mountpoint = new FilesystemDatabaseDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;

					/*case 'remote':
					$this->CI->load->helper('FilesystemRemoteDriver');
					$new_mountpoint = new FilesystemRemoteDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;*/

				case 'driver_options':
					$this->CI->load->helper('FilesystemDriverOptionsDriver');
					$new_mountpoint = new FilesystemDriverOptionsDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;

				case 'driver_options_writable':
					$this->CI->load->helper('FilesystemDriverOptionsDriver');
					$new_mountpoint = new FilesystemDriverOptionsDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;

				case 'dummy':
					$this->CI->load->helper('FilesystemDummyDriver');
					$new_mountpoint = new FilesystemDummyDriver($driver, $driver_options, (int)$mountpoint_info['id']);
					break;

				default:
					log_message('debug', 'Mountpoint driver "' . $driver . '" for mountpoint id# ' . $mountpoint_info['id'] . ' is not supported.');
					break;
			}

			if (is_null($new_mountpoint)) {
				$this->CI->load->helper('FilesystemDummyDriver');
				$new_mountpoint = new FilesystemDummyDriver('dummy', $driver_options, (int)$mountpoint_info['id']);
			}

			if (!$new_mountpoint->mount()) {
				log_message('error', 'An error occurred while mounting "' . $driver . '" driver for mountpoint id# ' . $mountpoint_info['id'] . '.');
			}

			$this->mountpoints[(int)$mountpoint_info['id']] = $new_mountpoint;
		}

		return [
			'mountpoint' => $this->mountpoints[(int)$mountpoint_info['id']],
			'internal_path' => $this->get_path_in_mountpoint($full_path),
		];
	}

	/**
	 * Sanitizes and flattens paths.
	 * This is a naive implementation that only operates on the string
	 */
	public function sanitize_path($path)
	{
		$path_array = explode('/', trim($path, '/'));
		$sanitized_array = [];
		foreach ($path_array as $path_part) {
			if ($path_part === '..') {
				if (count($sanitized_array) > 0) {
					array_pop($sanitized_array);
				}
			} else if ($path_part !== '' && $path_part !== '.') {
				$sanitized_array[] = $path_part;
			}
		}
		return ('/' . implode('/', $sanitized_array));
	}

	/**
	 * Gets the mountpoint info for a file path
	 * @param path The full path to get the mountpoint info for
	 * @return array the mountpoint definition or null if no match
	 */
	protected function get_mountpoint_info($path)
	{
		$path_parts = explode('/', trim($path, '/'));
		$closest_match = null;
		$closest_match_count = 0;

		$mountpoint_defs_res = $this->CI->db->query('SELECT "id", "destination_path" FROM "mountpoint_defs" ORDER BY "id";')->result_array();
		$mountpoint_defs = [];
		foreach ($mountpoint_defs_res as $mountpoint_def) {
			$mountpoint_defs[(int)$mountpoint_def['id']] = [
				'id' => (int)$mountpoint_def['id'],
				'destination_path' => $mountpoint_def['destination_path'],
				'driver_options' => $mountpoint_def['driver_options']
			];
		}

		foreach ($mountpoint_defs as $mountpoint) {
			$mountpoint_dest_parts = explode('/', trim($mountpoint['destination_path'], '/'));

			if (count($mountpoint_dest_parts) <= count($path_parts)) { // mountpoint path must be shorter or equal to the requested path
				$is_match = true;
				$match_length = 0;
				for ($i = 0; $i < count($mountpoint_dest_parts); $i++) {
					if ($mountpoint_dest_parts[$i] === $path_parts[$i]) {
						$match_length++;
					} else {
						$is_match = false;
						break;
					}
				}

				if ($is_match && $match_length >= $closest_match_count) {
					$closest_match = $mountpoint;
					$closest_match_count = $match_length;
				}
			}
		}

		return $closest_match;
	}

	/**
	 * Gets the path of a file inside its mountpoint
	 * @return string The path inside of the mountpoint (with only leading slashes)
	 */
	public function get_path_in_mountpoint($full_path)
	{
		$mountpoint_dest = $this->get_mountpoint_info($full_path)['destination_path'];

		$full_path_parts = explode('/', trim($full_path, '/'));
		$mountpoint_dest_parts = explode('/', trim($mountpoint_dest, '/'));

		$path_parts = array_slice($full_path_parts, count($mountpoint_dest_parts));

		return '/' . implode('/', $path_parts);
	}

	/**
	 * Gets the db entry of a file or its parent
	 * @param full_path The full path to check
	 * @param create Creates the file entry with default values if it doesn't yet exist
	 * @param get_parent_if_null Recursively gets the parent id if null, not to be used for writes
	 */
	public function get_file_db_entry_id($full_path, $create = false, $get_parent_if_null = false)
	{
		$mountpoint_id = $this->get_mountpoint_info($full_path)['id'];
		$mountpoint_path = $this->get_path_in_mountpoint($full_path);

		$result = $this->CI->db->query('SELECT "id" FROM "files" WHERE "mountpoint_id" = ? AND "path_in_mountpoint" = ?;', [$mountpoint_id, $mountpoint_path])->result_array();
		if (isset($result[0])) {
			return (int)$result[0]['id'];
		} else if ($create) {
			$this->CI->db->query('INSERT INTO "files" ("mountpoint_id", "path_in_mountpoint", "owner_user_id", "mountpoint_driver_info") VALUES (?, ?, ?, ?);', [
				$mountpoint_id,
				$mountpoint_path,
				$this->get_owner_id($full_path), // get the owner id, shouldn't run recursively since getting the owner id should not create a new file id
				'{}'
			])->result_array();
			return (int)$this->CI->db->insert_id();
		} else if ($get_parent_if_null) {
			$full_path_parts = explode('/', trim($full_path, '/'));
			if (count($full_path_parts) > 1) {
				array_pop($full_path_parts);
				return $this->get_file_db_entry_id(('/' . implode('/', $full_path_parts)), false, true);
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Gets an array with the file db entries from highest to lowest specificity
	 */
	public function get_parent_file_db_entry_ids($full_path)
	{
		$full_path_parts = explode('/', trim($full_path, '/'));
		$full_path_part_count = count($full_path_parts);

		$file_db_entry_ids = [];

		while (count($full_path_parts) > 0) {
			$path = '/' . implode('/', $full_path_parts);
			$mountpoint_id = $this->get_mountpoint_info($path)['id'];
			$mountpoint_path = $this->get_path_in_mountpoint($path);

			$result = $this->CI->db->query('SELECT "id" FROM "files" WHERE "mountpoint_id" = ? AND "path_in_mountpoint" = ?;', [$mountpoint_id, $mountpoint_path])->result_array();
			if (isset($result[0])) {
				$file_db_entry_ids[] = (int)$result[0]['id'];
			}

			array_pop($full_path_parts);
		}

		return $file_db_entry_ids;
	}

	/**
	 * Gets the permissions of the current user for a file
	 * @param full_path The full path of the file
	 * @return array Containing the 'read', 'write', and 'share' permissions
	 */
	public function get_db_permissions($full_path)
	{
		$this->CI->load->library('authentication');

		$file_db_entries = $this->get_parent_file_db_entry_ids($full_path);

		$group_ids = $this->CI->authentication->get_current_user_groups();

		$group_statuses = [];

		for ($i = 0; $i < count($file_db_entries); $i++) {
			$permissions_res = $this->CI->db->query(
				'SELECT "group_id", "read", "write", "share" FROM "file_permissions" WHERE "file_id" = ? AND "group_id" IN ? AND ("expires" IS NULL OR "expires" > ?);',
				[
					$file_db_entries[$i],
					$group_ids,
					time()
				]
			)->result_array();

			foreach ($permissions_res as $perm_rec) {
				$group_id = (int)$perm_rec['group_id'];
				$read = ($perm_rec['read'] == 1);
				$write = ($perm_rec['write'] == 1);
				$share = ($perm_rec['share'] == 1);

				if (isset($group_statuses[$group_id])) {
					$read = $read && $group_statuses[$group_id]['read'];
					$write = $write && $group_statuses[$group_id]['write'];
					$share = $share && $group_statuses[$group_id]['share'];
				} else {
					$group_statuses[$group_id] = ['read' => $read, 'write' => $write, 'share' => $share];
				}
			}
		}

		$read = false;
		$write = false;
		$share = false;
		foreach ($group_statuses as $group_status) {
			$read = $read || $group_status['read'];
			$write = $write || $group_status['write'];
			$share = $share || $group_status['share'];
		}

		return ['read' => $read, 'write' => $write, 'share' => $share];
	}



	public function opendir($path)
	{
		if (!$this->get_db_permissions($path)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to open directory "' . $path . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($path)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to open directory "' . $path . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($path));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->opendir($internal_path);
	}

	public function closedir($directory_handle)
	{
		return $directory_handle->closedir();
	}

	public function readdir($directory_handle)
	{
		return $directory_handle->readdir();
	}

	public function rewinddir($directory_handle)
	{
		return $directory_handle->rewinddir();
	}


	public function fopen($path, $mode)
	{
		$perms = $this->get_db_permissions($path);

		if (!$perms['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to open file "' . $path . '" without read permission.');
			return false;
		}

		if (!in_array($mode, ['r', 'r+', 'w', 'w+', 'a', 'a+'])) {
			log_message('error', 'Attempted to open file (fopen) "' . $path . '" with disallowed mode "' . $mode . '".');
			return false;
		}

		if (in_array($mode, ['r+', 'w', 'w+', 'a', 'a+']) && !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to open file "' . $path . '" for writing without write permission.');
			return false;
		}

		// Note: there is no readability/writability checking here since this can be used to create a file that would seem to be not readable/writable

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($path));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->fopen($internal_path, $mode);
	}

	public function fclose($file_handle)
	{
		return $file_handle->fclose();
	}

	public function fread($file_handle, $length)
	{
		return $file_handle->fread($length);
	}

	public function fwrite($file_handle, $string, $length = null)
	{
		return $file_handle->fwrite($string, $length);
	}

	public function feof($file_handle)
	{
		return $file_handle->feof();
	}

	public function ftell($file_handle)
	{
		return $file_handle->ftell();
	}

	public function fseek($file_handle, $offset)
	{
		return $file_handle->fseek($offset);
	}

	public function rewind($file_handle)
	{
		return $file_handle->rewind();
	}


	public function disk_usage($directory) // returns associative array with keys 'free', 'used', 'total'
	{
		if (!$this->get_db_permissions($directory)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to check disk usage for directory "' . $directory . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($directory)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to check disk usage for directory "' . $directory . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($directory));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->disk_usage($internal_path);
	}

	public function file_exists($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to check if file "' . $filename . '" exists without read permission.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->file_exists($internal_path);
	}

	public function filemtime($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get modified time of file "' . $filename . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($filename)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get modified time of file "' . $filename . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->filemtime($internal_path);
	}

	public function filesize($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filesize of "' . $filename . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($filename)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filesize of "' . $filename . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->filesize($internal_path);
	}

	public function filetype($filename) // returns either 'file', 'dir', or 'unknown', or false on failure
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filetype of "' . $filename . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($filename)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filetype of "' . $filename . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->filetype($internal_path);
	}

	public function filecount($directory)
	{
		if (!$this->get_db_permissions($directory)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filecount of "' . $directory . '" without read permission.');
			return false;
		}

		if (!$this->is_readable($directory)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get filecount of "' . $directory . '" which is not readable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($directory));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->filecount($internal_path);
	}

	public function is_readable($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			return false;
		}

		if (!$this->file_exists($filename)) {
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->is_readable($internal_path);
	}

	public function is_writable($filename)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			return false;
		}

		if (!$this->is_readable($filename)) {
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->is_writable($internal_path);
	}

	public function touch($filename)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to create file "' . $filename . '" without write permission.');
			return false;
		}

		//Note: no readability/writability checking since this can create the file

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->touch($internal_path);
	}

	public function mkdir($pathname)
	{
		$perms = $this->get_db_permissions($pathname);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to create folder "' . $pathname . '" without write permission.');
			return false;
		}

		//Note: no readability/writability checking since this can create the directory

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($pathname));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->mkdir($internal_path);
	}

	public function rmdir($pathname)
	{
		$perms = $this->get_db_permissions($pathname);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to delete folder "' . $pathname . '" without write permission.');
			return false;
		}

		if (!$this->is_writable($pathname)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to delete folder "' . $pathname . '" which is not writable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($pathname));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->rmdir($internal_path);
	}

	public function unlink($filename)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to delete file "' . $filename . '" without write permission.');
			return false;
		}

		if (!$this->is_writable($filename)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to delete file "' . $filename . '" which is not writable.');
			return false;
		}

		$mountpoint_info = $this->get_mountpoint($this->sanitize_path($filename));
		$mountpoint = $mountpoint_info['mountpoint'];
		$internal_path = $mountpoint_info['internal_path'];

		return $mountpoint->unlink($internal_path);
	}

	public function copy($source, $destination)
	{
		$src_perms = $this->get_db_permissions($source);
		if (!$src_perms['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to copy from file "' . $source . '" to "' . $destination . '" without read permission on source.');
			return false;
		}

		if (!$this->is_readable($source)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to copy from file "' . $source . '" to "' . $destination . '" without source being readable.');
			return false;
		}

		$dest_perms = $this->get_db_permissions($destination);
		if (!$dest_perms['read'] || !$dest_perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to copy from file "' . $source . '" to "' . $destination . '" without write permission on destination.');
			return false;
		}

		$src_mountpoint_info = $this->get_mountpoint_info($this->sanitize_path($source));
		$dst_mountpoint_info = $this->get_mountpoint_info($this->sanitize_path($destination));
		if (is_null($src_mountpoint_info) || is_null($dst_mountpoint_info))
			return false;

		if ($src_mountpoint_info['id'] === $dst_mountpoint_info['id']) {
			$mountpoint_info = $this->get_mountpoint($this->sanitize_path($source));
			$mountpoint = $mountpoint_info['mountpoint'];
			$src_internal_path = $this->get_path_in_mountpoint($source);
			$dst_internal_path = $this->get_path_in_mountpoint($destination);

			return $mountpoint->copy($src_internal_path, $dst_internal_path);
		} else {
			$src_fh = $this->fopen($source, 'r');
			if (!$src_fh)
				return false;

			$dst_fh = $this->fopen($destination, 'w');
			if (!$dst_fh)
				return false;

			while (!$this->feof($src_fh)) {
				$buf = $this->fread($src_fh, 32768);
				if ($buf === false)
					return false;

				$res = $this->fwrite($dst_fh, $buf);
				if ($res === false)
					return false;
			}

			$dst_close_res = $this->fclose($dst_fh);
			$src_close_res = $this->fclose($src_fh);
			if (!$dst_close_res || !$src_close_res)
				return false;

			return true;
		}
	}

	public function move($source, $destination)
	{
		$src_perms = $this->get_db_permissions($source);
		if (!$src_perms['read'] || !$src_perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to move file "' . $source . '" to "' . $destination . '" without write permission on source.');
			return false;
		}

		if (!$this->is_writable($source)) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to move file "' . $source . '" to "' . $destination . '" without source being writable.');
			return false;
		}

		$dest_perms = $this->get_db_permissions($destination);
		if (!$dest_perms['read'] || !$dest_perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to move file "' . $source . '" to "' . $destination . '" without write permission on destination.');
			return false;
		}

		$src_mountpoint_info = $this->get_mountpoint_info($this->sanitize_path($source));
		$dst_mountpoint_info = $this->get_mountpoint_info($this->sanitize_path($destination));
		if (is_null($src_mountpoint_info) || is_null($dst_mountpoint_info))
			return false;

		if ($src_mountpoint_info['id'] === $dst_mountpoint_info['id']) {
			$mountpoint_info = $this->get_mountpoint($this->sanitize_path($source));
			$mountpoint = $mountpoint_info['mountpoint'];
			$src_internal_path = $this->get_path_in_mountpoint($source);
			$dst_internal_path = $this->get_path_in_mountpoint($destination);

			return $mountpoint->move($src_internal_path, $dst_internal_path);
		} else {
			$src_fh = $this->fopen($source, 'r');
			if (!$src_fh)
				return false;

			$dst_fh = $this->fopen($destination, 'w');
			if (!$dst_fh)
				return false;

			while (!$this->feof($src_fh)) {
				$buf = $this->fread($src_fh, 32768);
				if ($buf === false)
					return false;

				$res = $this->fwrite($dst_fh, $buf);
				if ($res === false)
					return false;
			}

			$dst_close_res = $this->fclose($dst_fh);
			$src_close_res = $this->fclose($src_fh);
			if (!$dst_close_res || !$src_close_res)
				return false;

			if (!$this->unlink($source))
				return false;

			return true;
		}
	}

	public function get_display_name($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get display name of file "' . $filename . '" without read permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$name = $this->CI->db->query('SELECT "display_name" FROM "files" WHERE "id" = ?;', [$entry_id])->result_array();
		if (isset($name[0])) {
			return $name['display_name'];
		} else {
			return basename($filename);
		}
	}

	public function set_display_name($filename, $display_name)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to set display name of file "' . $filename . '" without write permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$this->CI->db->query('UPDATE "files" SET "display_name" = ? WHERE "id" = ?;', [$display_name, $entry_id]);
		
		return true;
	}

	public function get_owner_id($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get owner of file "' . $filename . '" without read permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$owner = $this->CI->db->query('SELECT "owner_user_id" FROM "files" WHERE "id" = ?;', [$entry_id])->result_array();
		if (isset($owner[0])) {
			return (int)$owner['owner_user_id'];
		} else {
			return null;
		}
	}

	public function set_owner_id($filename, $owner_id)
	{
		if ($this->CI->authentication->get_current_user_type() !== 'admin' && $this->get_owner_id($filename) !== $this->CI->authentication->get_current_user_id()) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to set owner of file "' . $filename . '" without being owner or admin.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$this->CI->db->query('UPDATE "files" SET "owner_user_id" = ? WHERE "id" = ?;', [$owner_id, $entry_id]);
		
		return true;
	}


	/*
		Permissions are in an array for each group id
		Permission arrays contain the keys: 'read', 'write', 'share', 'expires'
	*/
	public function get_permissions($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get permissions of file "' . $filename . '" without read permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, false);
		if (is_null($entry_id)) {
			return [];
		}

		$permissions = [];
		$permissions_res = $this->CI->db->query('SELECT "group_id", "read", "write", "share", "expires" FROM "file_permissions" WHERE "file_id" = ?;', )->result_array();
		foreach ($permissions_res as $permission) {
			$permissions[] = [
				'group_id' => (int)$permission['group_id'],
				'read' => ((int)$permission['read'] == 1),
				'write' => ((int)$permission['write'] == 1),
				'share' => ((int)$permission['share'] == 1),
				'expires' => (int)$permission['expires']
			];
		}

		return $permissions;
	}

	public function get_effective_permissions($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get effective permissions of file "' . $filename . '" without read permission.');
			return false;
		}

		$file_db_entries = $this->get_parent_file_db_entry_ids($filename);

		$group_statuses = [];

		for ($i = 0; $i < count($file_db_entries); $i++) {
			$permissions_res = $this->CI->db->query(
				'SELECT "group_id", "read", "write", "share" FROM "file_permissions" WHERE "file_id" = ? AND ("expires" IS NULL OR "expires" > ?);',
				[
					$file_db_entries[$i],
					time()
				]
			)->result_array();

			foreach ($permissions_res as $perm_rec) {
				$group_id = (int)$perm_rec['group_id'];
				$read = ($perm_rec['read'] == 1);
				$write = ($perm_rec['write'] == 1);
				$share = ($perm_rec['share'] == 1);

				if (isset($group_statuses[$group_id])) {
					$read = $read && $group_statuses[$group_id]['read'];
					$write = $write && $group_statuses[$group_id]['write'];
					$share = $share && $group_statuses[$group_id]['share'];
				} else {
					$group_statuses[$group_id] = ['group_id' => $group_id, 'read' => $read, 'write' => $write, 'share' => $share];
				}
			}
		}

		return $group_statuses;
	}

	public function set_permissions($filename, $permissions)
	{
		if ($this->CI->authentication->get_current_user_type() !== 'admin' && $this->get_owner_id($filename) !== $this->CI->authentication->get_current_user_id()) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to set permissions of file "' . $filename . '" without being owner or admin.');
			return false;
		}

		$this->CI->db->trans_start();

		$entry_id = $this->get_file_db_entry_id($filename, true);
		if (!is_null($entry_id)) {
			$this->CI->db->query('DELETE FROM "file_permissions" WHERE "file_id" = ?;', [$entry_id]);
		}

		foreach ($permissions as $permission) {
			$this->CI->db->query('INSERT INTO "file_permissions" ("file_id", "group_id", "read", "write", "share", "expires") VALUES (?, ?, ?, ?, ?, ?);', [
				$entry_id,
				$permission['group_id'],
				($permission['read'] ? 1 : 0),
				($permission['write'] ? 1 : 0),
				($permission['share'] ? 1 : 0),
				$permission['exipres']
			]);
		}

		$this->CI->db->trans_complete();

		return true;
	}


	public function get_tags($filename)
	{
		if (!$this->get_db_permissions($filename)['read']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to get tags of file "' . $filename . '" without read permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$tags_res = $this->CI->db->query('SELECT "tags"."id" AS "tag_id", "tags"."name" AS "tag_name", "tags"."description" AS "tag_description" FROM "tags" INNER JOIN "tagged_files" ON "tagged_files"."tag_id" = "tags"."id" WHERE "tagged_files"."file_id" = ?;', [$entry_id])->result_array();

		$tags = [];
		foreach ($tags_res as $tag) {
			$tags[] = [
				'id' => (int)$tag['tag_id'],
				'name' => $tag['tag_name'],
				'description' => $tag['tag_description'],
			];
		}

		return $tags;
	}

	public function add_tag($filename, $tag_id)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to add tag to file "' . $filename . '" without write permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$tag_exists = (int)($this->CI->db->query('SELECT COUNT() AS "count" FROM "tagged_files" WHERE "file_id" = ? AND "tag_id" = ?;', [$entry_id, $tag_id])->result_array()[0]['count']);

		if ($tag_exists == 0) {
			$this->CI->db->query('INSERT INTO "tagged_files" ("file_id", "tag_id") VALUES ?, ?;', [$entry_id, $tag_id]);
		}

		return true;
	}

	public function remove_tag($filename, $tag_id)
	{
		$perms = $this->get_db_permissions($filename);
		if (!$perms['read'] || !$perms['write']) {
			log_message('error', 'User "' . $this->CI->authentication->get_current_username() . '" attempted to remove tag from file "' . $filename . '" without write permission.');
			return false;
		}

		$entry_id = $this->get_file_db_entry_id($filename, true);
		$this->CI->db->query('DELETE FROM "tagged_files" WHERE "file_id" = ? AND "tag_id" = ?;', [$entry_id, $tag_id]);

		return true;
	}
}



/**
 * Defines the directory handle interface
 */
interface IFilesystemDirectoryHandle
{
	public function __construct($driver);

	/**
	 * Opens a directory for reading with readdir
	 * @return IDirectoryHandle
	 */
	public function opendir($path);

	/**
	 * Closes directory
	 * Must also be destructor
	 */
	public function closedir();

	public function readdir();

	public function rewinddir();
}

/**
 * Defines the file handle interface
 */
interface IFilesystemFileHandle
{
	public function __construct($driver);

	/**
	 * Opens a file for reading and writing with fread() and fwrite() and seeking with fseek() and rewind()
	 * Mode may only be 'r', 'w', 'r+', 'w+', 'a', 'a+'
	 * @return IDirectory
	 */
	public function fopen($path, $mode);

	/**
	 * Closes file
	 * Must also be the destructor
	 */
	public function fclose();

	public function fread($length);
	public function fwrite($string, $length = null);
	public function feof();
	public function ftell();
	public function fseek($offset);
	public function rewind();
}

interface IFilesystemDriver
{
	public function __construct($driver_name, $driver_options, $mountpoint_id);
	public function mount();
	public function unmount();

	public function opendir($path);
	public function closedir($directory_handle);
	public function readdir($directory_handle);
	public function rewinddir($directory_handle);

	public function fopen($path, $mode);
	public function fclose($file_handle);
	public function fread($file_handle, $length);
	public function fwrite($file_handle, $string, $length = null);
	public function feof($file_handle);
	public function ftell($file_handle);
	public function fseek($file_handle, $offset);
	public function rewind($file_handle);

	public function disk_usage($directory); // returns associative array with keys 'free', 'used', 'total'
	public function file_exists($filename);
	public function filemtime($filename);
	public function filesize($filename);
	public function filetype($filename); // returns either 'file', 'dir', or 'unknown'
	public function filecount($directory);
	public function is_readable($filename);
	public function is_writable($filename);
	public function touch($filename);
	public function mkdir($pathname);
	public function rmdir($pathname);
	public function unlink($filename);
	public function copy($source, $destination); // only called for the same filesystem
	public function move($source, $destination); // only called for same filesystem, also rename
}
