<?php

class dbMongo {
	protected $boot;
	protected $db;

	function __construct($boot) {
		$this->boot = $boot;

		$m = new Mongo("mongodb://{$boot->config->dbUser}:{$boot->config->dbPass}@{$boot->config->dbHost}/{$boot->config->db}");
		$this->db = $m->selectDB($boot->config->db);
	}

	public function findOne($collection, $query, $options = FALSE) {
		$collection = new MongoCollection($this->db, $collection);
		if(is_array($options->return)) {
			$result = $collection->findOne($this->checkQuery($query), $options->return);
		} else {
			$result = $collection->findOne($this->checkQuery($query));
		}
		return (object)array('result' => $this->checkResult($result), 'query' => (object)$query, 'options' => (object)$options);
	}

	public function count($collection, $query) {
		$collection = new MongoCollection($this->db, $collection);
		$result = $collection->count($this->checkQuery($query));
		return (object)array('result' => $this->checkResult($result)->scalar, 'query' => (object)$query);
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
	
	public function add($collection, $query) {
		$collection = new MongoCollection($this->db, $collection);
		$result = $collection->insert($this->checkQuery($query));
		return (object)array('result' => $this->checkResult($result)->scalar, 'query' => (object)$query);
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
		return new MongoRegex($regex);
	}

	public function id($id) {
		return new MongoID($this->regex("/{$id}/i"));
	}

	public function date($date) {
		return new MongoDate($date);
	}

	public function checkQuery($query) {
		foreach($query as $key => $value) {
			switch($key) {
				case 'id':
					$query['_id'] = $this->getID($value);
					unset($query['id']);
				break;

				case 'created':
				case 'updated':
					$query[$key] = $this->date(is_string($value) ? strtotime($value) : $value);
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
					case '_id':
						$result['id'] = $value->{'$id'};
					break;
					
					case 'updated':
					case 'created':
						$result[$key] = $value->{'sec'};
					break;
	
					default:
					break;
				}
				
				if(is_array($value)) (object)$query[$key] = $this->checkResult($value);
			}
		}

		return (object)$result;
	}
}