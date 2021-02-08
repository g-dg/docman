<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => (trim($title) !== '' ? $title . ' - Search' : 'Search')]); ?>

<a href="<?= html_escape($this->config->site_url('/browse' . $current_dir_link)); ?>">&lt;- Back</a>

<p>Results:</p>
<ul>
	<?php foreach ($results as $result): ?>
		<li>
			<a href="<?= html_escape($this->config->site_url('/browse' . $result['path'])); ?>"><?= html_escape($result['display_name']) ?></a>
		</li>
	<?php endforeach ?>
</ul>

<?php $this->load->view('template/footer'); ?>