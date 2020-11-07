<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header', ['title' => 'Browse']); ?>

<table>
	<thead>
		<tr>
			<th>Type</th>
			<th>Name</th>
			<th>Size</th>
			<th>Last Modified</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($files as $file) { ?>
			<tr>
				<td><?= htmlspecialchars($file['type']) ?></td>
				<td><a href="<?= htmlspecialchars($file['url']) ?>"><?= htmlspecialchars($file['name']) ?></a></td>
				<td><?= htmlspecialchars($file['size']) ?></td>
				<td><?= htmlspecialchars(date('c', $file['mtime'])) ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<?php $this->load->view('template/footer'); ?>
