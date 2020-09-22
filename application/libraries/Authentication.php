<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication {

	private $CI;

	const AUTH_SUCCESS = 1;
	const AUTH_BAD_USERNAME = 2;
	const AUTH_BAD_PASSWORD = 4;
	const AUTH_NEED_PASSWORD_CHANGE = 8;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * 
	 */
	public function authenticate($username, $password)
	{

	}

}
