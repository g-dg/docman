<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FilesystemDummyDirectoryHandle implements IFilesystemDirectoryHandle
{
	public function __construct($driver)
	{
	}

	public function opendir($path)
	{
		return false;
	}

	public function closedir()
	{
		return false;
	}

	public function readdir()
	{
		return false;
	}

	public function rewinddir()
	{
		return false;
	}
}

class FilesystemDummyFileHandle implements IFilesystemFileHandle
{
	public function __construct($driver)
	{
	}

	public function fopen($path, $mode)
	{
		return false;
	}

	public function fclose()
	{
		return false;
	}

	public function fread($length)
	{
		return false;
	}
	public function fwrite($string, $length = null)
	{
		return false;
	}
	public function feof()
	{
		return true;
	}
	public function ftell()
	{
		return false;
	}
	public function fseek($offset)
	{
		return -1;
	}
	public function rewind()
	{
		return false;
	}
}

class FilesystemDummyDriver implements IFilesystemDriver
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
		return true;
	}
	public function unmount()
	{
		return true;
	}

	public function opendir($path)
	{
		$dh = new FilesystemDummyDirectoryHandle($this);
		return $dh->opendir($path);
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
		$fh = new FilesystemDummyFileHandle($this);
		return $fh->fopen($path, $mode);
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

	public function disk_usage($directory)
	{
		return ['free' => 0, 'used' => 0, 'total' => 0];
	}
	public function file_exists($filename)
	{
		return false;
	}
	public function filemtime($filename)
	{
		return false;
	}
	public function filesize($filename)
	{
		return false;
	}
	public function filetype($filename)
	{
		return 'unknown';
	}
	public function filecount($directory)
	{
		return false;
	}
	public function is_readable($filename)
	{
		return false;
	}
	public function is_writable($filename)
	{
		return false;
	}
	public function touch($filename)
	{
		return false;
	}
	public function mkdir($pathname)
	{
		return false;
	}
	public function rmdir($pathname)
	{
		return false;
	}
	public function unlink($filename)
	{
		return false;
	}
	public function copy($source, $destination)
	{
		return false;
	}
	public function move($source, $destination)
	{
		return false;
	}
}
