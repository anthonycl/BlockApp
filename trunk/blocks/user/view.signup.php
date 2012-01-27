<form action="<?=$this->url('user/signup')?>" method="post" class="form">
	<div class="signup">
		<input type="text" name="name" class="form-name" value="<?=$this->formValue('name')?>" placeholder="Full Name" required />
		<input type="email" name="email" class="form-email" value="<?=$this->formValue('email')?>" placeholder="E-Mail" required />
		<input type="password" name="password" class="form-password" value="" placeholder="Password" required />
		<input type="password" name="passwordConfirm" class="form-passwordConfirm" value="" placeholder="Password Confirmation" required />
			
		<p class="legal">By clicking <strong>Sign Up</strong> you agree to <?=$this->config->siteName?>'s <a href="<?=$this->url('page/tos')?>">Terms of Service</a></p>
	
		<input type="hidden" name="action" value="proceed" />
		<input type="submit" name="submit" class="submit" value="Sign Up" />
	</div>
</form>