<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => 'My Account']); ?>

<dl>
	<dt>Username:</dt>
	<dd><?= html_escape($username) ?></dd>
	<dt>User ID:</dt>
	<dd><?= html_escape($user_id) ?></dd>
	<dt>User Type:</dt>
	<dd><?= html_escape($user_type) ?></dd>
</dl>

<?php if ($allow_password_change) : ?>
	<fieldset>
		<legend>Change Password</legend>
		<script>
			function checkPasswordMatch() {
				if (document.getElementById('new_password1').value !== document.getElementById('new_password2').value) {
					alert("New passwords don't match.");
					return false;
				} else {
					return true;
				}
			}
		</script>
		<form action="<?= html_escape($this->config->site_url('/settings')); ?>?action=change_password" method="POST">
			<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
			<label for="old_password">Old Password:</label>
			<input id="old_password" name="old_password" value="" type="password" />
			<br />
			<label for="new_password1">New Password:</label>
			<input id="new_password1" name="new_password1" value="" type="password" />
			<br />
			<label for="new_password2">New Password (again):</label>
			<input id="new_password2" name="new_password2" value="" type="password" />
			<br />
			<input type="submit" value="Change Password" onclick='return checkPasswordMatch();' />
		</form>
	</fieldset>
<?php endif ?>

<?php $this->load->view('template/footer'); ?>