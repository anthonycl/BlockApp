<form action="<?=$this->url('user/signin')?>" method="post" class="form">
	<input type="email" name="email" value="<?=$this->formValue('email')?>" placeholder="E-Mail" required />
	<input type="password" name="password" value="" placeholder="Password" required />
	
	<input type="hidden" name="action" value="proceed" />
	<input type="submit" name="submit" value="Signin" />
</form>