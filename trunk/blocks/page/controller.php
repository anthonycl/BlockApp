<?php

class Page {
	protected $boot;

	function __construct($boot) {
		$this->boot = $boot;
	}

	public function index() {
		$this->view('home');
		$this->boot->view = 'view';
	}

	public function view($slug) {
		$query = array('slug' => $this->boot->sanitize($slug, TRUE));
		$page = $this->boot->db->findOne('page', $query);
		
		if(!$page->result->id) $this->redirect('page/view/exist');
		if(!$page->result->id && $slug == 'exist') $this->redirect('page/exist');
		
		$this->boot->addView($slug);

		$this->boot->title = $page->result->title;
		$this->boot->content = $page->result->content;
	}

	public function exist() {
		$this->boot->title = 'Fatal Error';
	}
}