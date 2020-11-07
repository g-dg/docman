<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings
{
	private $CI;

	public $settings = [];

	public $save_in_session = false;
	public $autosave = true;

	private $loaded = false;

	public function __construct()
	{
		$this->CI = &get_instance();
	}

	public function load()
	{
		setup_session();

		$this->CI->load->library('authentication');

		$user_id = $this->CI->authentication->get_current_user_id();
		if (is_null($user_id)) {
			$this->save_in_session = true;
		}

		if ($this->CI->authentication->get_current_user_type() === 'guest') {
			$this->save_in_session = true;
		}

		if (isset($user_id)) {
			// load settings from database
			$settings_res = $this->CI->db->query('SELECT "settings" FROM "users" WHERE "id" = ?;', [])->result_array();

			if (isset($settings_res[0])) {
				$this->settings = json_decode($settings_res[0]['settings'], true);
				if (json_last_error() != JSON_ERROR_NONE) {
					$this->settings = [];
				}
			}
		} else {
			$this->settings = [];
		}

		// merge any settings from session
		if (isset($_SESSION['settings'])) {
			$session_settings = $_SESSION['settings'];

			foreach ($session_settings as $key => $value) {
				$this->settings[$key] = $value;
			}
		}

		$this->loaded = true;
	}

	public function save()
	{
		if (!$this->loaded) {
			$this->load();
		}

		if ($this->save_in_session) {
			$_SESSION['settings'] = $this->settings;
		} else {
			$this->CI->db->query('UPDATE "users" SET "settings" = ? WHERE "id" = ?;', [
				json_encode($this->settings),
				$this->CI->authentication->get_current_user_id()
			]);
		}
	}

	public function get($key, $default = null)
	{
		$this->load();

		if ($this->isset($key)) {
			return $this->settings[$key];
		} else {
			return $default;
		}
	}

	public function set($key, $value)
	{
		$this->load();

		$this->settings[$key] = $value;

		if ($this->autosave) {
			$this->save();
		}
	}

	public function isset($key)
	{
		$this->load();

		return isset($this->settings[$key]);
	}

	public function unset($key)
	{
		$this->load();

		unset($this->settings[$key]);

		if ($this->autosave) {
			$this->save();
		}
	}
}
