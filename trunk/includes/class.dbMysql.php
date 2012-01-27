<?php

class dbMysql {
	protected $boot;
	protected $db;

	function __construct($boot) {
		$this->boot = $boot;

		$m = mysql_connect($boot->config->dbHost, $boot->config->dbUser, $boot->config->dbPass);
		$this->db = mysql_select_db($boot->config->db, $m);
	}

	public function findOne($table, $query, $options = FALSE) {
		$where = $this->toString($query);

		$query = mysql_query("SELECT * FROM `{$table}` WHERE {$where} LIMIT 1");
		if($query) $result = mysql_fetch_object($query);

		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'options' => (object)$options);
	}

	public function count($table, $query) {
		$where = $this->toString($query);

		$query = mysql_query("SELECT * FROM `{$table}` WHERE {$where}");
		if($query) $result = mysql_num_rows($query);

		return (object)array('result' => $result, 'query' => (object)$query, 'options' => (object)$options);
	}

	public function findAll($collection, $query, $options = FALSE) {
		$collection = new MongoCollection($this->db, $collection);
		if(is_array($options->return)) {
			$result = $collection->find($this->checkQuery($query), $options->return);
		} else {
			$result = $collection->find($this->checkQuery($query));
		}
		
		if(is_array($options->sort)) $result->sort($options->sort);
		if(is_int($options->limit)) $result->limit($options->limit);
		if(is_int($options->skip)) $result->skip($options->skip);
		
		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'options' => (object)$options);
	}

	public function update($collection, $query, $update, $options = FALSE) {
		$collection = new MongoCollection($this->db, $collection);
		if(is_array($options->options)) {
			$result = $collection->update($this->checkQuery($query), $this->checkQuery($update), $options->options);
		} else {
			$result = $collection->update($this->checkQuery($query), $this->checkQuery($update));
		}
		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'update' => (object)$update, 'options' => (object)$options);
	}
	
	public function add($table, $query) {
		$query = $this->checkQuery($query);
	
		$keys = $this->toString($query, "`,`", "[K]");
		$values = $this->toString($query, "','", "[V]");

		$query = mysql_query("INSERT INTO `{$table}` (`{$keys}`) VALUES ('{$values}')");
		if($query) $result = mysql_num_rows($query);

		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'options' => (object)$options);
	}

	public function remove($collection, $query, $options = FALSE) {
		$collection = new MongoCollection($this->db, $collection);
		if(is_array($options->options)) {
			$result = $collection->remove($this->checkQuery($query), $options->options);
		} else {
			$result = $collection->remove($this->checkQuery($query));
		}
		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'options' => (object)$options);
	}

	public function regex($regex) {
		return $regex;
	}

	public function id($id) {
		return $id;
	}

	public function date($date) {
		return strtotime($date);
	}

	public function checkQuery($query) {
		foreach($query as $key => $value) {
			switch($key) {
				case 'id':
				break;

				case 'created':
				case 'updated':
					$query[$key] = date('Y-m-d H:i:s', $this->date($value));
				break;

			}

			if(is_array($value)) $query[$key] = $this->checkQuery($value);
		}

		return $query;
	}

	public function checkResult($result) {
		if(is_array($result)) {
			foreach($result as $key => $value) {
				switch($key) {
					case 'id':
					break;
					
					case 'updated':
					case 'created':
						$result[$key] = $this->date($value);
					break;
	
					default:
					break;
				}
				
				if(is_array($value)) (object)$query[$key] = $this->checkResult($value);
			}
		}

		return (object)$result;
	}

	public function toString($array, $seperator = " AND ", $format = "`[K]` = '[V]'", $formatCompare = "`[K]` [C] '[V]'") {
		if(is_array($array)) {
			$newArray = array();

			foreach($array as $key => $value) {
				list($key, $compare) = explode(' ', $key);

				if($compare) {
					$format = $formatCompare;
				}

				$string = str_replace('[C]', $key, $format);
				$string = str_replace('[K]', $key, $string);
				$string = str_replace('[V]', $value, $string);

				$newArray[] = $string;
			}
			
			$string = join($seperator, $newArray);
			return $string;
		}
	}
}