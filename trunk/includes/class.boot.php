<?php

class Boot {
	private $_vars = array();
	protected $controller;
	protected $action;
	protected $arguments;

	function __construct() {
		// Load the config
		require_once('config.php');
		$this->config = $config;
		
		// Load the DB Class
		$dbDrvier = 'db' . ucfirst($config->dbDriver);
		require_once("class.{$dbDrvier}.php");
		$this->db = new $dbDrvier($this);

		// Load the Session Class
		require_once('class.session.php');
		$this->session = new Session($this);

		// Load the Input Class
		require_once('class.input.php');
		$this->input = new Input($this);

		// Start Auth Class
		$this->auth = new stdClass();
	
		// Build Blocks
		$this->blocks = $this->buildBlocks();
		
		// Logged In
		$this->loggedIn = (int)$this->session->loggedIn;
		
		// Views
		$this->views = array();
		$this->isRendered = FALSE;

		$this->initiateRoute();
		$this->render();
	}

	public function __get($key) {
		return $this->_vars[$key];
	}
	
	public function __set($key, $value) {
		if(is_array($this->_vars[$key])) {
			$this->_vars[$key] = array_merge($this->_vars[$key], array($value));
		} else {
			$this->_vars[$key] = $value;
		}
	}

	public function formValue($key) {
		$controller = $this->controller . ucwords($key);

		return $this->input->$key ? $this->input->$key : $this->session->$controller;
	}

	public function sanitize($data, $superClean = FALSE) {
		if(!is_object($data)) {
			if(is_array($data)) {
				foreach ($data as $key => $value) {
					$data[$key] = $this->sanitize($value, $superClean);
				}
			} else {
				if($superClean) $data = preg_replace("/[^a-zA-Z0-9-_\s\.\/]/", "", $data);
				$data = htmlentities($data, ENT_QUOTES, 'UTF-8');
				$data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
			}
		}

		return $data;
	}

	public function unsanitize($data) {
		if(!is_object($data)) {
			if(is_array($data)) {
				foreach ($data as $key => $value) {
					$data[$key] = $this->unsanitize($value);
				}
			} else {
				$data = html_entity_decode($data);
				$data = mb_convert_encoding($data, 'UTF-8', 'HTML-ENTITIES');
			}
		}

		return $data;
	}
	
	public function url($URL = '/') {
		$parsedURL = (object)parse_url($URL);
	
		if($parsedURL->scheme) {
			return $URL;
		} elseif($parsedURL->path) {
			return substr($URL, 1) != '/' ? '/' . $URL : $URL;
		}
		
		return '/';
	}

	public function redirect($URL = FALSE) {
		$URL = $this->url($URL);

		if($URL) {
			header("Location: $URL");
			exit();
		}
	}

	public function buildBlocks($array = FALSE) {
		$array = (object)$array;
		
		$block = $array->block ? $array->block : $this->controller;
		$idKey = $array->idKey ? $array->idKey : 'id';
		$idValue = $array->idValue ? $array->idValue : $this->arguments[0];
		$response = is_array($array->response) ? $array->response : FALSE;
		$return = $array->return ? $array->return : TRUE;
		
		// Check for Provided Response (If Not Try Pull From DB)
		if(!$response) {
			$query = array($idKey => $idValue);
			$response = $this->db->findOne($block, $query)->result;
		}
		
		// Check Response = Array
		if(is_array($response)) {
			$blocks = new stdClass();

			foreach($response as $key => $value) {
				$name = $block . ucfirst($key);

				if(is_array($value)) {
					$array = array(
						'block' => $block,
						'idKey' => $idKey,
						'idValue' => $idValue,
						'response' => $value,
						'return' => FALSE
					);
					
					$blocks->$key = $this->buildBlocks($array);
				} else {
					// Set Session for Block
					$this->session->$name = $value;
					
					// Build Block Object
					$blocks->$key = $this->session->$name;
				}
			}

			if($return) {
				$this->blocks = $blocks;
				return TRUE;
			} else {
				return $blocks;
			}
		}
		
		return FALSE;
	}

	public function sendRequest($URL, $type = 'get', $fields = FALSE, $responseType = 'json') {
		// Open connection
		$ch = curl_init();

		// Set defaults
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch,CURLOPT_URL, $URL);
		
		if($type == 'post') {
			// Check $fields is array
			if(!is_array($fields)) return FALSE;
			
			// Process $fields array
			foreach($fields as $key=>$value) { $fieldsString .= $key.'='.$value.'&'; }
			rtrim($fieldsString,'&');
		
			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fieldsString);
		}

		// Execute curl
		$response = curl_exec($ch);

		// Close connection
		curl_close($ch);
		
		switch($responseType) {
			default:
			case 'json':
				$response = json_decode($response);
				if(!$response->code) $response->code = 200;
			break;
		}
		
		return $response;
	}

	public function flattenArray($array) {
		foreach($array as $row) {
			if(is_array($row)) {
				$newArray = array_merge($newArray, $this->flattenArray($row));
			} else {
				$newArray[] = $row;
			}
		}
		
		return $newArray;
	}

	public function sortArrayByKey($array, $subKey){
	    foreach($array as $key => $val){
	        $temp[$key] = $val->$subKey;
	    }
	
	    arsort($temp);
	
	    foreach($temp as $key => $val){
	        $out[$key] = $array[$key];
	    }
	
		return $out;
	}

	public function validate($value, $type = 'text') {
		$valid = new stdClass;
		
		switch($type) {
			case 'username':
				$valid->match = preg_match("/^[a-zA-Z0-9_-]{3,16}$/", $value);
				$valid->format = 'can contain only letters, numbers, dashes, underscores and must be at least 3 but no longer then 16 characters.';
			break;
			
			case 'password':
				$valid->match = preg_match("/^[a-zA-Z0-9_-\W]{6,18}$/", $value);
				$valid->format = 'can contain only letters, numbers, regular symbols, punctuation and must be at least 6 but no longer then 18 characters.';
			break;
			
			case 'hex':
				$valid->match = preg_match("/^#?([a-f0-9]{6}|[a-f0-9]{3})$/", $value);
				$valid->format = 'must be a color hexadecimal code.';
			break;
			
			case 'slug':
				$valid->match = preg_match("/^[a-zA-Z0-9-]+$/", $value);
				$valid->format = 'can contain only letters, numbers and dashes.';
			break;

			case 'domain':
				$valid->match = preg_match("/^([\da-zA-Z\.-]+)\.([a-zA-Z\.]{2,6})([\/\w \.-]*)*\/?$/", $value);
				$valid->format = 'must be valid.';
			break;

			case 'email':
				$valid->match = preg_match("/^([a-zA-Z0-9_\.-]+)@([\da-zA-Z\.-]+)\.([a-zA-Z\.]{2,6})$/", $value);
				$valid->format = 'must be valid.';
			break;
			
			case 'url':
				$valid->match = preg_match("/^(https?:\/\/)?([\da-zA-Z\.-]+)\.([a-zA-Z\.]{2,6})([\/\w \.-]*)*\/?$/", $value);
				$valid->format = 'must be valid.';
			break;
			
			case 'ip':
				$valid->match = preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $value);
				$valid->format = 'must be valid.';
			break;
			
			case 'htmltag':
				$valid->match = preg_match("/^<([a-zA-Z]+)([^<]+)*(?:>(.*)<\/\1>|\s+\/>)$/", $value);
				$valid->format = 'must be valid.';
			break;

			case 'name':
				$valid->match = preg_match("/^[a-zA-Z\s]{5,50}$/", $value);
				$valid->format = 'can contain only letters, spaces and must be at least 5 but no longer then 50 characters.';
			break;

			default:
			case 'text':
				$valid->match = preg_match("/^[a-zA-Z0-9_-\s\W]$/", $value);
				$valid->format = 'can contain only letters, numbers, spaces, regular symbols and punctuation.';
			break;
		}
		
		return $valid;
	}

	public function logError($error) {
        $add = array(
        	'error' => $this->sanitize($error),
        	'controller' => $this->controller,
        	'function' => $this->action,
        	'input' => $this->input,
        	'user' => $this->loggedIn ? $this->user->id : FALSE,
        	'created' => time()
        );
        
        $result = $this->db->add('errors', $add);
		return TRUE;
	}

	public function setFlash($array) {	
		if(is_array($array)) {
			$flashes = $this->session->flash;
			
			$flash = new stdClass();
			$flash->message = $array['message'];
			$flash->type = $array['type'] ? $array['type'] : 'notice';
			$flash->title = $array['title'] ? $array['title'] : ucwords($this->action . ' ' . $type);
			$flash->sticky = $array['sticky'] ? $array['sticky'] : 'false';

			if(is_array($flashes)) {
				$this->session->flash = array_merge($flashes, array($flash));
			} else {
				$this->session->flash = array($flash);
			}
		}
		
		return TRUE;
	}

	public function getFlash() {
		if(is_array($this->session->flash)) {
			$flash = $this->session->flash;
			
			// Erase flash vars
			unset($this->session->flash);
			
			return $flash;
		}

		return FALSE;
	}

	function prettyDate($date) {
		$periods = array(
			array('decade',  (3600 * 24 * 365 * 10)),
			array('year',  (3600 * 24 * 365)),
			array('month', (3600 * 24 * 30)),
			array('week',  (3600 * 24 * 7)),
			array('day',   (3600 * 24)),
			array('hour',  (3600)),
			array('min',   (60)),
			array('sec',   (1))
		);

		#Get the time from the function arg and the time now
		$argtime = strtotime($date);
		$nowtime = time();

		#Get the time diff in seconds
		$diff    = $nowtime - $argtime;

		#Store the results of the calculations
		$res = array ();

		#Calculate the largest unit of time
		for ($i = 0; $i < count($periods); $i++) {
			$title = $periods[$i][0];
			$calc  = $periods[$i][1];
			$units = floor($diff / $calc);
			if ($units > 0) {
				$res[$title] = $units;
			}
		}

		if (isset($res['year']) && $res['year'] > 0) {
			if (isset($res['month']) && $res['month'] > 0 && $res['month'] < 12) {
				$format      = "About %s %s %s %s ago";
				$year_label  = $res['year'] > 1 ? 'years' : 'year';
				$month_label = $res['month'] > 1 ? 'months' : 'month';
				return sprintf($format, $res['year'], $year_label, $res['month'], $month_label);
			} else {
				$format     = "About %s %s ago";
				$year_label = $res['year'] > 1 ? 'years' : 'year';
				return sprintf($format, $res['year'], $year_label);
			}
		}

		if (isset($res['month']) && $res['month'] > 0) {
			if (isset($res['day']) && $res['day'] > 0 && $res['day'] < 31) {
				$format      = "About %s %s %s %s ago";
				$month_label = $res['month'] > 1 ? 'months' : 'month';
				$day_label   = $res['day'] > 1 ? 'days' : 'day';
				return sprintf($format, $res['month'], $month_label, $res['day'], $day_label);
			} else {
				$format      = "About %s %s ago";
				$month_label = $res['month'] > 1 ? 'months' : 'month';
				return sprintf($format, $res['month'], $month_label);
			}
		}

		if (isset($res['day']) && $res['day'] > 0) {
			if ($res['day'] == 1) {
				return sprintf("Yesterday at %s", date('h:i a', $argtime));
			}
			if ($res['day'] <= 7) {
				return date("\L\a\s\\t l \a\\t h:i a", $argtime);
			}
			if ($res['day'] <= 31) {
				return date("l \a\\t h:i a", $argtime);
			}
		}

		if (isset($res['hour']) && $res['hour'] > 0) {
			if ($res['hour'] > 1) {
				return sprintf("About %s hours ago", $res['hour']);
			} else {
				return "About an hour ago";
			}
		}

		if (isset($res['min']) && $res['min']) {
			if ($res['min'] == 1) {
				return "About one minut ago";
			} else {
				return sprintf("About %s minuts ago", $res['min']);
			}
		}

		if (isset ($res['sec']) && $res['sec'] > 0) {
			if ($res['sec'] == 1) {
				return "One second ago";
			} else {
				return sprintf("%s seconds ago", $res['sec']);
			}
		}
	}

	public function getStringOffset($search, $string, $offset = 0) {
	    $arr = explode($search, $string);

	    switch($offset) {
	        case $offset == 0:
	        	return false;
	        break;
	    
	        case $offset > max(array_keys($arr)):
	        	return false;
	        break;
	
	        default:
	        	return strlen(implode($search, array_slice($arr, 0, $offset)));
	        break;
	    }
	}

	public function addView($view) {
		if(strstr($view, '/')) $splitView = explode('/', $view, 2);

		if(file_exists("../blocks/partials/view.$view.php")) {
			$view = "../blocks/partials/view.$view.php";
		} elseif(file_exists("../blocks/{$this->controller}/view.$view.php")) {
			$view = "../blocks/{$this->controller}/view.$view.php";
		} elseif(file_exists("../blocks/{$splitView[0]}/view.{$splitView[1]}.php")) {
			$view = "../blocks/{$splitView[0]}/view.{$splitView[1]}.php";
		} elseif(file_exists($view)) {
			$view = $view;
		} else {
			return FALSE;
		}
		
		if($this->isRendered) {
			require_once($view);
		} else {
			$this->views = $view;
		}
		
		return TRUE;
	}

	public function renderView() {
		require_once($this->view);

		foreach($this->views as $view) {
			require_once($view);
		}
	}

	private function render() {
		ob_start();
		
		// Load the controller
		$checkFile = "../blocks/{$this->controller}/controller.php";
		if(file_exists($checkFile)) {
			require_once($checkFile);
		
			$controller = $this->controller;
			$function = $this->action;
			$block = new $controller($this);


			// Load Method 
			if(method_exists($block, $function)) {
				call_user_func_array(array($block, $function), $this->arguments);
			} else {
				$this->redirect('page/view/exist');
			}

			$this->isRendered = TRUE;

			// Set View
			if(file_exists("../blocks/{$this->controller}/view.{$this->view}.php")) {
				$this->view = "../blocks/{$this->controller}/view.{$this->view}.php";
			} elseif(file_exists("../blocks/{$this->controller}/view.{$this->action}.php")) {
				$this->view = "../blocks/{$this->controller}/view.{$this->action}.php";
			} elseif(file_exists($this->view)) {
				$this->view = $this->view;
			}

			// Render Layout
			if(file_exists("../blocks/layouts/layout.{$this->layout}.php")) {
				require_once("../blocks/layouts/layout.{$this->layout}.php");
			} elseif(file_exists("../blocks/layouts/layout.{$this->config->defaultLayout}.php")) {
				require_once("../blocks/layouts/layout.{$this->config->defaultLayout}.php");
			} else {
				$this->redirect('page/exist');
			}
		} else {
			// Block Not Found
			$this->redirect('page/view/exist');
		}
		
		ob_end_flush();
	}

	private function initiateRoute() {
		$requestURI = strpos($_SERVER['REQUEST_URI'], '?') === FALSE ? $_SERVER['REQUEST_URI'] : strstr($_SERVER['REQUEST_URI'], '?', true);
        $requestURI = explode('/', $requestURI);
        $scriptName = explode('/',$_SERVER['SCRIPT_NAME']);
        $commandArray = array_diff_assoc($requestURI,$scriptName);
        $commandArray = array_values($commandArray);

		// Set controller
		$this->controller = $this->config->defaultController;

		// Check for access
        if(!$this->loggedIn && in_array($commandArray[0], $this->config->allowedControllers) || !$commandArray[0] || $this->loggedIn) {
        	if($commandArray[0]) $this->controller = $this->sanitize($commandArray[0], TRUE);
        } else {
        	$this->setFlash('You must be logged in to do that!', "warning");
        	$this->redirect();
        }
        
        // Set function
        $this->action = $commandArray[1] ?  $this->sanitize($commandArray[1], TRUE) : 'index';

        // Set layout
        $this->layout = $this->loggedIn ? $this->config->defaultLoggedInLayout : $this->config->defaultLayout;
        
        $parameters = array_slice($commandArray, 2);
		$arguments = array();

        foreach ($parameters as $input) {
        	$arguments[] = $this->sanitize($input);
        }
        
        $this->arguments = $arguments;
	}
}