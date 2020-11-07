<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->library('authentication');

?><!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($title); ?> - Garnet DeGelder's DocMan <?= DOCMAN_VERSION ?></title>
	<link rel="stylesheet" src="<?= html_escape($this->config->base_url('/resources/css/main.css')) ?>" />
	<link rel="stylesheet" src="<?= html_escape($this->config->base_url('/resources/css/template.css')) ?>" />
</head>

<body>
	<header>
		<ul>
			<li><a href="<?= html_escape($this->config->site_url('/browse')) ?>">Browse</a></li>
			<li>Logged in as <?= htmlspecialchars($this->authentication->get_current_username()) ?></li>
			<li><a href="<?= html_escape($this->config->site_url('/login/logout')) ?>">Log out</a></li>
		</ul>
	</header>
	<main>
