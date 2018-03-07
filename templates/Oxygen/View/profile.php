<?php $this->layout('main') ?>

<?=$this->section('profile')?>

<?php $this->push('main_menu') ?>
<?php if (isset($forum_page['main_menu'])) : ?>	
<?= $this->insert('partials/main_menu', ['forum_page' => $forum_page]) ?>
<?php endif ?>
<?php $this->end() ?>