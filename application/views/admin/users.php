<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => 'Users - Administration']); ?>

<fieldset>
	<legend>Create New User</legend>
	<form action="<?= html_escape($this->config->site_url('/admin/action')); ?>?action=user_create" method="POST">
		<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
		<label for="username">Username:</label>
		<input id="username" name="username" type="text" />
		<br />
		<label for="user_password">Password:</label>
		<input id="user_password" name="password" type="password" />
		<br />
		<label for="user_type">User Type:</label>
		<select id="user_type" name="user_type">
			<option value="0">Administrator</option>
			<option value="1">Standard User</option>
			<option value="2">Guest</option>
		</select>
		<br />
		<input type="submit" value="Create User" />
</fieldset>

<table>
	<thead>
		<tr>
			<th>Username</th>
			<th>Type</th>
			<th>Delete</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($users as $user) : ?>
			<tr>
				<td><?= html_escape($user['username']) ?></td>
				<td>
					<form action="<?= html_escape($this->config->site_url('/admin/action')); ?>?action=user_change_type" method="POST" style="display: inline;">
						<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
						<input name="user_id" value="<?= $user['id'] ?>" type="hidden" />
						<select name="type">
							<option value="0" <?= $user['type'] == 0 ? 'selected="selected"' : '' ?>>Administrator</option>
							<option value="1" <?= $user['type'] == 1 ? 'selected="selected"' : '' ?>>Standard User</option>
							<option value="2" <?= $user['type'] == 2 ? 'selected="selected"' : '' ?>>Guest</option>
						</select>
						<input type="submit" value="Change Type" />
					</form>
				</td>
				<td>
					<?php if ($user['id'] != $current_user_id) : ?>
						<form action="<?= html_escape($this->config->site_url('/admin/action')); ?>?action=user_delete" method="POST" style="display: inline;">
							<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
							<input name="user_id" value="<?= $user['id'] ?>" type="hidden" />
							<input type="submit" value="Permanently Delete User" onclick='return confirm("Really delete this user?");' />
						</form>
					<?php endif ?>
				</td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>

<?php $this->load->view('template/footer'); ?>