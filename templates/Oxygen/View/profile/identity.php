<?php $this->layout('profile', ['forum_page' => $forum_page]) ?>

<?php $this->start('profile') ?>

	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Identity welcome'] : $lang_profile['Identity welcome user'], \Punbb\ForumFunction::forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

if ($forum_page['has_required']): ?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo $lang_common['Required warn'] ?></p>
		</div>
<?php endif; ?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php if ($forum_page['has_required']): ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_req_info_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_username')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod'] && ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['g_mod_rename_users'] == '1')): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Username'] ?></span> <small><?php echo $lang_profile['Username help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_username" value="<?php echo(isset($_POST['req_username']) ? \Punbb\ForumFunction::forum_htmlencode($_POST['req_username']) : \Punbb\ForumFunction::forum_htmlencode($user['username'])) ?>" size="35" maxlength="25" required /></span>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_email')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['E-mail'] ?></span> <small><?php echo $lang_profile['E-mail help'] ?></small></label><br />
						<span class="fld-input"><input type="email" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_email" value="<?php echo(isset($_POST['req_email']) ? \Punbb\ForumFunction::forum_htmlencode($_POST['req_email']) : \Punbb\ForumFunction::forum_htmlencode($user['email'])) ?>" size="35" maxlength="80" required /></span>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_req_info_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_req_info_fieldset_end')) ? eval($hook) : null; ?>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_personal_fieldset')) ? eval($hook) : null; ?><?php $forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Personal legend'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_realname')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Realname'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[realname]" value="<?php echo(isset($form['realname']) ? \Punbb\ForumFunction::forum_htmlencode($form['realname']) : \Punbb\ForumFunction::forum_htmlencode($user['realname'])) ?>" size="35" maxlength="40" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_title')) ? eval($hook) : null; ?>
<?php if ($forum_user['g_set_title'] == '1'): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Title'] ?></span><small><?php echo $lang_profile['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="title" value="<?php echo(isset($_POST['title']) ? \Punbb\ForumFunction::forum_htmlencode($_POST['title']) : \Punbb\ForumFunction::forum_htmlencode($user['title'])) ?>" size="35" maxlength="50" /></span><br />
					</div>
				</div>
<?php endif; ?>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_location')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Location'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[location]" value="<?php echo((isset($form['location']) ? \Punbb\ForumFunction::forum_htmlencode($form['location']) : \Punbb\ForumFunction::forum_htmlencode($user['location']))) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_admin_note')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Admin note'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="admin_note" value="<?php echo(isset($_POST['admin_note']) ? \Punbb\ForumFunction::forum_htmlencode($_POST['admin_note']) : \Punbb\ForumFunction::forum_htmlencode($user['admin_note'])) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_num_posts')) ? eval($hook) : null; ?>
<?php if ($forum_user['g_id'] == FORUM_ADMIN): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Edit count'] ?></span></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo $forum_page['fld_count'] ?>" name="num_posts" value="<?php echo $user['num_posts'] ?>" size="8" maxlength="8" /></span>
					</div>
				</div>
<?php endif; ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_personal_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_personal_fieldset_end')) ? eval($hook) : null; ?><?php $forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Contact legend'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_url')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Website'] ?></span></label><br />
						<span class="fld-input"><input type="url" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[url]" value="<?php echo(isset($form['url']) ? \Punbb\ForumFunction::forum_htmlencode($form['url']) : \Punbb\ForumFunction::forum_htmlencode($user['url'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_facebook')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Facebook'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[facebook]" placeholder="<?php echo $lang_profile['Name or Url'] ?>" value="<?php echo(isset($form['facebook']) ? \Punbb\ForumFunction::forum_htmlencode($form['facebook']) : \Punbb\ForumFunction::forum_htmlencode($user['facebook'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_twitter')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Twitter'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[twitter]" placeholder="<?php echo $lang_profile['Name or Url'] ?>" value="<?php echo(isset($form['twitter']) ? \Punbb\ForumFunction::forum_htmlencode($form['twitter']) : \Punbb\ForumFunction::forum_htmlencode($user['twitter'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_linkedin')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['LinkedIn'] ?></span></label><br />
						<span class="fld-input"><input type="url" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[linkedin]" value="<?php echo(isset($form['linkedin']) ? \Punbb\ForumFunction::forum_htmlencode($form['linkedin']) : \Punbb\ForumFunction::forum_htmlencode($user['linkedin'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_contact_fieldset_end')) ? eval($hook) : null; ?>				
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_contact_fieldset_end')) ? eval($hook) : null; ?>			
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Contact messengers legend'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_jabber')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Jabber'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="email" name="form[jabber]" value="<?php echo(isset($form['jabber']) ? \Punbb\ForumFunction::forum_htmlencode($form['jabber']) : \Punbb\ForumFunction::forum_htmlencode($user['jabber'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_skype')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Skype'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[skype]" value="<?php echo(isset($form['skype']) ? \Punbb\ForumFunction::forum_htmlencode($form['skype']) : \Punbb\ForumFunction::forum_htmlencode($user['skype'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_msn')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['MSN'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[msn]" value="<?php echo(isset($form['msn']) ? \Punbb\ForumFunction::forum_htmlencode($form['msn']) : \Punbb\ForumFunction::forum_htmlencode($user['msn'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_icq')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['ICQ'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[icq]" value="<?php echo(isset($form['icq']) ? \Punbb\ForumFunction::forum_htmlencode($form['icq']) : $user['icq']) ?>" size="20" maxlength="12" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_aim')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['AOL IM'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[aim]" value="<?php echo(isset($form['aim']) ? \Punbb\ForumFunction::forum_htmlencode($form['aim']) : \Punbb\ForumFunction::forum_htmlencode($user['aim'])) ?>" size="20" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_yahoo')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Yahoo'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[yahoo]" value="<?php echo(isset($form['yahoo']) ? \Punbb\ForumFunction::forum_htmlencode($form['yahoo']) : \Punbb\ForumFunction::forum_htmlencode($user['yahoo'])) ?>" size="20" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_pre_messengers_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('pf_change_details_identity_messengers_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /></span>
			</div>
		</form>
	</div>
<?php $this->stop() ?>	