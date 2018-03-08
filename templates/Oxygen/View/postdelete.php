<?php $this->layout('main') ?>

<?php ($hook = \Punbb\ForumFunction::get_hook('dl_main_output_start')) ? eval($hook) : null; ?>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<ul class="info-list">
				<li><span><?= $lang_delete['Forum'] ?>:<strong> <?= $this->e($cur_post['forum_name']) ?></strong></span></li>
				<li><span><?= $lang_delete['Topic'] ?>:<strong> <?= $this->e($cur_post['subject']) ?></strong></span></li>				
			</ul>
		</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_pre_post_display')) ? eval($hook) : null; ?>
		<div class="post singlepost">
			<div class="posthead">
				<h3 class="hn post-ident"><?= implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_new_post_head_option')) ? eval($hook) : null; ?>
			</div>
			<div class="postbody">
				<div class="post-entry">
					<h4 class="entry-title hn"><?= $forum_page['item_subject'] ?></h4>
					<div class="entry-content">
						<?= $cur_post['message']."\n" ?>
					</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?= $forum_page['form_action'] ?>">
			<div class="hidden">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="csrf_token" value="<?= \Punbb\ForumFunction::generate_form_token($forum_page['form_action']) ?>" />
			</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_pre_confirm_delete_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?= ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?= ($cur_post['is_topic']) ? $lang_delete['Delete topic'] : $lang_delete['Delete post'] ?></strong></legend>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_pre_confirm_delete_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?= ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?= ++$forum_page['fld_count'] ?>" name="req_confirm" value="1" checked="checked" /></span>
						<label for="fld<?= $forum_page['fld_count'] ?>"><span><?= $lang_delete['Please confirm'] ?></span> <?php printf(((($cur_post['is_topic'])) ? $lang_delete['Delete topic label'] : $lang_delete['Delete post label']), \Punbb\ForumFunction::forum_htmlencode($cur_post['poster']), \Punbb\ForumFunction::format_time($cur_post['posted'])) ?></label>
					</div>
				</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_pre_confirm_delete_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_confirm_delete_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="delete" value="<?= ($cur_post['is_topic']) ? $lang_delete['Delete topic'] : $lang_delete['Delete post'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?= $lang_common['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php ($hook = \Punbb\ForumFunction::get_hook('dl_end')) ? eval($hook) : null; ?>
