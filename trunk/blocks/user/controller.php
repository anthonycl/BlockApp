<?php

class User {
	protected $boot;

	function __construct($boot) {
		$this->boot = $boot;
	}
	
	public function index() {
		$this->boot->redirect('user/dashboard');
	}

	public function dashboard() {
		if(!$this->boot->loggedIn) $this->boot->redirect('user/signin');
		
		$this->boot->title = 'Dashboard';
	}

	public function signin() {
		$this->boot->title = 'Signin';

		if($this->boot->input->action) {
			$success = TRUE;
	
			// Validate email
			$valid = $this->boot->validate($this->boot->input->email, 'email');
	
			if(!$valid->match) {
				$this->boot->setFlash(array('title' => 'Please Review Your Email', 'message' => "Your Email {$valid->format}"));
				$success = FALSE;
			}
	
			// Validate password
			$valid = $this->boot->validate($this->boot->input->password, 'password');
			if(!$valid->match) {
				$this->boot->setFlash(array('title' => 'Please Review Your Password', 'message' => "Your Password {$valid->format}"));
				$success = FALSE;
			}
	
			if($success) {
				// Check user credentials
				$query = array('email' => $this->boot->input->email, 'password' => sha1($this->boot->input->password));
				$user = $this->boot->db->findOne('user', $query);

				if($user->result->id) {
		        	// Logged In
		        	$this->boot->session->loggedIn = TRUE;
					$this->boot->buildBlocks(array('response' => (array)$user->result));
					$this->boot->user = $this->boot->blocks;
					$this->boot->setFlash(array('title' => 'You Successfully Signed In', 'message' => "Welcome Back, {$user->result->name}."));
					$this->boot->redirect('user/dashboard');
		        } else {
		        	$this->boot->setFlash(array('title' => 'Invalid Login Information Provided', 'message' => "Looks like the login information entered is invalid, please try again."));
		        }
	        }
	    }
	}

	public function signup() {
		$success = TRUE;

		// Validate name
		$valid = $this->boot->validate($this->boot->input->name, 'name');
		if(!$valid->match) {
			$this->boot->setFlash(array('title' => 'Please Review Your Name', 'message' => "Your Name {$valid->format}"));
			$success = FALSE;
		}

		// Validate email
		$valid = $this->boot->validate($this->boot->input->email, 'email');
		if(!$valid->match) {
			$this->boot->setFlash(array('title' => 'Please Review Your Email', 'message' => "Your Email {$valid->format}"));
			$success = FALSE;
		} else {
			// Check email exist
			$query = array('email' => $this->boot->input->email);
			$response = $this->boot->db->count('user', $query);

			if($response->result > 0) {
				$this->boot->setFlash(array('title' => 'Please Review Your Email', 'message' => "That email you entered is already in use. Please sign in using your existing account, or try a different email. <a href='#/user/forgotpassword'>I forgot my password!</a>"));
				$success = FALSE;
			}
		}

		// Validate password
		$valid = $this->boot->validate($this->boot->input->password, 'password');
		if(!$valid->match) {
			$this->boot->setFlash(array('title' => 'Please Review Your Password', 'message' => "Your Password {$valid->format}"));
			$success = FALSE;
		}

		// Validate password confirm
		if($this->boot->input->password != $this->boot->input->passwordConfirm) {
			$this->boot->setFlash(array('title' => 'Please Review Your Password Confirmation', 'message' => "The Password and this Password Confirmation must exactly match."));
			$success = FALSE;
		}

		if($success) {
			// Add user to db
	        $add = array(
	        	'name' => ucwords($this->boot->input->name),
	        	'email' => $this->boot->input->email,
	        	'password' => sha1($this->boot->input->password),
	        	'locale' => 'en-US',
	        	'active' => 1,
	        	'verified' => 0,
	        	'created' => time(),
	        	'updated' => time()
	        );
	        
	        $user = $this->boot->db->add('user', $add);

			// Technical error
	        if(!$user->result) {
		        $this->boot->logError($user);
			}

			$query = array('email' => $this->boot->input->email, 'password' => sha1($this->boot->input->password));
			$user = $this->boot->db->findOne('user', $query);

			if($user->result->id) {
	        	// Logged In
	        	$this->boot->session->loggedIn = TRUE;
				$this->boot->buildBlocks(array('response' => (array)$user->result));
				$this->boot->user = $this->boot->blocks;
				$this->boot->setFlash(array('title' => 'You Successfully Signed In', 'message' => "Welcome Back, {$user->result->name}."));
				$this->boot->redirect('user/dashboard');
	        } else {
	        	$this->boot->setFlash(array('title' => 'System Failed on Sign Up', 'message' => "There was a problem during your sign up, please try again."));
	        }
        }
	}

	public function signout() {
		session_destroy();
		$this->boot->redirect();
	}
}