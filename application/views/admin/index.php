<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => 'Administration']); ?>

<h1>Administration</h1>

<ul>
	<li><a href="<?= htmlspecialchars(site_url('/admin/users')) ?>">Users</a></li>
	<!--<li><a href="<?= htmlspecialchars(site_url('/admin/groups')) ?>">Groups</a></li>
	<li><a href="<?= htmlspecialchars(site_url('/admin/mountpoints')) ?>">Mountpoints</a></li>-->
</ul>

<?php $this->load->view('template/footer'); ?>
