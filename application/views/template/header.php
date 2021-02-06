<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->library('authentication');

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($title); ?> - Garnet DeGelder's DocMan v<?= htmlspecialchars(DOCMAN_VERSION) ?><?= isset($_SERVER['SERVER_NAME']) ? ' on ' . htmlspecialchars($_SERVER['SERVER_NAME']) : '' ?></title>
	<link rel="stylesheet" href="<?= html_escape(base_url('/resources/css/main.css')) ?>" />
	<link rel="stylesheet" href="<?= html_escape(base_url('/resources/css/template.css')) ?>" />
</head>

<body>
	<header>
		<h1><?= htmlspecialchars($title); ?></h1>
		<ul>
			<li><a href="<?= html_escape(site_url('/browse')) ?>">Browse</a></li>
			<?php if ($this->authentication->get_current_user_type() === 'admin') { ?>
				<li><a href="<?= html_escape(site_url('/admin')) ?>">Administration</a></li>
			<?php } ?>
			<li>Logged in as <?= htmlspecialchars($this->authentication->get_current_username()) ?></li>
			<li><a href="<?= html_escape(site_url('/login/logout')) ?>">Log out</a></li>
		</ul>
		<div style="clear:both;"></div>
	</header>
	<main>
