<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication {

	private $CI;

	const AUTH_SUCCESS = 1;
	const AUTH_BAD_USERNAME = 2;
	const AUTH_BAD_PASSWORD = 4;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Authenticates and logs in a user
	 */
	public function login($username, $password)
	{
		$this->CI->load->library('session');
		$auth_result = $this->authenticate($username, $password);
	}

	/**
	 * Ensures a user's credentials are valid
	 * @param username The username to check
	 * @param password The password to check
	 * @return int Bitmask describing authentication result
	 */
	public function authenticate($username, $password)
	{
		// get user from database
		$query = $this->CI->db->query('SELECT "id", "username", "password", "type", "full_name", "last_password_change", "settings" FROM "users" WHERE "username" = ?;', [$username]);
		foreach ($query->result_array() as $user) {
			// check username
			if ($user['username'] == $username) {
				// check password
				if (password_verify($password, $user['password'])) {
					// check whether password needs rehashing
					if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
						// rehash and store
						$this->CI->db->query('UPDATE "users" SET "password" = ? WHERE "id" = ?;', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
					}
					return self::AUTH_SUCCESS;
				} else {
					return self::AUTH_BAD_PASSWORD;
				}
			}
		}
		return self::AUTH_BAD_USERNAME;
	}
}
