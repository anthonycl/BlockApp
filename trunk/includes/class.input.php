<?php

class Input {
	protected $boot;
	private $_vars = array();

	function __construct($boot) {
		$this->boot = $boot;

		// Process All Posts
		foreach($_POST as $key => $value) {
			$this->$key = $value;
		}
	}

	public function __set($key, $value) {
		$key = $this->boot->sanitize($key, TRUE);
		$value = $this->boot->sanitize($value);
		$this->_vars[$key] = $value;
		return TRUE;
	}

	public function __get($key) {
		$key = $this->boot->sanitize($key, TRUE);
		$value = $this->boot->unsanitize($this->_vars[$key]);
		return $key ? $value : FALSE;
	}

	public function __unset($key) {
		$key = $this->boot->sanitize($key, TRUE);
		unset($this->_vars[$key]);
		return TRUE;
	}
}