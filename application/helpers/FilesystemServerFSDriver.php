<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Driver technical name: "server_fs"

Driver settings:
{
	"server_fs": {
		"path": "<path to folder on root fs>"
	}
}
*/

class FilesystemServerFSDirectoryHandle implements IFilesystemDirectoryHandle
{
	private $driver;
	private $CI;

	private $dh;

	public function __construct($driver)
	{
		$this->driver = $driver;
		$this->CI = &get_instance();
	}

	public function opendir($path)
	{
		$this->dh = opendir($this->driver->get_fs_path($path));
		if ($this->dh !== false) {
			return $this;
		} else {
			return false;
		}
	}

	public function closedir()
	{
		return closedir($this->dh);
	}

	public function readdir()
	{
		return readdir($this->dh);
	}
	public function rewinddir()
	{
		return rewinddir($this->dh);
	}
}

class FilesystemServerFSFileHandle implements IFilesystemFileHandle
{
	private $driver;
	private $CI;

	private $fh;

	public function __construct($driver)
	{
		$this->driver = $driver;
		$this->CI = &get_instance();
	}

	public function fopen($path, $mode)
	{
		$this->fh = fopen($this->driver->get_fs_path($path), $mode);
		if ($this->dh !== false) {
			return $this;
		} else {
			return false;
		}
	}

	public function fclose()
	{
		return fclose($this->fh);
	}

	public function fread($length)
	{
		return fread($this->fh, $length);
	}
	public function fwrite($string, $length = null)
	{
		return fwrite($this->fh, $string, $length);
	}
	public function feof()
	{
		return feof($this->fh);
	}
	public function ftell()
	{
		return ftell($this->fh);
	}
	public function fseek($offset)
	{
		return fseek($this->fh, $offset);
	}
	public function rewind()
	{
		return rewind($this->fh);
	}
}

class FilesystemServerFSDriver implements IFilesystemDriver
{
	private $CI;
	public $mountpoint_id;
	public $fs_path;
	public function __construct($driver_name, $driver_options, $mountpoint_id)
	{
		$this->CI = &get_instance();
		$this->mountpoint_id = $mountpoint_id;
		if (isset($driver_options['server_fs'], $driver_options['server_fs']['path'])) {
			$this->fs_path = rtrim($driver_options['server_fs']['path'], '/');
		} else {
			$this->fs_path = '.';
		}
	}

	public function get_fs_path($path) {
		return '/' . trim(rtrim($this->fs_path, '/') . '/' . ltrim($this->CI->filesystem->sanitize_path($path)), '/');
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
		$dh = new FilesystemServerFSDirectoryHandle($this);
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
		$fh = new FilesystemServerFSFileHandle($this);
		return $fh->fopen($path, $mode);
	}
	public function fclose($file_handle)
	{
		return $file_handle->fclose($file_handle);
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
		$path = $this->get_fs_path($directory);
		return [
			'total' => disk_total_space($path),
			'used' => disk_total_space($path) - disk_free_space($path),
			'free' => disk_free_space($path)
		];
	}
	public function file_exists($filename)
	{
		return file_exists($this->get_fs_path($filename));
	}
	public function filemtime($filename)
	{
		return filemtime($this->get_fs_path($filename));
	}
	public function filesize($filename)
	{
		return filesize($this->get_fs_path($filename));
	}
	public function filetype($filename)
	{
		switch ($this->get_fs_path($filename)) {
			case 'file':
				return 'file';
			case 'dir':
				return 'dir';
			default:
				return 'unknown';
		}
	}
	public function filecount($directory)
	{
		$count = 0;
		$dh = $this->opendir($directory);

		if ($dh === false) {
			return false;
		}

		while (($name = $this->readdir($dh)) !== false) {
			if ($name !== '.' && $name !== '..') {
				$count++;
			}
		}
		$this->closedir($dh);

		return $count;
	}
	public function is_readable($filename)
	{
		return is_readable($this->get_fs_path($filename));
	}
	public function is_writable($filename)
	{
		return is_writable($this->get_fs_path($filename));
	}
	public function touch($filename)
	{
		return touch($this->get_fs_path($filename));
	}
	public function mkdir($pathname)
	{
		return mkdir($this->get_fs_path($pathname));
	}
	public function rmdir($pathname)
	{
		return rmdir($this->get_fs_path($pathname));
	}
	public function unlink($filename)
	{
		return unlink($this->get_fs_path($filename));
	}
	public function copy($source, $destination)
	{
		return copy($this->get_fs_path($source), $this->get_fs_path($destination));
	}
	public function move($source, $destination)
	{
		return rename($this->get_fs_path($source), $this->get_fs_path($destination));
	}
}
