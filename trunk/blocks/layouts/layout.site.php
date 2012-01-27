<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="Description" content="<?=$this->config->siteName?> - <?=$this->title ? $this->title : $this->config->siteSlogan?>" />
	<meta name="robots" content="all, index, follow" />
	<meta name="distribution" content="global" />

	<title><?=$this->title ? $this->title : $this->config->siteSlogan?> | <?=$this->config->siteName?></title>

	<link rel="shortcut icon" href="/favicon.ico" />
	<link type="text/css" rel="stylesheet" href="/css/jquery.aristo.css" />
	<link type="text/css" rel="stylesheet" href="/css/jquery.pnotify.css" />
	<link type="text/css" rel="stylesheet" href="/css/style.css" />

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" /></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js" /></script>
	<script type="text/javascript" src="/js/jquery.pnotify.js" /></script>
	<script type="text/javascript" src="/js/scripts.js" /></script>
</head>
<body>
	<div id="flashes">
		<?php if($flashes = $this->getFlash()): ?>
			<?php foreach($flashes as $flash): ?>
				<div class="hide">
					<p class="title"><?=$flash->title?></p>
					<p class="message"><?=$flash->message?></p>
					<p class="sticky"><?=$flash->sticky?></p>
					<p class="type"><?=$flash->type?></p>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div id="container">
		<div class="header">
			<div id="header" class="padded">
				<h1 class="float-left">
					<a href="<?=$this->url()?>"><?=$this->config->siteName?></a>
				</h1>
				
				<ul id="menu" class="float-right">
					<li><a href="<?=$this->url()?>" title="Home">Home</a></li>
					<li><a href="<?=$this->url('user/signin')?>" title="Signin">Signin</a></li>
					<li><a href="<?=$this->url('user/signup')?>" title="Signup">Signup</a></li>
				</ul>
			</div>
		</div>

		<div class="content">
			<div id="content" class="padded">
				<?php $this->renderView(); ?>
			</div>
		</div>

		<div class="footer">
			<div id="footer" class="padded">
				Copyright &copy; <?=date('Y')?> <?=$this->config->siteName?>. All Rights Reserved.
			</div>
		</div>
	</div>
</body>
</html>