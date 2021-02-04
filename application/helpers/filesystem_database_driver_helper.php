<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FilesystemDatabaseDirectoryHandle implements IFilesystemDirectoryHandle
{
	private $driver;
	private $CI;
	public function __construct($driver)
	{
		$this->driver = $driver;
		$this->CI = &get_instance();
	}

	public function opendir($path)
	{
	}

	public function closedir()
	{
	}

	public function readdir()
	{
	}

	public function rewinddir()
	{
	}
}

class FilesystemDatabaseFileHandle implements IFilesystemFileHandle
{
	private $driver;
	private $CI;
	public function __construct($driver)
	{
		$this->driver = $driver;
		$this->CI = &get_instance();
	}

	public function fopen($path, $mode)
	{
	}

	public function fclose()
	{
	}

	public function fread($length)
	{
	}
	public function fwrite($string, $length = null)
	{
	}
	public function feof()
	{
	}
	public function ftell()
	{
	}
	public function fseek($offset)
	{
	}
	public function rewind()
	{
	}
}

class FilesystemDatabaseDriver implements IFilesystemDriver
{
	private $CI;
	public $mountpoint_id;
	public $storage_path;
	public function __construct($driver_name, $driver_options, $mountpoint_id)
	{
		$this->CI = &get_instance();
		$this->mountpoint_id = $mountpoint_id;

		if (isset($driver_options['database'], $driver_options['database']['storage_path'])) {
			$this->fs_path = rtrim($driver_options['database']['storage_path'], '/');
		} else {
			$this->fs_path = './files';
		}
	}

	public function getFileEntryId($path, $create = false) {
		$file_id = $this->CI->filesystem->get_internal_file_entry_id($this->mountpoint_id, $path, $create);

		$result = $this->CI->db->query('SELECT "display_name", "owner_user_id", "mountpoint_driver_info" FROM "files" WHERE "id" = ?;', [$file_id])->result_array();
		if (isset($result[0])) {
			return (int)$result[0]['id'];
		} else {
			if ($create) {
				throw new Exception('File entry not created for "' . $path . '" in mountpoint ' . $this->mountpoint_id);
			}
			return false;
		}
	}

	// OLD
	public function getStoragePath($path) {
		/*$entry_id = $this->getFileEntryId($path, true);

		$file_info_result = $this->CI->db->query('SELECT "mountpoint_driver_info" FROM "files" WHERE "id" = ?;', [$entry_id])->result_array();

		if (!isset($file_info_result[0])) {
			throw new Exception('No id for file "' . $path . '" in mountpoint id ' . $this->mountpoint_id);
		}

		$file_info = json_decode($file_info_result[0]['mountpoint_driver_info'], true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new Exception('Malformed mountpoint driver info for file id ' . $entry_id);
		}

		if (!isset($file_info['database'], $file_info['database']['storage_filename'])) {
			$new_file_storage_name = hash('sha512', $this->CI->security->get_random_bytes(4096));
			$file_info['database'] = ['storage_filename' => $new_file_storage_name];
			$this->CI->db->query('UPDATE "files" SET "mountpoint_driver_info" = ? WHERE "id" = ?;', [json_encode($file_info), $file_id]);
		}

		return ($this->CI->filesystem->sanitize_path($this->storage_path) . $this->CI->filesystem->sanitize_path($file_info['database']['storage_filename']));*/
	}

	public function mount()
	{
	}
	public function unmount()
	{
	}

	public function opendir($path)
	{
	}
	public function closedir($directory_handle)
	{
	}
	public function readdir($directory_handle)
	{
	}
	public function rewinddir($directory_handle)
	{
	}

	public function fopen($path, $mode)
	{
	}
	public function fclose($file_handle)
	{
	}
	public function fread($file_handle, $length)
	{
	}
	public function fwrite($file_handle, $string, $length = null)
	{
	}
	public function feof($file_handle)
	{
	}
	public function ftell($file_handle)
	{
	}
	public function fseek($file_handle, $offset)
	{
	}
	public function rewind($file_handle)
	{
	}

	public function disk_usage($directory)
	{
	}
	public function file_exists($filename)
	{
	}
	public function filemtime($filename)
	{
	}
	public function filesize($filename)
	{
	}
	public function filetype($filename)
	{
	}
	public function filecount($directory)
	{
	}
	public function is_readable($filename)
	{
	}
	public function is_writable($filename)
	{
	}
	public function touch($filename)
	{
	}
	public function mkdir($pathname)
	{
	}
	public function rmdir($pathname)
	{
	}
	public function unlink($filename)
	{
	}
	public function copy($source, $destination)
	{
	}
	public function move($source, $destination)
	{
	}
}
