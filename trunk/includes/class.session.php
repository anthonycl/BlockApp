<?php

class Session {
	protected $boot;

	function __construct($boot) {
		$this->boot = $boot;

		session_start();
	}

	public function __set($key, $value) {
		$key = $this->boot->sanitize($key, TRUE);
		$value = $this->boot->sanitize($value);
		$_SESSION[$key] = $value;
		return TRUE;
	}

	public function __get($key) {
		$key = $this->boot->sanitize($key, TRUE);
		$value = $this->boot->unsanitize($_SESSION[$key]);
		return $key ? $value : FALSE;
	}

	public function __unset($key) {
		$key = $this->boot->sanitize($key, TRUE);
		unset($_SESSION[$key]);
		return TRUE;
	}
}