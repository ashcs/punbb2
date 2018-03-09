<?php $this->layout('main') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('mi_rules_output_start')) ? eval($hook) : null; ?>
	<div class="main-head">
		<h2 class="hn"><span><?= $lang_common['Rules'] ?></span></h2>
	</div>

	<div class="main-content main-frm">
		<div id="rules-content" class="ct-box user-box">
			<?= $forum_config['o_rules_message']."\n" ?>
		</div>
	</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('mi_rules_end')) ? eval($hook) : null; ?>
