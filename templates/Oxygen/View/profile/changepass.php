<?php $this->layout('profile', ['forum_page' => $forum_page]) ?>

<?php $this->start('profile') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_output_start')) ? eval($hook) : null; ?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo $forum_page['own_profile'] ? $lang_profile['Change your password'] : sprintf($lang_profile['Change user password'], \Punbb\ForumFunction::forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Change pass errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo $lang_common['Required warn'] ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>" autocomplete="off">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_old_password')) ? eval($hook) : null; ?>
<?php if (!$forum_user['is_admmod'] || $forum_user['id'] == $id): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Old password'] ?></span> <small><?php echo $lang_profile['Old password help'] ?></small></label><br />
						<span class="fld-input"><input type="<?php echo($forum_config['o_mask_passwords'] == '1' ? 'password' : 'text') ?>" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_old_password" size="35" value="<?php if (isset($_POST['req_old_password'])) echo \Punbb\ForumFunction::forum_htmlencode($_POST['req_old_password']); ?>" required /></span>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_new_password')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count']; if ($forum_config['o_mask_passwords'] == '1') echo ' prepend-top'; ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['New password'] ?></span> <small><?php echo $lang_profile['Password help'] ?></small></label><br />
						<span class="fld-input"><input type="<?php echo($forum_config['o_mask_passwords'] == '1' ? 'password' : 'text') ?>" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password1" size="35" value="<?php if (isset($_POST['req_new_password1'])) echo \Punbb\ForumFunction::forum_htmlencode($_POST['req_new_password1']); ?>" required /></span><br />
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_new_password_confirm')) ? eval($hook) : null; ?>
<?php if ($forum_config['o_mask_passwords'] == '1'): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Confirm new password'] ?></span> <small><?php echo $lang_profile['Confirm password help'] ?></small></label><br />
						<span class="fld-input"><input type="<?php echo($forum_config['o_mask_passwords'] == '1' ? 'password' : 'text') ?>" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password2" size="35" value="<?php if (isset($_POST['req_new_password2'])) echo \Punbb\ForumFunction::forum_htmlencode($_POST['req_new_password2']); ?>" required /></span><br />
					</div>
				</div>
<?php endif; ?>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_pass_normal_end')) ? eval($hook) : null; ?>


<?php $this->stop() ?>	
