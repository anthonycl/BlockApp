<?php

setlocale(LC_MONETARY, 'en_US.utf8');

$config = new stdClass;

# TLD
$config->tld = substr($_SERVER['SERVER_NAME'], 0, strpos($_SERVER['SERVER_NAME'], '.'));

# Site Name
$config->siteName = 'My Site';

# Site Slogan
$config->siteSlogan = 'My Slogan';

# Default Block Controller
$config->defaultController = 'page';

# Default Layout
$config->defaultLayout = 'site';

# Default Layout When Logged In
$config->defaultLoggedInLayout = 'site';

# Allowed Blocks Without Login
$config->allowedControllers = array('page', 'user');

# Config Based on URL
switch($config->tld) {
	case 'dev':
	case 'local':
		# Database Driver
		$config->dbDriver = 'mysql';

		# Database User
		$config->dbUser = 'root';
		
		# Database Pass
		$config->dbPass = 'root';
		
		# Database Host
		$config->dbHost = 'localhost';
		
		# Database Name
		$config->db = 'blockapp';

		$config->siteDomain = 'dev.mysite.com';
		$config->sitePath = '/User/admin/Sites/' . $config->siteDomain . '/html/';
	break;
	
	default:
		# Database Driver
		$config->dbDriver = 'mysql';

		# Database User
		$config->dbUser = 'root';
		
		# Database Pass
		$config->dbPass = 'root';
		
		# Database Host
		$config->dbHost = 'localhost';
		
		# Database Name
		$config->db = 'blockapp';

		$config->siteDomain = 'mysite.com';
		$config->sitePath = '/home/' . $config->siteDomain . '/html/';
	break;
}