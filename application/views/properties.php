<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => (trim($title) !== '' ? $title . ' - Properties' : 'Properties')]); ?>

<a href="<?= html_escape($this->config->site_url('/browse' . $current_dir_link)); ?>">&lt;- Back</a>

<?php if ($allow_cut) : ?>
	<form action="<?= html_escape($this->config->site_url('/properties' . $file_path)); ?>?action=cut" method="POST">
		<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
		<input type="submit" value="Cut" />
	</form>
<?php endif ?>

<form action="<?= html_escape($this->config->site_url('/properties' . $file_path)); ?>?action=copy" method="POST">
	<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
	<input type="submit" value="Copy" />
</form>

<?php if ($allow_paste) : ?>
	<form action="<?= html_escape($this->config->site_url('/properties' . $file_path)); ?>?action=paste" method="POST">
		<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
		<input type="submit" value="Paste" />
	</form>
<?php endif ?>

<form action="<?= html_escape($this->config->site_url('/properties' . $file_path)); ?>?action=set_display_name" method="POST">
	<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
	<input id="friendly_name" name="new_friendly_name" value="<?= html_escape($friendly_name); ?>" type="text" />
	<input type="submit" value="Rename" />
</form>

<?php $this->load->view('template/footer'); ?>