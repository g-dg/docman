<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Authentication
{
	private $CI;

	public function __construct()
	{
		$this->CI = &get_instance();
	}

	/**
	 * Run to check if logged in, redirects to log in screen if not
	 * @param target_url The URL (within the application) to redirect to after login
	 */
	public function require_login($target_url = null)
	{
		setup_session();

		if (isset($_SESSION['docman_login_id'])) {
			// check if login is valid (i.e. if the user has not logged out of all other locations)
			if ((int)($this->CI->db->query('SELECT COUNT() AS "count" FROM "logins" WHERE "id" = ?;', [$_SESSION['docman_login_id']])->row_array()['count']) == 1) {
				// update "last_used" of login table
				$time = time();
				// we don't want to lock the database for writing every second for each login
				$this->CI->db->query('UPDATE "logins" SET "last_used" = ? WHERE "id" = ? AND "last_used" != ?;', [$time, $_SESSION['docman_login_id'], $time]);
				return true;
			}
		}

		//$_SESSION['docman_login_redirect'] = is_null($target_url) ? $_SERVER['REQUEST_URI'] : rtrim($this->config->site_url(), '/') . $target_url;
		$this->CI->load->helper('url');
		redirect(html_escape(rtrim($this->CI->config->site_url(), '/')) . '/login/', 'location', 303);
		return false;
	}

	/**
	 * Authenticates and logs in a user
	 * @param username the username to log in as
	 * @param password the password of the user
	 * @return bool whether login succeeded or not
	 */
	public function login($username, $password)
	{
		setup_session();
		$auth_result = $this->authenticate($username, $password);

		// check if successful
		if (!is_null($auth_result)) {
			$time = time();
			// create login entry
			$this->CI->db->query('INSERT INTO "logins" ("user_id", "client_addr", "user_agent", "login_time", "last_used") VALUES (?, ?, ?, ?, ?);', [
				$auth_result,
				isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
				isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
				$time,
				$time
			]);
			// set session
			$_SESSION['docman_login_id'] = $this->CI->db->insert_id();
			log_message('info', 'User "' . $username . '" logged in.');
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
						log_message('info', 'Rehashed password for "' . $username . '".');
					}
					return (int)$user['id'];
				} else {
					log_message('info', 'Attempted to log in as "' . $username . '" with incorrect password.');
					return null;
				}
			}
		}
		log_message('info', 'Attempted to log in as non-existent user "' . $username . '".');
		return null;
	}

	/**
	 * Gets the currently logged in user id
	 * @return int currently logged in user id, null if not logged in
	 */
	public function get_current_user_id()
	{
		setup_session();

		if (isset($_SESSION['docman_login_id'])) {
			$logins = $this->CI->db->query('SELECT "user_id" FROM "logins" WHERE "id" = ?;', [$_SESSION['docman_login_id']])->result_array();
			if (isset($logins[0])) {
				return (int)$logins[0]['user_id'];
			} else {
				log_message('error', 'Attempted to get user id of non-existent login id #' . $_SESSION['docman_login_id'] . '. (Login is probably no longer valid.)');
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Gets the currently logged in username
	 */
	public function get_current_username()
	{
		setup_session();

		if (isset($_SESSION['docman_login_id'])) {
			$logins = $this->CI->db->query('SELECT "users"."id" AS "user_id", "users"."username" AS "username" FROM "users" INNER JOIN "logins" ON "logins"."user_id" = "users"."id" WHERE "logins".id" = ?;', [$_SESSION['docman_login_id']])->result_array();
			if (isset($logins[0])) {
				return $logins[0]['username'];
			} else {
				log_message('error', 'Attempted to get username from non-existent login id #' . $_SESSION['docman_login_id'] . '. (Login is probably no longer valid.)');
				return null;
			}
		} else {
			return null;
		}
	}

	public function get_current_user_type()
	{
		setup_session();

		if (isset($_SESSION['docman_login_id'])) {
			$logins = $this->CI->db->query('SELECT "users"."id" AS "user_id", "users"."type" AS "user_type" FROM "users" INNER JOIN "logins" ON "logins"."user_id" = "users"."id" WHERE "logins".id" = ?;', [$_SESSION['docman_login_id']])->result_array();
			if (isset($logins[0])) {
				switch ((int)$logins[0]['user_type']) {
					case 0:
						return 'admin';
					case 1:
						return 'standard';
					case 2:
						return 'guest';
					default:
						log_message('error', 'Invalid user type: "' . $logins[0]['user_type'] . '" for user id "' . $logins[0]['user_id'] . '".');
						break;
				}
			} else {
				log_message('error', 'Attempted to get user type from non-existent login id #' . $_SESSION['docman_login_id'] . '. (Login is probably no longer valid.)');
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Gets the groups that a user is in.
	 */
	public function get_current_user_groups()
	{
		$user_id = $this->get_current_user_id();
		if (is_null($user_id)) {
			log_message('error', 'Attempted to get groups of current user while not logged in.');
			return null;
		}

		$groups_res = $this->CI->db->query('SELECT "group_id" FROM "users_in_groups" WHERE "user_id" = ?;', [$user_id]);

		$groups = [];

		foreach ($groups_res as $group) {
			$groups[] = (int)$group['group_id'];
		}

		return $groups;
	}
}
