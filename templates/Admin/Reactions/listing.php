<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Reactions\Model\Entity\Reaction> $reactions
 */
$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills flex-column">
        <li class="nav-item heading"><?= __d('reactions', 'Actions') ?></li>
        <li class="nav-item">
			<?= $this->Html->link(__d('reactions', 'Back'), ['action' => 'index'], ['class' => 'nav-link']) ?>
        </li>
    </ul>
</nav>
<div class="reactions index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __d('reactions', 'Reactions') ?></h2>

    <div class="">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('model') ?></th>
                    <th><?= $this->Paginator->sort('foreign_key') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
					<th><?= __d('reactions', 'Reaction') ?></th>
                    <th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
                    <th class="actions"><?= __d('reactions', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reactions as $reaction): ?>
                <tr>
                    <td><?= h($reaction->model) ?></td>
                    <td><?= h($reaction->foreign_key) ?></td>
                    <td><?= $reaction->hasValue('user') ? $this->Html->link($reaction->user->name ?? (string)$reaction->user->id, ['controller' => 'Users', 'action' => 'view', $reaction->user->id]) : '' ?></td>
					<td><?php echo h($reaction->reaction); ?></td>
                    <td><?= $this->Time->nice($reaction->created) ?></td>
                    <td class="actions">
						<?php
						$label = __d('reactions', 'Delete');
						if ($this->helpers()->has('Icon')) {
							$label = $this->Icon->render('delete');
						}
						echo $this->Form->postButton($label, ['action' => 'delete', $reaction->id], [
							'escapeTitle' => false,
							'class' => 'btn btn-link p-0 align-baseline',
							'form' => [
								'class' => 'd-inline',
								'data-confirm-message' => __d('reactions', 'Are you sure you want to delete # {0}?', $reaction->id),
							],
						]);
						?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo $this->element('Reactions.pagination'); ?>
</div>
<script<?= $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '' ?>>
document.querySelectorAll('form[data-confirm-message]').forEach(function(form) {
	form.addEventListener('submit', function(e) {
		if (!confirm(this.dataset.confirmMessage)) {
			e.preventDefault();
		}
	});
});
</script>
