<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FilesystemServerFSDirectoryHandle implements IFilesystemDirectoryHandle
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

class FilesystemServerFSFileHandle implements IFilesystemFileHandle
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

class FilesystemServerFSDriver implements IFilesystemDriver
{
	private $CI;
	public $mountpoint_id;
	public function __construct($driver_name, $driver_options, $mountpoint_id)
	{
		$this->CI = &get_instance();
		$this->mountpoint_id = $mountpoint_id;
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

	public function get_permissions($filename, $group_ids = null)
	{
	}
	public function set_permissions($filename, $permissions)
	{
	}
}
