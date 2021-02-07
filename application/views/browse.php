<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => (trim($title) !== '' ? $title . ' - Browse' : 'Browse' )]); ?>

<form action="<?= html_escape($this->config->site_url('/search' . $current_dir_link)); ?>" method="POST" style="display: inline;">
	<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
	<input type="search" name="q" value="" placeholder="Search" ?>
	<input type="submit" value="Search" />
</form>
|
<a href="<?= html_escape($this->config->site_url('/filter' . $current_dir_link)); ?>">Filter</a>
|
<form action="<?= html_escape($this->config->site_url('/upload' . $current_dir_link)); ?>" enctype="multipart/form-data" method="POST" style="display: inline;">
	<input name="_csrf_token" value="<?= html_escape(get_csrf_token()); ?>" type="hidden" />
	<label for="upload_file">Upload:</label>
	<input id="upload_file" name="file" type="file" required="required" />
	<input type="submit" value="Upload" />
</form>

<table>
	<thead>
		<tr>
			<th>Type</th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=name&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Name</a></th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=mtime&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Last Modified</a></th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=size&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Size</a></th>
			<th>Tags</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($files as $file) { ?>
			<tr>
				<td><?= htmlspecialchars($file['basetype']) ?></td>
				<td><a href="<?= htmlspecialchars($file['url']) ?>"<?= ($file['type'] != 'dir') ? ' target="_blank"' : '' ?>><?= htmlspecialchars($file['name']) ?></a></td>
				<td><?= htmlspecialchars(date('c', $file['mtime'])) ?></td>
				<td><?= htmlspecialchars($file['size']) ?></td>
				<td>
					<?php foreach($file['tags'] as $tag) { ?>
						<?= htmlspecialchars($tag['tag_name']); ?>
					<?php } ?>
				</td>
				<td><?php if ($file['realname'] != '..'): ?><a href="<?= htmlspecialchars($file['properties_url']) ?>">Properties</a><?php endif ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<?php $this->load->view('template/footer'); ?>
