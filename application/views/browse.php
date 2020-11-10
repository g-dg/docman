<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => 'Browse']); ?>

<table>
	<thead>
		<tr>
			<th>Type</th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=name&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Name</a></th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=mtime&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Last Modified</a></th>
			<th><a href="<?= htmlspecialchars(site_url('/browse' . $current_dir_link)) . '?sort=size&amp;order=' . ($sort_order == 'asc' ? 'desc' : 'asc') ?>">Size</a></th>
			<th><a href="#">Tags</a></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($files as $file) { ?>
			<tr>
				<td><?= htmlspecialchars($file['type']) ?></td>
				<td><a href="<?= htmlspecialchars($file['url']) ?>"><?= htmlspecialchars($file['name']) ?></a></td>
				<td><?= htmlspecialchars(date('c', $file['mtime'])) ?></td>
				<td><?= htmlspecialchars($file['size']) ?></td>
				<td>
					<?php foreach($file['tags'] as $tag) { ?>
						<?= htmlspecialchars($tag['tag_name']); ?>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<?php $this->load->view('template/footer'); ?>
