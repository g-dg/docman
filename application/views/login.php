<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Log In - Garnet DeGelder's DocMan <?= DOCMAN_VERSION ?></title>
	<link rel="stylesheet" href="<?= html_escape($this->config->base_url('/resources/css/main.css')) ?>" />
	<link rel="stylesheet" href="<?= html_escape($this->config->base_url('/resources/css/login.css')) ?>" />
</head>

<body>
	<form action="<?= html_escape($this->config->site_url('/login/do_login')); ?>" method="POST">
		<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
		<h1>Log In - Garnet DeGelder's DocMan <?= DOCMAN_VERSION ?></h1>
		<label for="username">Username:</label>
		<input id="username" name="username" type="text" />
		<br />
		<label for="password">Password:</label>
		<input id="password" name="password" type="password" />
		<br />
		<input type="submit" value="Log In" />
		<?= isset($_SESSION['docman_login_result']) ? html_escape($_SESSION['docman_login_result']) : ''; ?>
	</form>
</body>

</html>
