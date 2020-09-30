<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication {

	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Authenticates and logs in a user
	 * @param username the username to log in as
	 * @param password the password of the user
	 * @return bool whether login succeeded or not
	 */
	public function login($username, $password)
	{
		$this->CI->load->library('session');
		$auth_result = $this->authenticate($username, $password);

		// check if successful
		if (!is_null($auth_result)) {
			// create login entry
			$this->CI->db->query('INSERT INTO "logins" ("user_id", "client_addr", "user_agent", "login_time", "last_used") VALUES (?, ?, ?, ?, ?);', [
				$auth_result,
				isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
				isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
				time(),
				time()
			]);
			// set session
			$_SESSION['docman_login_id'] = $this->CI->db->insert_id();
			return true;
		}
		return false;
	}

	/**
	 * Ensures a user's credentials are valid
	 * @param username The username to check
	 * @param password The password to check
	 * @return int User id, null on failure
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
					return (int)$user['id'];
				} else {
					return null;
				}
			}
		}
		return null;
	}
}
